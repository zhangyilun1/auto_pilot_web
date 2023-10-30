<?php
namespace app\yiwang\controller;
use app\common\model\Admin;

use think\Db;
use think\facade\Session;

/**
 * 后台首页控制器
 */
class Admins extends Base{

    //定义此页面的模型
    private $table;
    private $school_id;
    private $station_id;
    private $from;

    public function __construct()
    {
        parent::__construct();

        //实例化当前模型
        $this->table=new Admin();

        //输出页面标题
        $this->assign('top_title','管理员');


        $school_id=input('school_id',0);
        $this->school_id=$school_id;
        $this->assign('school_id',$school_id);

        $station_id=input('station_id',0);
        $this->station_id=$station_id;
        $this->assign('station_id',$station_id);

        $from=input('from','');
        $this->from=$from;
        $this->assign('from',$from);

        $group_where='1=1';
        switch ($from){
            case 'school':
                $group_where.=" and type=1";
                break;
            case 'station':
                $group_where.=" and type=2";
                break;
            default:
                $group_where.=" and type=0";
        }
        
        $groups=Db::name('auth_group')
            ->where($group_where)
            ->select();
        $this->assign('groups',$groups);

    }

    /**
     * 退出登陆
     */
    public function logout()
    {
        session_unset();
        session_destroy();
        Session::clear();
        if($this->admin_teacher_id || $this->admin_school_id || $this->admin_station_id){
            $this->redirect(url('Index/index',[],true,true,'index'));
        }
        $this->redirect(url('Common/login'));
        $this->success("退出成功",url('Common/login'));
    }

    /**
     * 管理员列表
     */
    public function index(){
        $keywords=input('keywords','');
        $where="1=1";
        if($keywords){
            $where.=" and (username like '%$keywords%' or mobile like '%$keywords%')";
            $this->assign('keywords',$keywords);
        }

        if($this->admin_id!=88){
            $where.=" and id<>$this->admin_id";
        }

        switch ($this->from){
            case 'school':
                $where.=" and school_id=$this->school_id and type=1";
                break;
            case 'station':
                $where.=" and station_id=$this->station_id and school_id=$this->school_id and type=2";
                break;
            default:
                $where.=" and station_id=0 and school_id=0 and type=0";
        }

        $page=input('page',1);
        $data=$this->table
            ->where($where)
            ->order('id asc')
            ->paginate(10,false,['query' => request()->param(),'page'=>$page]);

        $assign=array(
            'data'=>$data
        );

        $this->assign($assign);
        return $this->fetch();
    }

    public function handle(){
        $act=input('act','');
        $id=input('id',0);
        if(IS_AJAX){
            $data=input('post.');

            //表单验证
            if(($act=='add' || $act=='edit') ){

                if(empty($data['username'])){
                    ajax_return(-1,'请填写登录帐号');
                }

                if(empty($data['group_id'])){
                    ajax_return(-1,'请选择用户组');
                }

                if(!empty($data['password'])){
                    $salt=substr(uniqid(),-6);
                    $data['password']=md5($data['password'].md5($salt));
                    $data['salt']=$salt;
                }


                switch ($data['from']){
                    case 'school':
                        $data['type']=1;
                        if(empty($data['school_id'])){
                            ajax_return(-1,'未知学校');
                        }
                        break;
                    case 'station':
                        $data['type']=2;
                        if(empty($data['school_id'])){
                            ajax_return(-1,'未知学校');
                        }
                        if(empty($data['station_id'])){
                            ajax_return(-1,'未知站点');
                        }
                        break;
                    default:
                        $data['type']=0;
                        $data['school_id']=0;
                        $data['station_id']=0;
                }

                unset($data['from']);
            }

            if($act=='add'){
                if(empty($data['password'])){
                    ajax_return(-1,'请设置密码');
                }

                $data['create_time']=date('Y-m-d H:i:s');
            }

            if($act=='edit'){
                if(empty($data['password'])){
                    unset($data['password']);
                }
                $data['update_time']=date('Y-m-d H:i:s');
            }

            unset($data['act']);

            if($act=='add'){
                unset($data['id']);
                $result=$this->table->addData($data);
                $data['id']=$result;
                actLog("新增管理员『{$data['username']}』",$data);
            }elseif($act=='edit'){
                $result=$this->table->editData(['id'=>$id],$data);
                actLog("修改管理员『{$data['username']}』",$data);
            }elseif($act=='del'){
                $data=$this->table->where('id',$id)->find();
                actLog("删除管理员『{$data['username']}』",$data);
                $result=$this->table->deleteData($id);
            }elseif($act=='del_p'){
                $data=$this->table->where('id','in',$id)->select();
                $usernames=$this->table->where('id','in',$id)->column('username');
                $usernames=implode('、',$usernames);
                actLog("批量删除管理员『{$usernames}』",$data);
                $result=$this->table->deleteDatas($id);
            }else{
                $result=false;
            }
            if($result){
                ajaxReturn(['status'=>1,'msg'=>'操作成功']);
            }else{
                ajaxReturn(['status'=>-1,'msg'=>'操作失败']);
            }
        }
        if($act=='edit' && $id>0){
            $data=$this->table->where(array('id'=>$id))->find()->toArray();
            $data['group_id']=explode(',',$data['group_id']);
            //P($data);
            $this->assign('data',$data);
        }
        $pid=input('pid',0);
        $this->assign('pid',$pid);
        $this->assign('act',$act);
        return $this->fetch();
    }

    public function order_sort(){
        $data=input('post.');
        $result=$this->table->update($data);
        if ($result) {
            $this->ajaxReturn(['status'=>1,'msg'=>'排序成功']);
        }else{
            $this->ajaxReturn(['status'=>-1,'msg'=>'排序失败']);
        }
    }

    public function on_off(){
        $data=input('post.');
        $column=$data['column'];
        $result=$this->table
            ->where('id',$data['id'])
            ->update([$column=>$data['value']]);
        if ($result) {
            $data1=$this->table->where('id',$data['id'])->find();
            actLog("修改管理员状态『ID：{$data1['id']}，{$column}={$data['value']}』",$data1);
            ajax_return(1,'操作成功');
        }else{
            ajax_return(-1,'操作失败');
        }
    }

}
