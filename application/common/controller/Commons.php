<?php
namespace app\common\controller;
use think\Controller;
use think\Db;
use think\facade\Cookie;
class Commons extends Controller{
    protected $error_head;
    protected $site_config;
    protected $no_need_login;

    public function __construct()
    {

        parent::__construct();

        define('MODULE_NAME',$this->request->module());  // 当前模块名称是
        define('CONTROLLER_NAME',$this->request->controller()); // 当前控制器名称
        define('ACTION_NAME',$this->request->action()); // 当前操作名称
        // 定义应用目录
        define('APP_PATH', __DIR__ . '/../application/');
        define('ADMIN_NAME','admin');//后台目录，这里改了后台目录文件夹名称也要改
        define('NOW_TIME',$_SERVER['REQUEST_TIME']);
        defined('UPLOAD_PATH') or define('UPLOAD_PATH','./upload/'); //图片上传路径
        define('RUNTIME_PATH', '/../runtime/');
        define('BASE_URL','http://'.$_SERVER['HTTP_HOST']);
        define('BASE_URL1','http://'.$_SERVER['HTTP_HOST']);//支付宝不认宝塔的https
        define('UPLOAD_TYPE',1);//1：本地相对路径（网页），2：带域名全路径（小程序）

        $this->request->isAjax() ? define('IS_AJAX',true) : define('IS_AJAX',false);
        ($this->request->method() == 'GET') ? define('IS_GET',true) : define('IS_GET',false);
        ($this->request->method() == 'POST') ? define('IS_POST',true) : define('IS_POST',false);

    }

}
