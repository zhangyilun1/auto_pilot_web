<?php

namespace app\wchat\controller;
use think\Controller;
use clt\WchatOauth;
use think\Db;

class Wchat extends Controller
{
    public $wchat;
    public $weixin_service;
    public $author_appid;
    public $instance_id;
    public $style;
    public $token;
    public function initialize()
    {
        parent::initialize();
        ini_set('date.timezone','Asia/Shanghai');
        $this->wchat = new WchatOauth(); // 微信公众号相关类
        $this->instance_id = 0;
        $value = db('wx_config')->where([ 'key' => 'SHOPWCHAT'])->value('value');
        $value = json_decode($value,true);
        $this->token = $value['token'];
        define("TOKEN", $this->token);
        $this->getMessage();
    }

    /**
     * ************************************************************************微信公众号消息相关方法 开始******************************************************
     */
    /**
     * 关联公众号微信
     *
     */
    public function relateWeixin()
    {
        //$data=input();
        //file_put_contents('./wx_log.txt', json_encode($data).PHP_EOL,FILE_APPEND);
        $sign = input('signature', '');
        if (defined("TOKEN") && isset($sign)) {
            $signature = $sign;
            $timestamp = input('timestamp');
            $nonce = input('nonce');
            $token = TOKEN;
            //file_put_contents('./wx_log.txt', $token.PHP_EOL);
            $tmpArr = array(
                $token,
                $timestamp,
                $nonce
            );
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode($tmpArr);
            $tmpStr = sha1($tmpStr);
            //file_put_contents('./wx_log.txt', $tmpStr.PHP_EOL,FILE_APPEND);
            if ($tmpStr == $signature) {
                $echostr = input('echostr', '');
                if (!empty($echostr)) {
                    ob_clean();
                    exit($echostr);
                    echo $echostr;
                }
                return 1;
            } else {
                return 0;
            }
        }
    }

    public function templatemessage()
    {
        $media_id = input('media_id',0);
        //P($media_id);
        $info = $this->getWeixinMediaDetailByMediaId($media_id);
        //P($info);
        if (! empty($info["media_parent"])) {
            $this->assign("info", $info);
            return view();
        } else {
            echo "图文消息没有查询到";
        }
    }

    private function getWeixinMediaDetailByMediaId($media_id){
        //P($media_id);
        $weixin_media_item =db('wx_media_item');
        //echo $media_id;
        //P($weixin_media_item);
        $item_list = $weixin_media_item->where('id',$media_id)->find();
        //P($item_list);
        if (!empty($item_list)) {
            // 主表
            $weixin_media = db('wx_media');
            $weixin_media_info["media_parent"] = $weixin_media->where(["media_id" => $item_list["media_id"] ])->find();
            //P($weixin_media_info);
            // 微信配置
            $weixin_auth = db('wx_auth');
            $weixin_media_info["weixin_auth"] = $weixin_auth->where(["instance_id" => $weixin_media_info["media_parent"]["instance_id"]])->find();

            $weixin_media_info["media_item"] = $item_list;
            //P($weixin_media_info);
            // 更新阅读次数
            $res = db('wx_media_item')->where('id',$media_id)->setInc('hits');
            //P($weixin_media_item->getLastSql());
            //P($res);
            return $weixin_media_info;
        }
        return null;
    }

