<?php
namespace app\common\model;
use think\Model;
use think\Db;
use app\yiwang\extend\Data;
use app\common\model\AuthGroupAccess;


/**
 * 权限规则model
 */
class AuthGroup extends Base{



	/**
	 * 传递主键id删除数据
	 * @param  array   $map  主键id
	 * @return boolean       操作是否成功
	 */
	public function deleteData($map){
		$result=$this->where($map)->delete();
		if ($result) {
			$group_map=array(
				'group_id'=>$map['id']
			);
			// 删除关联表中的组数据
            $AuthGroupAccess=new AuthGroupAccess;
			$result=$AuthGroupAccess->deleteData($group_map);
		}
		return true;
	}



}
