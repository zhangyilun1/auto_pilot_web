<?php
namespace app\common\model;
use app\yiwang\extend\Data;
use think\Db;
use think\facade\Cookie;

class Admin extends Base{


    public function addData($data){
        $group_ids=$data['group_id'];
        $data['group_id']=implode(',',$data['group_id']);
        // 去除键值首尾的空格
        foreach ($data as $k => $v) {
            $data[$k]=trim($v);
        }
        $this->startTrans();
        $id=$this->insertGetId($data);
        if(!$id){
            $this->rollback();
            return false;
        }
        if(!empty($group_ids)){
            $group=[];
            foreach($group_ids as $k=>$v){
                $group[]=[
                    'uid'=>$id,
                    'group_id'=>$v
                ];
            }
            $res=Db::name('auth_group_access')
                ->insertAll($group);
            if(!$res){
                $this->rollback();
                return false;
            }
        }
        $this->commit();
        return $id;
    }


    public function editData($where,$data){
        $group_ids=$data['group_id'];
        $data['group_id']=implode(',',$data['group_id']);
        // 去除键值首位空格
        foreach ($data as $k => $v) {
            if(is_string($v)){
                $data[$k]=trim($v);
            }
        }
        $this->startTrans();
        $result=$this->where($where)->update($data);
        if(!$result){
            $this->rollback();
            return false;
        }
        $res=Db::name('auth_group_access')
            ->where('uid',$data['id'])
            ->delete();
        if($res===false){
            $this->rollback();
            return false;
        }
        if(!empty($group_ids)){
            $group=[];
            foreach($group_ids as $k=>$v){
                $group[]=[
                    'uid'=>$data['id'],
                    'group_id'=>$v
                ];
            }
            $res=Db::name('auth_group_access')
                ->insertAll($group);
            if(!$res){
                $this->rollback();
                return false;
            }
        }
        $this->commit();
        return $result;
    }

    public function deleteData($id){
        $this->where('id',$id)->delete();
        $res=Db::name('auth_group_access')
            ->where('uid',$id)
            ->delete();
        return true;
    }

    public function deleteDatas($ids){
        $this->where('id','in',$ids)->delete();
        $res=Db::name('auth_group_access')
            ->where('uid','in',$ids)
            ->delete();
        return true;
    }


    public function login($username,$password){
        $admin=$this->where('username',$username)->find();
        if(!empty($admin)){
            if($admin['status']!=1){
                ajaxReturn(['status'=>-1,'msg'=>'禁止登录，请联系管理员']);
            }
            if(md5($password.md5($admin['salt']))==$admin['password']){
                $token=md5('YW'.$admin['id'].mt_rand(10000,99999).time());
                Cookie::set('ht_token',$token);
                $this->where('id', $admin['id'])->update([
                    'last_login_time' => date('Y-m-d H:i:s'),
                    'last_login_ip' => request()->ip(),
                    'login_times'=>$admin['login_times']+1,
                    'token'=>$token
                ]);
                unset($admin['password']);
                unset($admin['salt']);
                $admin['group_name']=admin_group($admin['id'],'name');
                session('admin_user',$admin);
                actLog('登录平台',$admin);
                return true;
            }
        }
        return false;
    }

}
