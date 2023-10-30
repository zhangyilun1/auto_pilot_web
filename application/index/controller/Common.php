<?php
namespace app\index\controller;
use app\common\controller\Commons;
use app\wchat\controller\Easywechat;
use think\Controller;
use EasyWeChat\Factory;
use think\Db;
use think\facade\Cookie;

class Common extends Commons {
    public $app;
    public $qr_code;

    public function __construct(){
        parent::__construct();
        if(!is_weixin()){
            die('<script>alert("请在微信中打开！")</script>');
        }
    }

    public function wx_login(){
        $wx_config=$this->site_config;
//        if(strpos($_SERVER['HTTP_USER_AGENT'], 'miniprogram') !== false){
//            $wx_config['wx_appid']='';
//            $wx_config['wx_appsecret']='';
//        }
        $wechat_config=array(
            'app_id'=>$wx_config['wx_appid'],
            'secret'=>$wx_config['wx_appsecret'],
            'response_type'=>'array',
            'log' => [
                'level' => 'debug',
                'file' => __DIR__.'/wechat.log',
            ],
            'oauth' => [
                'scopes'   => ['snsapi_base'],
                'callback'=>BASE_URL.'/common/wx_login.html'
            ]
        );
        $app=Factory::officialAccount($wechat_config);
        $this->app=$app;
        // 获取 OAuth 授权结果用户信息
        $oauth=$this->app->oauth;
        $user = $oauth->user();
        $user = $user->toArray();
        $openid=$user['id'];

        session('openid',$openid);
        if(session('?member_id')){
            Db::name('member')
                ->where('id',session('member_id'))
                ->update(['wxh5_openid'=>$openid]);
        }

        $targetUrl = empty(session('target_url')) ? '/' : session('target_url');
        //$targetUrl='/';
        //P($targetUrl);
        $this->redirect($targetUrl);
    }


}
