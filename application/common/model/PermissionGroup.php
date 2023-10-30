<?php
namespace app\common\model;
use think\Db;

class PermissionGroup extends Base{

    protected $table = 'PermissionGroup';

    public function permission(){
        return $this->hasOne('permission_list','permissionID','permissionID');
    }

}
