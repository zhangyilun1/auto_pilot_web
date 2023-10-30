<?php

namespace app\push\controller;
use think\worker\Server;

class Worker extends Server
{
    protected $socket = 'websocket://0.0.0.0:2345';


    public function __construct()
    {
        parent::__construct();

        global $worker;
        $worker = $this->worker;
        $worker->count = 1;

        // 新增加一个属性，用来保存uid到connection的映射(uid是用户id或者客户端唯一标识)
        $worker->uidConnections = array();
        // 当有客户端发来消息时执行的回调函数
        $worker->onMessage = function ($connection, $data) {
            global $worker;
            $data=json_decode($data,true);
            if(empty($data)){
                return false;
            }

            $uid=$data['uid'];
            $type=$data['type'];
            switch ($type){

                case 'pong':
                    // 客户端回应服务端的心跳
                    break;

                case 'login':
                    // 客户端登录 message格式: {type:login, name:xx, room_id:1} ，添加到客户端，广播给所有客户端xx进入聊天室
                    if(empty($uid)){
                        return false;
                    }
                    $connection->uid = $uid;
                    $worker->uidConnections[$connection->uid] = $connection;
                    return $connection->send(json_encode(['type'=>'login','content'=>$connection->uid.'登录']));
                    break;
                case 'msg':
                    // 给特定uid发送
                    $this->sendMessageByUid($uid, json_encode($data));
                    break;
                case 'msg_all':
                    $this->broadcast(json_encode($data));
                    break;
                default:
                    return false;
            }

        };

        // 当有客户端连接断开时
        $worker->onClose = function ($connection) {
            global $worker;
            if (isset($connection->uid)) {
                // 连接断开时删除映射
                unset($worker->uidConnections[$connection->uid]);
            }
        };


    }

    // 向所有验证的用户推送数据
    function broadcast($message)
    {
        global $worker;
        foreach ($worker->uidConnections as $connection) {
            $connection->send($message);
        }
    }

    // 针对uid推送数据
    function sendMessageByUid($uid, $message)
    {
        global $worker;
        if (isset($worker->uidConnections[$uid])) {
            $connection = $worker->uidConnections[$uid];
            $connection->send($message);
        }
    }
}