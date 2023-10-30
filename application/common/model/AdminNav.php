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
class AdminNav extends Base{

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
		$this->where(array('id'=>$id))->delete();
		return true;
	}

    /**
     * 批量删除数据
     * @param	array	$map	where语句数组形式
     * @return	boolean			操作是否成功
     */
    public function deleteDatas($ids){
        $count=$this
            ->where('pid','in',$ids)
            ->count();
        if($count!=0){
            return false;
        }
        $this->where('id','in',$ids)->delete();
        return true;
    }

	/**
	 * 获取全部菜单
	 * @param  string $type tree获取树形结构 level获取层级结构
	 * @return array       	结构数据
	 */
	public function getNavTreeData($type='tree',$order='',$name='name',$child='',$parent=''){
		// 判断是否需要排序
		if(empty($order)){
			$data=$this->where('open',1)->select();
		}else{
			$data=$this->order($order)->where('open',1)->select()->toArray();
			//P($data);
		}
		//P($data);
		// 获取树形或者结构数据
        $obj=new Data;
        if($type=='tree'){
			$data=$obj->tree($data,$name,'id','pid');
			//P($data);
		}elseif($type="level"){
			$data=$obj->channelLevel($data,0,'&nbsp;','id');
			//P($data);
			// 显示有权限的菜单
			$auth=new Auth();
			//P(session('admin_user'));
            foreach ($data as $k => $v) {
                //P($v);
                if ($auth->check($v['mca'],session('admin_user.id'))) {
                    //P($v);
                    foreach ($v['_data'] as $m => $n) {
                        if ($auth->check($n['mca'],session('admin_user.id'))) {
                            foreach($n['_data'] as $mm=>$nn){
                                if(!$auth->check($nn['mca'],session('admin_user.id'))){
                                    unset($data[$k]['_data'][$m]['_data'][$mm]);
                                }
                            }
                        }else{
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
