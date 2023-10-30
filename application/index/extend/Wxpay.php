<?php
namespace app\index\extend;
use index\wchat\controller\Easywechat;
use Endroid\QrCode\QrCode;
use think\Db;
use Yansongda\Pay\Log;
use Yansongda\Pay\Pay;

class Wxpay
{
    protected $config;
    protected $member;
    protected $member_id;
    protected $target_url;
    protected $type;
    protected $header;

    public function __construct()
    {
        $wx_config=Db::name('site')->where('id',1)->field('site_name,sign,wx_appid,wx_app_key,wx_mch_id')->find();
        $config = [
            'appid' => $wx_config['wx_appid'], // APP APPID，APP支付使用
            'app_id' => $wx_config['wx_appid'], // 公众号 APPID
            'miniapp_id' => $wx_config['wx_appid'], // 小程序 APPID
            'mch_id' => $wx_config['wx_mch_id'],
            'key' => $wx_config['wx_app_key'],
            'notify_url' => 'http://'.$_SERVER['HTTP_HOST'].'/wx_notify.html',
            'cert_client' => './cert/apiclient_cert.pem', // optional，退款等情况时用到
            'cert_key' => './cert/apiclient_key.pem',// optional，退款等情况时用到
            'log' => [ // optional
                'file' => './logs/wechat.log',
                'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                'type' => 'single', // optional, 可选 daily.
                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
                // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
            ],
            //'mode' => '', // optional, dev/hk;当为 `hk` 时，为香港 gateway。
        ];
        //P($config);
        $this->config=$config;
    }

    public function pay($data)
    {
        $order = [
            'out_trade_no' => $data['out_trade_no'],
            'body' => $data['subject'],
            'total_fee' => $data['money']*100
        ];
        $json= Pay::wechat($this->config)->app($order);
        ajax_return(1,'',['pay'=>json_decode($json->getContent())]);
    }

}