    /**
     * 微信开放平台模式(需要对消息进行加密和解密)
     * 微信获取消息以及返回接口
     */
    private function getMessage()
    {
        $from_xml = file_get_contents('php://input');
        if (empty($from_xml)) {
            return;
        }
        $signature = input('msg_signature', '');
        $signature = input('timestamp', '');
        $nonce = input('nonce', '');
        $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        $ticket_xml = $from_xml;
        $postObj = simplexml_load_string($ticket_xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        //file_put_contents('./wx_log.txt', json_encode($postObj).PHP_EOL,FILE_APPEND);
        $this->instance_id = 0;
        if (!empty($postObj->MsgType)) {
            switch ($postObj->MsgType) {
                case "text":
                    //用户发的消息   存入表中
                    //$this->addUserMessage((string)$postObj->FromUserName, (string) $postObj->Content, (string) $postObj->MsgType);
                    $resultStr = $this->MsgTypeText($postObj);
                    break;
                case "event":
                    $resultStr = $this->MsgTypeEvent($postObj);
                    break;
                default:
                    $resultStr = "";
                    break;
            }
        }
        if (!empty($resultStr)) {
            echo $resultStr;
        } else {
            echo '';
        }
    }

    /**
     * 文本消息回复格式
     *
     * @param unknown $postObj
     * @return Ambigous <void, string>
     */
    private function MsgTypeText($postObj)
    {
        $funcFlag = 0; // 星标
        $wchat_replay = $this->wchat->getWhatReplay($this->instance_id, (string)$postObj->Content);

        // 判断用户输入text
        if (!empty($wchat_replay)) { // 关键词匹配回复
            $contentStr = $wchat_replay; // 构造media数据并返回
        } elseif ($postObj->Content == "uu") {
            $contentStr = "shopId：" . $this->instance_id;
        } elseif ($postObj->Content == "TESTCOMPONENT_MSG_TYPE_TEXT") {
            $contentStr = "TESTCOMPONENT_MSG_TYPE_TEXT_callback"; // 微店插件功能 关键词，预留口
        } elseif (strpos($postObj->Content, "QUERY_AUTH_CODE") !== false) {
            $get_str = str_replace("QUERY_AUTH_CODE:", "", $postObj->Content);
            $contentStr = $get_str . "_from_api"; // 微店插件功能 关键词，预留口
        } else {
            $content = $this->wchat->getDefaultReplay($this->instance_id);
            if (!empty($content)) {
                $contentStr = $content;
            } else {
                $contentStr="嗨~我是神农泰，终于等到您了！\r\n 感谢您的关注！\r\n 在这里您可以得到您满意的农副产品和走亲访友的好礼品！";
            }
        }
        if (is_array($contentStr)) {
            $resultStr = $this->wchat->event_key_news($postObj, $contentStr);
        } elseif (!empty($contentStr)) {
            $resultStr = $this->wchat->event_key_text($postObj, $contentStr);
        } else {
            $resultStr = '';
        }
        return $resultStr;
    }

    /**
     * 事件消息回复机制
     */
    // 事件自动回复 MsgType = Event
    private function MsgTypeEvent($postObj)
    {
        $openid=$postObj->FromUserName;
        $contentStr = "";
        switch ($postObj->Event) {
            case "LOCATION"://上报地理位置
                $lat=$postObj->Latitude;
                $lng=$postObj->Longitude;
                $precision=$postObj->Precision;
                //file_put_contents('./wx_log.txt', $postObj.PHP_EOL,FILE_APPEND);
                $loc=array('lat'=>$lat,'lng'=>$lng);
                //$loc=getgps($lat,$lng,true);
                db('users')->where('openid',$openid)->update(['lat'=>$loc['lat'],'lng'=>$loc['lng'],'precision'=>$precision]);
                break;
            case "subscribe": // 关注公众号 添加关注回复
                $has_user=db('users')->where('openid',$openid)->find();
                if(empty($has_user)){
                    $easy_wx=new Easywechat();
                    $wxdata=$easy_wx->getUserInfoByOpenid($openid);
                    //file_put_contents('./wx_log.txt', json_encode($wxdata).PHP_EOL,FILE_APPEND);
                    $userData=array(
                        'subscribe'=>1,
                        'openid'=>$openid,
                        'sex'=>$wxdata['sex'],
                        'reg_time'=>time(),
                        'oauth'=>'wx',
                        'avatar'=>$wxdata['headimgurl'],
                        'username'=>$wxdata['nickname'],
                        'money'=>0,
                        'bind_wx'=>1,
                        'fenxiao_money'=>0
                    );

                    $userData['username']=removeEmoji($userData['username']);

                    // 由场景值获取分销一级id
                    $postArr=self::normalize($postObj);
                    $userData['openid']=$postArr['FromUserName'];
                    //file_put_contents('./wx_log.txt', var_export($postObj,true).'2'.PHP_EOL,FILE_APPEND);
                    $eventKey=$postArr['EventKey'];
                    //file_put_contents('./wx_log.txt', $eventKey.'2'.PHP_EOL,FILE_APPEND);
                    if ($eventKey) {
                        //file_put_contents('./wx_log.txt', $eventKey.'2'.PHP_EOL,FILE_APPEND);
                        $userData['p_id'] =(int) @substr($eventKey, strlen('qrscene_'));
                        //file_put_contents('./wx_log.txt', $userData['p_id'].'2'.PHP_EOL,FILE_APPEND);
                        if ($userData['p_id']) {
                            $puser = @db('users')->where('id', $userData['p_id'])->find();
                            if ($puser) {
                                $userData['p_p_id'] = $puser['p_id']; //  第一级推荐人
                                //$userData['p_p_p_id'] = $puser['p_p_id']; // 第二级推荐人
                                $userData['pids']=$puser['pids'].','.$puser['id'];
                                //他上线分销的下线人数要加1
//                                @db('users')->where('id', $userData['p_id'])->setInc('underline_num');
//                                @db('users')->where('id', $userData['p_p_id'])->setInc('underline_num');
//                                @db('users')->where('id', $userData['p_p_p_id'])->setInc('underline_num');
                                //$wx_msg="您已通过".$puser['username']."分享的二维码成为会员";
                            }
                        } else {
                            $userData['p_id'] = 0;
                            $userData['p_p_id']=0;
                            $userData['pids']='0';
                            $wx_msg="嗨~我是神农泰，终于等到您了！\r\n 感谢您的关注！\r\n 在这里您可以得到您满意的农副产品和走亲访友的好礼品！";
                        }
                    }else{
                        $wx_msg="嗨~我是神农泰，终于等到您了！\r\n 感谢您的关注！\r\n 在这里您可以得到您满意的农副产品和走亲访友的好礼品！";
                    }
                    //file_put_contents('./wx_log.txt', var_export($userData,true).'3'.PHP_EOL,FILE_APPEND);
                    //file_put_contents('./wx_log.txt', var_export(array('1'=>'1','2'=>2),true).'3'.PHP_EOL,FILE_APPEND);
                    $res=Db::name('users')->insertGetId($userData);
                    $wx_msg="嗨~我是神农泰，终于等到您了！\r\n 感谢您的关注！\r\n 在这里您可以得到您满意的农副产品和走亲访友的好礼品！";
                    //file_put_contents('./wx_log.txt', $res.PHP_EOL,FILE_APPEND);
                }else{
                    $res=db('users')->where('openid',$openid)->update(['subscribe'=>1]);
                    $wx_msg="嗨~我是神农泰，终于等到您了！\r\n 感谢您的关注！\r\n 在这里您可以得到您满意的农副产品和走亲访友的好礼品！";
                }
                $contentStr=$wx_msg;
                /**
                $content = $this->wchat->getSubscribeReplay($this->instance_id);
                if (!empty($content)) {
                    $contentStr = $content;
                }
                 **/
                // 构造media数据并返回
                break;
            case "unsubscribe": // 取消关注公众号
                db('users')->where('openid',$openid)->update(['subscribe'=>0]);
                break;
            case "VIEW": // VIEW事件 - 点击菜单跳转链接时的事件推送
                // $this->wchat->weichat_menu_hits_view($postObj->EventKey); //菜单计数
                $contentStr = "";
                break;
            case "SCAN": // SCAN事件 - 用户已关注时的事件推送
                $contentStr="嗨~我是神农泰，终于等到您了！\r\n 感谢您的关注！\r\n 在这里您可以得到您满意的农副产品和走亲访友的好礼品！";
                break;
            case "CLICK": // CLICK事件 - 自定义菜单事件
                $menu_detail = $this->wchat->getWeixinMenuDetail($postObj->EventKey);
                $media_info = $this->wchat->getWeixinMediaDetail($menu_detail['media_id']);
                $contentStr = $this->wchat->getMediaWchatStruct($media_info); // 构造media数据并返回
                break;
            default:
                break;
        }
        // $contentStr = $postObj->Event."from_callback";//测试接口正式部署之后注释不要删除
        if (is_array($contentStr)) {
            $resultStr = $this->wchat->event_key_news($postObj, $contentStr);
        } else {
            $resultStr = $this->wchat->event_key_text($postObj, $contentStr);
        }
        return $resultStr;
    }

    private static function normalize($obj)
    {
        $result = null;

        if (is_object($obj)) {
            $obj = (array) $obj;
        }

        if (is_array($obj)) {
            foreach ($obj as $key => $value) {
                $res = self::normalize($value);
                if (($key === '@attributes') && ($key)) {
                    $result = $res;
                } else {
                    $result[$key] = $res;
                }
            }
        } else {
            $result = $obj;
        }

        return $result;
    }

    public function notify(){
        $easy_wx=new Easywechat();
        $easy_wx->notify();
    }

}