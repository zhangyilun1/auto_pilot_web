<?php
namespace app\common\model;
use think\Model;
use think\Db;

/**
 * 权限规则model
 */
class AuthRule extends Base{

	/**
	 * 删除数据
	 * @param	array	$map	where语句数组形式
	 * @return	boolean			操作是否成功
	 */
	public function deleteData($id){
		$count=$this
			->where(array('pid'=>$id))
			->count();
		if($count!=0){
			return false;
		}
		$result=$this->where(array('id'=>$id))->delete();
		return $result;
	}




}
