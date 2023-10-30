<?php
namespace app\common\model;
use think\facade\Cookie;
use think\Model;
use think\Db;

/**
 * 基础model
 */
class Base extends Model{

    public $m_id;
    public $mem;
    public $is_login;
    public $lg_device;

    public function initialize()
    {

        parent::initialize();
        //网站配置
        $this->m_id=(int)session('member_id');
        $this->mem=(array)session('member');
        $this->is_login=(int)session('is_login');
        $this->lg_device=session('login_device');
    }
    /**
     * 添加数据
     * @param  array $data  添加的数据
     * @return int          新增的数据id
     */
    public function addData($data){
        // 去除键值首尾的空格
        foreach ($data as $k => $v) {
            $data[$k]=trim($v);
        }
        $id=$this->insertGetId($data);
        return $id;
    }

    /**
     * 修改数据
     * @param   array   $map    where语句数组形式
     * @param   array   $data   数据
     * @return  boolean         操作是否成功
     */
    public function editData($map,$data){
        // 去除键值首位空格
        foreach ($data as $k => $v) {
            if(is_string($v)){
                $data[$k]=trim($v);
            }
        }
        $result=$this->where($map)->update($data);
        return $result;
    }

    /**
     * 删除数据
     * @param   array   $map    where语句数组形式
     * @return  boolean         操作是否成功
     */
    public function deleteData($map){
        if (empty($map)) {
            if(IS_AJAX){
                ajaxReturn(['status'=>-1,'msg'=>'where为空的危险操作']);
            }
            die('where为空的危险操作');
        }
        $result=$this->where($map)->delete();
        return $result;
    }

    /**
     * 数据排序
     * @param  array $data   数据源
     * @param  string $id    主键
     * @param  string $order 排序字段
     * @return boolean       操作是否成功
     */
    public function orderData($data,$id='id',$order='sort'){
        foreach ($data as $k => $v) {
            $v=empty($v) ? null : $v;
            $this->where(array($id=>$k))->update(array($order=>$v));
        }
        return true;
    }

    public function getTreeData($type='tree',$order='',$name='name',$child='id',$parent='pid',$where='1=1'){
        // 判断是否需要排序
        if(empty($order)){
            $data=$this->where($where)->select()->toArray();
        }else{
            $data=$this->where($where)->order($order)->select()->toArray();
        }
        // 获取树形或者结构数据
        $obj=new Data;
        if($type=='tree'){
            $data=$obj->tree($data,$name,$child,$parent);
        }elseif($type="level"){
            $data=$obj->channelLevel($data,0,'&nbsp;',$child);
        }
        return $data;
    }

}
