<?php
namespace app\index\controller;
use app\common\controller\Commons;
use think\Db;
use think\facade\Cookie;
use think\facade\Log;
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


                $myCompanyName = Db::view('user_info')
                ->where('userID', $member['userID'])
                ->value('companyName');


                Log::info(" myCompanyName:  " . var_export($myCompanyName,true));
                if($myCompanyName != null){
                    $logoPath = '/static/index/img/' . $myCompanyName . '.png';
                }else {
                    $logoPath = '/static/index/img/logo.png';
                }
              
                $webInfo = 'AI巡检管控平台';
                Log::info(" logoPath:  " . $logoPath);
                // Log::info(" myCompanyName:  " . $myCompanyName['companyName']);
            }else{
                session('member_id',-1);
                session('member',[]);
            }
        }
        $this->is_login=$is_login;
        session('is_login',$this->is_login);

     
      


        // $seo=[
        //     'title'=>'AI巡检管控平台',
        //     'desc'=>'AI巡检管控平台',
        //     'keyword'=>'AI巡检管控平台'
        // ];


        $seo=[
            'title'=> $webInfo,
            'desc'=> $webInfo,
            'keyword'=> $webInfo,
            'logoPath' => $logoPath
        ];
        $this->seo=$seo;

        //$no_need=['login','getTaskList','getTaskDetail'];
        $no_need=['login','gettasklist','gettaskdetail'];

        Log::info(" ACTION_NAME :  " . $this->request->action()) ;
        if(!$this->is_login && !in_array(ACTION_NAME,$no_need)){
            Log::info(" need to login   ") ;
            $this->redirect(url('index/login'));
        }
        Log::info(" === not login ===  ");
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
        Log::info(" member:  " . var_export($member,true));
        $this->assign(compact('member','is_login','seo'));

    }

}
