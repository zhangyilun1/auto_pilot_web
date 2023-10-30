<?php
namespace app\common\model;
use app\yiwang\extend\Data;
use think\Db;

class Debris extends Base{

    //获取首页热销、新品广告图
    public function get_hot_new_ad(){
        $hot=$this->where(['open'=>1,'id'=>4])
            ->cache('hot_ad')
            ->field('id,name,title,photo,wap_photo as big_photo,"" as intro_photo,"hot" as type')
            ->find()->toArray();

        $new=$this->where(['open'=>1,'id'=>5])
            ->cache('new_ad')
            ->field('id,name,title,photo,wap_photo as big_photo,"" as intro_photo,"new" as type')
            ->find()->toArray();
        return ['hot'=>$hot,'new'=>$new];
    }

    //我的书课顶部广告图
    public function my_book_ad(){
        $ad=Db::name('debris')
            ->where(['open'=>1,'id'=>6])
            ->field('id,name,title as course_id,photo')
            ->find();
        return $ad;
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

	public function deleteData($id){
		$this->where(array('id'=>$id))->delete();
		return true;
	}

    public function deleteDatas($ids){
        $this->where('id','in',$ids)->delete();
        return true;
    }




}
