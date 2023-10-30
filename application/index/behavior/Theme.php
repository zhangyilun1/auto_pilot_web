<?php

namespace app\index\behavior;

use think\Db;
use think\facade\View;

class Theme
{

    public function run()
    {
        if(!isMobile()){
            View::config('view_path', '../template/index/');
        }else{
            View::config('view_path', '../template/index/');
        }

    }
}