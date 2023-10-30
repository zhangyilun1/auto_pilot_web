<?php
namespace app\index\controller;
use app\common\controller\Commons;
use think\Db;
use think\facade\Cookie;

class Base extends Commons
{
    protected $is_login=0;
    protected $member;
    protected $member_id;
    protected $seo;
    protected $target_url;

    public function __construct(){
        parent::__construct();

        $member_id=session('member_id');
        $is_login=0;
        if($member_id<0){
            $member_id=Cookie::get('member_id');
        }
        if($member_id>=0){
            $member=Db::table('UserList')
                ->where(['userID'=>$member_id])
                ->find();
            if(!empty($member)){
                $this->member=$member;
                $this->member_id=$member_id;
                session('member_id',$member_id);
                session('member',$member);
                $is_login=1;
            }else{
                session('member_id',-1);
                session('member',[]);
            }
        }
        $this->is_login=$is_login;
        session('is_login',$this->is_login);

        $seo=[
            'title'=>'AI巡检管控平台',
            'desc'=>'AI巡检管控平台',
            'keyword'=>'AI巡检管控平台'
        ];
        $this->seo=$seo;

        $no_need=['login'];
        if(!$this->is_login && !in_array(ACTION_NAME,$no_need)){
            $this->redirect(url('index/login'));
        }

        if($this->is_login){
            $permission_group_id=$member['permissionGroupID'];
            $permission_id=Db::table('PermissionGroup')
                ->where('groupID',$permission_group_id)
                ->value('permissionID');
            $my_permission=Db::table('permissionLIst')
                ->where('permissionID',$permission_id)
                ->find();
            //P($my_permission);
            $this->my_permission=$my_permission;
            $this->assign(compact('my_permission'));
        }

        $this->assign(compact('member','is_login','seo'));

    }

}
