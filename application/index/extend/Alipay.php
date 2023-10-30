<?php
namespace app\index\extend;
use think\Db;
use Yansongda\Pay\Log;
use Yansongda\Pay\Pay;

class Alipay
{
    protected $config;
    protected $member;
    protected $member_id;
    protected $target_url;

    public function __construct()
    {
        $zfb_config=Db::name('site')->where('id',1)->field('site_name,sign,zfb_app_id,zfb_public_key,zfb_private_key')->find();
        $config = [
            'app_id' => $zfb_config['zfb_app_id'],
            'notify_url' => 'http://'.$_SERVER['HTTP_HOST'].'/ali_notify.html',
            'ali_public_key' => $zfb_config['zfb_public_key'],
            'private_key' => $zfb_config['zfb_private_key'],
            'log' => [
                'file' => './logs/alipay.log',
                'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                'type' => 'single', // optional, 可选 daily.
                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
            ],
            'http' => [
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ],
            //'mode' => 'dev', // optional,设置此参数，将进入沙箱模式
        ];
        $this->config=$config;
        //P($this->config);
    }

    public function pay($data)
    {

        $order = [
            'out_trade_no' => $data['out_trade_no'],
            'total_amount' => $data['money'],
            'subject'      => $data['subject']
        ];
        //P($order);
        // 将返回字符串，供后续 APP 调用，调用方式不在本文档讨论范围内，请参考官方文档。
        $pay= Pay::alipay($this->config)->app($order);
        $pay_str=$pay->getContent();
        //P($pay);
        //P($pay_str);
        ajax_return(1,'',['pay'=>$pay_str]);
    }
}