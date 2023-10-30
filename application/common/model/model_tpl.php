<?php
namespace app\common\model;
use app\_MODULE_NAME_\extend\Data;

class _MODEL_NAME_ extends Base{


    public function addData($data){
        // 去除键值首尾的空格
        foreach ($data as $k => $v) {
            $data[$k]=trim($v);
        }
        $id=$this->insertGetId($data);
        return $id;
    }


    public function editData($where,$data){
        // 去除键值首位空格
        foreach ($data as $k => $v) {
            if(is_string($v)){
                $data[$k]=trim($v);
            }
        }
        $result=$this->where($where)->update($data);
        return $result;
    }

	public function deleteData($id,$delete=0){
        if($delete){
            $this->where('id',$id)->delete();
        }else{
            $this->where('id',$id)->update([
                'delete_time'=>date('Y-m-d H:i:s'),
                'is_delete'=>1,
                'open'=>0
            ]);
        }

		return true;
	}

    public function deleteDatas($ids,$delete=0){
        if($delete){
            $this->where('id','in',$ids)->delete();
        }else{
            $this->where('id','in',$ids)->update([
                'delete_time'=>date('Y-m-d H:i:s'),
                'is_delete'=>1,
                'open'=>0
            ]);
        }

        return true;
    }







}
