<?php
namespace app\common\model;
use app\common\model\Admin;
use think\Model;
use think\Db;
/**
 * 权限规则model
 */
class AuthGroupAccess extends Base{

	/**
	 * 根据group_id获取全部用户id
	 * @param  int $group_id 用户组id
	 * @return array         用户数组
	 */
	public function getUidsByGroupId($group_id){
		$user_ids=$this
			->where(array('group_id'=>$group_id))
			->column('uid');
		return $user_ids;
	}

	/**
	 * 获取管理员权限列表
	 */
	public function getAllData(){
	    /**
		$data=$this
			->field('u.id,u.username,u.email,u.register_time,u.last_login_time,aga.group_id,ag.title')
			->alias('aga')
			->join('__USERS__ u ',' aga.uid=u.id','RIGHT')
			->join('__AUTH_GROUP__ ag ',' aga.group_id=ag.id','LEFT')
			->select();
		P($data);
		// 获取第一条数据
		$first=$data[0];
		$first['title']=array();
		$user_data[$first['id']]=$first;
		// 组合数组
		foreach ($data as $k => $v) {
			foreach ($user_data as $m => $n) {
				$uids=array_map(function($a){return $a['id'];}, $user_data);
				//P($uids);
				if (!in_array($v['id'], $uids)) {
					$v['title']=array();
					$user_data[$v['id']]=$v;
				}
			}
		}
		//P($user_data);
		// 组合管理员title数组
		foreach ($user_data as $k => $v) {
			foreach ($data as $m => $n) {
				if ($n['id']==$k) {
					$user_data[$k]['title'][]=$n['title'];
				}
			}
			$user_data[$k]['title']=implode('、', $user_data[$k]['title']);
		}
		// 管理组title数组用顿号连接
         **/
	    $users=new Admin;
        $data=$users->field('id,username,register_time,last_login_time,last_login_ip')->order('id asc')->select();

        foreach($data as $k=>&$v){
	        $group_ids=$this->where(array('uid'=>$v['id']))->column('group_id');
	        $group_ids=implode(',',$group_ids);
	        $group=new AuthGroup;
	        $groups=$group->where('id','in',$group_ids)->column('title');
            $title=implode('、',$groups);
            $v['title']=$title;
        }
        //P($data);
		return $data;

	}


}
