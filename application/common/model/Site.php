<?php
namespace app\common\model;
use app\yiwang\extend\Auth;
use think\Model;
use think\Db;
use app\common\model\Base;
use app\yiwang\extend\Data;
/**
 * 菜单操作model
 */
class Site extends Base{

	/**
	 * 删除数据
	 * @param	array	$map	where语句数组形式
	 * @return	boolean			操作是否成功
	 */
	public function deleteData($id){
		$old_data=$this->where(array('id'=>$id))->find();
		$this->where(array('id'=>$id))->delete();
        //@ chmod('./upload/',0755);
        @ unlink('.'.$old_data['site_logo']);
        @ unlink('.'.$old_data['title_logo']);
        @ unlink('.'.$old_data['water_pic']);
        @ unlink('.'.$old_data['wechat_qr']);
        return true;
	}

    /**
     * 批量删除数据
     * @param	array	$map	where语句数组形式
     * @return	boolean			操作是否成功
     */
    public function deleteDatas($ids){
        $old_data=$this->where('id','in',$ids)->select();
        $this->where('id','in',$ids)->delete();
        foreach($old_data as $v){
            @ unlink('.'.$v['site_logo']);
            @ unlink('.'.$v['title_logo']);
            @ unlink('.'.$v['water_pic']);
            @ unlink('.'.$v['wechat_qr']);

        }
        return true;
    }

    //新增数据
    public function addData($data){

        foreach ($data as $k => $v) {
            $data[$k]=trim($v);
        }
        $data['token']=md5(md5(time().$data['title'].$data['url'].rand(1,9999)).time().$data['title'].$data['url'].rand(1,9999));
        $id=$this->insertGetId($data);
        return $id;
    }

    //新增数据
    public function editData($map,$data){

        foreach ($data as $k => $v) {
            $data[$k]=trim($v);
        }
        unset($data['token']);
        $old_data=$this->where($map)->find();
        $result=$this->update($data);
        //P($this->getLastSql());
        if(!empty($data['water_pic'])){
            @ chmod('./upload/',0755);
            @ unlink('.'.$old_data['water_pic']);
        }
        if(!empty($data['site_logo'])){
            @ chmod('./upload/',0755);
            @ unlink('.'.$old_data['site_logo']);
        }
        if(!empty($data['title_logo'])){
            @ chmod('./upload/',0755);
            @ unlink('.'.$old_data['title_logo']);
        }
        if(!empty($data['wechat_qr'])){
            @ chmod('./upload/',0755);
            @ unlink('.'.$old_data['wechat_qr']);
        }
        return $result;
    }

	/**
	 * 获取全部菜单
	 * @param  string $type tree获取树形结构 level获取层级结构
	 * @return array       	结构数据
	 */
	public function getTreeData($type='tree',$order='',$name='',$child='',$parent=''){
		// 判断是否需要排序
		if(empty($order)){
			$data=$this->select();
		}else{
			$data=$this->order('sort is null,'.$order)->select();
		}
		//P($data);
		// 获取树形或者结构数据
        $obj=new Data;
        if($type=='tree'){
			$data=$obj->tree($data,'name','id','pid');
		}elseif($type="level"){
			$data=$obj->channelLevel($data,0,'&nbsp;','id');
			// 显示有权限的菜单
			$auth=new Auth();
			foreach ($data as $k => $v) {
				if ($auth->check($v['mca'],session('admin_user.id'))) {
					foreach ($v['_data'] as $m => $n) {
						if(!$auth->check($n['mca'],session('admin_user.id'))){
							unset($data[$k]['_data'][$m]);
						}
					}
				}else{
					// 删除无权限的菜单
					unset($data[$k]);
				}
			}
		}
		// p($data);die;
		return $data;
	}


}
