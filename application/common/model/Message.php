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
class Message extends Base{

    //未读消息数量
    public function no_read_count($member_id){
        $my_message_ids=Db::name('message')
            ->where(['open'=>1])
            ->where("(member_id>0 and member_id={$member_id} and member_type=1) or (member_id=0 and member_ids like '%:\"{$member_id}\";%' and member_type=2 and send_sys=1) or (member_type=1 and member_id=0 and send_sys=1)")
            ->order('id desc')
            ->column('id');

        $read_ids=Db::name('message_read')
            ->where('member_id',$member_id)
            ->column('message_id');

        $no_read_ids=array_diff($my_message_ids,$read_ids);
        return count($no_read_ids);
    }


    //我的消息
    public function my_message($member_id,$pg=1){
        $type=input('type',0);
        $where='open=1';
        if($type){
            $where.=" and type=$type";
        }
        $page=input('page',1);
        $rows=10;
        if(empty($pg)){
            $rows=99999999;
        }

        $data=Db::name('message')
            ->field('id,id as message_id,title,content,create_time,type')
            ->where($where)
            ->where("(member_id>0 and member_id={$member_id} and member_type=1) or (member_id=0 and member_ids like '%:\"{$member_id}\";%' and member_type=2 and send_sys=1) or (member_type=1 and member_id=0 and send_sys=1)")
            ->order('id desc')
            ->paginate($rows,true,['query' => request()->param(),'page'=>$page])
            ->toArray()['data'];

        $read_ids=Db::name('message_read')
            ->where('member_id',$member_id)
            ->column('message_id');

        foreach($data as $k=>$v){
            $data[$k]['content']=strip_tags($v['content']);
            $data[$k]['str_time']=get_last_time($v['create_time']);
            $is_read=0;
            if(in_array($v['id'],$read_ids)){
                $is_read=1;
            }
            $data[$k]['is_read']=$is_read;
        }
        return $data;
    }

    //一件已读
    public function read_all(){
        $where='open=1';
        $type=input('type',0);
        if($type){
            $where.=" and type=$type";
        }

        $my_message_ids=Db::name('message')
            ->field('id,id as message_id,title,content,create_time,type')
            ->where($where)
            ->where("(member_id>0 and member_id={$this->m_id} and member_type=1) or (member_id=0 and member_ids like '%:\"{$this->m_id}\";%' and member_type=2) or (member_type=1 and member_id=0)")
            ->order('id desc')
            ->column('id');

        $read_ids=Db::name('message_read')
            ->where('member_id',$this->m_id)
            ->column('message_id');
        $arr=[];
        foreach($my_message_ids as $k=>$v){
            if(!in_array($v,$read_ids)){
                $arr[]=[
                    'message_id'=>$v,
                    'member_id'=>$this->m_id
                ];
            }
        }
        if(!empty($arr)){
            Db::name('message_read')
                ->insertAll($arr);
        }
        ajax_return(1,'设置成功');
    }

    public function message_detail(){
        $message_id=input('message_id',0);
        $member_id=$this->m_id;
        $message=Db::name('message')
            ->field('id,id as message_id,title,content,create_time,type')
            ->where("(member_id>0 and member_id={$member_id} and member_type=1) or (member_id=0 and member_ids like '%:\"{$member_id}\";%' and member_type=2) or (member_type=1 and member_id=0)")
            ->where('id',$message_id)
            ->find();
        if(!empty($message)){
            $has_read=Db::name('message_read')
                ->where(['member_id'=>$member_id,'message_id'=>$message_id])
                ->find();
            if(empty($has_read)){
                Db::name('message_read')
                    ->insert([
                        'member_id'=>$member_id,
                        'message_id'=>$message_id
                    ]);
            }
        }
        return $message;
    }







	/**
	 * 删除数据
	 * @param	array	$map	where语句数组形式
	 * @return	boolean			操作是否成功
	 */
	public function deleteData($id){

		$this->where(array('id'=>$id))->delete();

		return true;
	}

    /**
     * 批量删除数据
     * @param	array	$map	where语句数组形式
     * @return	boolean			操作是否成功
     */
    public function deleteDatas($ids){

        $this->where('id','in',$ids)->delete();

        return true;
    }

    //新增数据
    public function addData($data){

        foreach ($data as $k => $v) {
            $data[$k]=trim($v);
        }

        $id=$this->insertGetId($data);
        return $id;
    }

    //新增数据
    public function editData($map,$data){

        foreach ($data as $k => $v) {
            $data[$k]=trim($v);
        }

        $result=$this->where($map)->update($data);

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
