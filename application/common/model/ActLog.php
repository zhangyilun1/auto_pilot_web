<?php
namespace app\common\model;
use app\yiwang\extend\Data;

class ActLog extends Base{

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

	public function deleteData($id){
		$this->where(array('id'=>$id))->delete();
		return true;
	}

    public function deleteDatas($ids){
        $this->where('id','in',$ids)->delete();
        return true;
    }


}
