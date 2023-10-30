<?php

namespace app\wchat\controller;
use app\common\controller\Commons;
use EasyWeChat\Factory;


class Easywechat extends Commons
{
    public $wechat_config;
    public $app;

    public function __construct(){
        parent::__construct();
        $wx_config=$this->site_config;
        //P($wx_config);
        $this->wechat_config=array(
            'app_id'=>$wx_config['wx_appid'],
            'secret'=>$wx_config['wx_appsecret'],
            'token'=>$wx_config['wx_token'],
            'response_type'=>'array',
            'oauth' => [
                'scopes'   => ['snsapi_base'],
                'callback'=>BASE_URL.'/common/wx_login.html'
            ]
        );
        //P($this->wechat_config);
        $app=Factory::officialAccount($this->wechat_config);
        $this->app=$app;
    }

    public function send_wx_msg($openid,$message){
        $res=$this->app->customer_service->message($message)->to($openid)->send();
        //return $res;
    }

    public function send_subscribe_message($data){
        $res=$this->app->subscribe_message->send($data);
        //P($res);
        file_put_contents('./wx_log.txt', json_encode($res).PHP_EOL,FILE_APPEND);
    }

    public function getUserInfoByOpenid($openid){
        return $this->app->user->get($openid);
    }

    public function web_oauth(){
        $oauth=$this->app->oauth;
        // 未登录
        $oauth->redirect()->send();
    }

    private function get_url() {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
    }

    public function get_js_sdk(){
        $wx_js_sdk=$this->app->jssdk->buildConfig(
            array(
                'closeWindow',
                'checkJsApi',
                'updateAppMessageShareData',
                'updateTimelineShareData',
                'onMenuShareWeibo',
                'showOptionMenu',
                'hideMenuItems',
                'onMenuShareQZone',
                'scanQRCode',
                'previewImage',
                'downloadImage'
            ),
            false,
            $beta=false,
            $json=true
        );
        return $wx_js_sdk;
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

    public function getQrCode($user_id){
        $result = $this->app->qrcode->forever($user_id);
        $url = $this->app->qrcode->url($result['ticket']);
        //P($url);
        $content=@file_get_contents($url);
        $file='./uploads/qr/'.$user_id.'/'.md5($user_id.time()).'.jpg';
        if (!($_exists = file_exists('./uploads/qr/'.$user_id.'/'))){
            mkdir('./uploads/qr/'.$user_id);
        }
        $res=@file_put_contents($file,$content);
        if($res){
            db('users')->where('id',$user_id)->update(['qr_code'=>trim($file,'.')]);
            return trim($file,'.');
        }else{
            return false;
        }

    }

    //生成带参数的小程序码
    public function getAppCodeWithScene($scene,$filename,$path){
        $response=$this->app->app_code->getUnlimit($scene,[
            'page'=>'pages/index/index',
            'width'=>600
        ]);
        //P($response);
        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $filename = $response->save($path,$filename);
        }
        return $filename;
    }

    public function check_sensitive_string($string){
        //检测是否存在违规内容
        $wx_app=Factory::miniProgram($this->wechat_config);
        $result=$wx_app->content_security->checkText($string);
        //P($string);
        //P($result);
        //$result=json_decode($result);
        //P($result);
        if($result['errcode']==87014){
            return true;
        }else{
            return false;
        }
    }



    public function check_sensitive_img($img){
        //检测是否存在违规图片
        $wx_app=Factory::miniProgram($this->wechat_config);
        $result=$wx_app->content_security->checkImage($img);
        //$result=json_decode($result);
        if($result['errcode']==87014){
            return true;
        }else{
            return false;
        }
    }


}