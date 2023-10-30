<?php

namespace app\index\controller;

use app\common\model\Message;
use think\Db;
use think\facade\Cookie;
use think\facade\Log;
// use think\Log;
use think\App;

require_once("tcp.php");
class Index extends Base
{

    public function __construct()
    {
        parent::__construct();
    }


    public function takeoffLink(){

        if(IS_AJAX && IS_POST){
            $datas=input('post.');
            Log::info("takeoff ");
            Log::info("data in takeoffLink : " . var_export($datas,true));
            $controller =  "takeoffLink";

            foreach($datas as $data) {
                $snCode = Db::view('submission_mission')
                ->where('submissionID',$data)
                ->value('snCode');
            //     $networkType = Db::table('networkTypes')
            //     ->where('isValid',1)
            //     ->value('networkType');
            //     $timestamp = time();
            //     $submissionType = Db::view('submission_mission')
            //     ->where('submissionID',$data)
            //     ->value('missionID');
                
            //     //chinese can't display
            //     Log::info("networkType: " . $networkType);
            //     Log::info("submissionType: " . $submissionType);
            //     Log::info("time: " . $timestamp);

            //     $linePoint = Db::view('submission_mission')
            //     ->where('submissionID',$data)
            //     ->column('snCode');

            //     $rtk = 1;
            //     $flight = 1;
            //     $record = 1;
            //     $imgwidth = 1;
            //     $imgheight = 1;
            //     // Log::info("snCode: " . var_export($snCode,true));
            
                $all_data = array(
                    "submissionID" => $data,
                    "snCode" => $snCode,
                );

            //     // $all_data = json_encode($all_data,JSON_UNESCAPED_SLASHES);
                Log::info("take off : " . var_export($all_data,true));
                
                $len = strLen(json_encode($all_data));

                $combinedData = array(
                    "controller" => $controller,
                    "len" => $len,
                    "data" =>  $all_data,
                );
                $combinedData = json_encode($combinedData,JSON_UNESCAPED_SLASHES);
                Log::info("combinedData: " . var_export($combinedData,true));
                
                $socket = new tcp();
                $socket->socketSend($combinedData);

                
            }
        //return $this->fetch();
        }
    //}
}

    public function getHeartbeatInfo()
    {
        // 检查是否是 AJAX 请求和 POST 请求
        if ($this->request->isAjax() && $this->request->isPost()) {
            try {
            // 创建 TCP 套接字对象
                $socket = new tcp();

                // 接收数据
                $message = $socket->socketReceive();

                if ($message !== false) {
                    // 记录接收到的消息到日志
                    Log::info("Received message: " . $message);
                    // 返回接收到的数据给前端
                    return json(['data' => $message]);
                } else {
                    // 处理接收失败的情况
                    //return json(['error' => 'Failed to receive data']);
                    return ;
                }
            } catch (Exception $e) {
                //return json(['error' => $e->getMessage()]);
                return ;
            }
        }

        return $this->fetch();
    }


    public function index(){
        if($this->my_permission['findTask']<=0){
            return $this->fetch();
        }
        $task_name=input('task_name','');
        $task_where='1=1';
        if($task_name){
            $task_where.=" and submissionName like '%{$task_name}%'";
        }
        $tasks=Db::view('submission_mission')
            ->where($task_where)
            ->order('submissionID desc')
            ->select();

        $submissionID=input('submissionID',-1);
        if($submissionID>=0){
            $tower_ids=Db::table('submissiontowerList')
                ->where('submissionID',$submissionID)
                ->column('towerID');
            if(empty($tower_ids)){
                $tower_ids=[-1];
            }
            $towers=Db::table('towerList')
                ->where('towerID','in',$tower_ids)
                ->order('towerNumber asc')
                ->select();

            $related_drone_ids=Db::table('submissionList')
                ->where('submissionID',$submissionID)
                ->column('droneID');

            if(empty($related_drone_ids)){
                    $related_drone_ids=[-1];
            }

            $related_drones=Db::table('drone')
                ->where('droneID','in',$tower_ids)
                ->order('droneID desc')
                ->select();
        }
        //P($tasks);
        $this->assign(compact('tasks','task_name','towers','submissionID','related_drones'));

        $drone_sncode=input('snCode','');

        $drone_where='1=1';

        if($drone_sncode){
            $drone_where.=" and snCode like '%{$drone_sncode}%'";
        }

        $all_drones = Db::table('drone')
        ->where($drone_where)
        ->order('droneID desc')
        ->select();

        $droneID=input('droneID',-1);

        // if(IS_AJAX && IS_POST){
        //     $datas=input('post.');
        //     Log::info("index controller send task info to drone ");
        //     Log::info("data in ajax : " . var_export($datas,true));
        //     $controller =  "index";
        //     foreach($datas as $data) {

        //         $snCode = Db::view('submission_mission')
        //         ->where('submissionID',$data['submissionID'])
        //         ->value('snCode');

        //         $networkType = Db::table('networkTypes')
        //         ->where('isValid',1)
        //         ->value('networkType');

        //         $timestamp = time();


        //         $submissionType = Db::view('submission_mission')
        //         ->where('submissionID',$data['submissionID'])
        //         ->value('missionID');
                
        //         //chinese can't display
        //         Log::info("networkType: " . $networkType);
        //         Log::info("submissionType: " . $submissionType);
        //         Log::info("time: " . $timestamp);

        //         $rtk = 1;
        //         $record = 1;
        //         $imgwidth = 1;
        //         $imgheight = 1;
        //         // $flight = $data['flight'];

        //         $policyID = Db::view('submission_mission')
        //         ->where('submissionID',$data['submissionID'])
        //         ->value('policyID');

        //         if($policyID === 0){
        //             $return = 0;
        //         } else if ($policyID === 1){
        //             $returnAltitude = Db::view('submission_mission')
        //                 ->where('submissionID',$data['submissionID'])
        //                 ->value('returnAltitude');
        //             $return = $returnAltitude;
        //         }else if($policyID === 2){
        //             $return = 255;
        //         }
        //         Log::info("return : " .$return);

        //         $all_data = array(
        //             "submissionID" => $data['submissionID'],
        //             "snCode" => $snCode,
        //             // "networkType" => $networkType,
        //             "missionType" => $submissionType,
        //             "rtk" => $rtk,
        //             "flight" => $data['flight'],
        //             "timestamp" => $timestamp,
        //             "record" => $record,
        //             "imgwidth" => $imgwidth,
        //             "imgheight" => $imgheight,
        //             "return" => $return,
        //         );

        //         // $all_data = json_encode($all_data,JSON_UNESCAPED_SLASHES);
        //         // Log::info("combinedData: " . var_export($all_data,true));
                
        //         $len = strLen(json_encode($all_data));

        //         $combinedData = array(
        //             "controller" => $controller,
        //             "len" => $len,
        //             "data" =>  $all_data,
        //         );
        //         $combinedData = json_encode($combinedData,JSON_UNESCAPED_SLASHES);
        //         Log::info("combinedData: " . var_export($combinedData,true));
                
        //         $socket = new tcp();
        //         $socket->socketSend($combinedData);

                
        //     }



        // }
       
        //if($droneID>=0){
            // $tower_ids=Db::table('submissiontowerList')
            //     ->where('droneID',$droneID)
            //     ->column('towerID');

            // if(empty($tower_ids)){
            //     $tower_ids=[-1];
            // }
            // $towers=Db::table('towerList')
            //     ->where('towerID','in',$tower_ids)
            //     ->order('towerNumber asc')
            //     ->select();

        //}

        $this->assign(compact('all_drones','drone_sncode','droneID'));

        return $this->fetch();
    }
 
    public function uploadTask(){

        if(IS_AJAX && IS_POST){
            $datas=input('post.');
            Log::info("uploadTask controller send task info to drone ");
            Log::info("data in ajax : " . var_export($datas,true));
            $controller =  "index";
            foreach($datas as $data) {

                $snCode = Db::view('submission_mission')
                ->where('submissionID',$data['submissionID'])
                ->value('snCode');

                $networkType = Db::table('networkTypes')
                ->where('isValid',1)
                ->value('networkType');

                $timestamp = time();


                $submissionType = Db::view('submission_mission')
                ->where('submissionID',$data['submissionID'])
                ->value('missionID');
                
                //chinese can't display
                Log::info("networkType: " . $networkType);
                Log::info("submissionType: " . $submissionType);
                Log::info("time: " . $timestamp);

                $rtk = 1;
                $record = 1;
                $imgwidth = 1;
                $imgheight = 1;
                // $flight = $data['flight'];

                $policyID = Db::view('submission_mission')
                ->where('submissionID',$data['submissionID'])
                ->value('policyID');
                Log::info("policyID : " . $policyID);
                if($policyID == 0){ 
                    //return from origin line  
                    $return = 255;
                } else if ($policyID == 1){
                    //return need altitude
                    $returnAltitude = Db::view('submission_mission')
                        ->where('submissionID',$data['submissionID'])
                        ->value('returnAltitude');
                    $return = (double)$returnAltitude;
                }else if($policyID == 2){
                    //return from other point  
                    $return = 254;
                    $landingLongtitude = Db::view('submissionList')
                    ->where('submissionID',$data['submissionID'])
                    ->value('landingLongtitude');
                    $landingLongtitude = (double)$landingLongtitude;

                    $landingLatitude = Db::view('submissionList')
                    ->where('submissionID',$data['submissionID'])
                    ->value('landingLatitude');
                    $landingLatitude = (double)$landingLatitude;

                    $landingAltitude = Db::view('submissionList')
                    ->where('submissionID',$data['submissionID'])
                    ->value('landingAltitude');
                    $landingAltitude = (double)$landingAltitude;
                }
                Log::info("return : " . $return);

                $all_data = array(
                    "submissionID" => $data['submissionID'],
                    "snCode" => $snCode,
                    "rtk" => $rtk,
                    "missionType" => $submissionType,
                    "flight" => $data['flight'],
                    "timestamp" => $timestamp,
                    "record" => $record,
                    "imgwidth" => $imgwidth,
                    "imgheight" => $imgheight,
                    "return" => $return,
                    "landingLongtitude" => $landingLongtitude,
                    "landingLatitude" =>  $landingLatitude,
                    "landingAltitude" => $landingAltitude,
                );

                // $all_data = json_encode($all_data,JSON_UNESCAPED_SLASHES);
                Log::info("all_data: " . var_export($all_data,true));
                
                $len = strLen(json_encode($all_data));

                $combinedData = array(
                    "controller" => $controller,
                    "len" => $len,
                    "data" =>  $all_data,
                );
                $combinedData = json_encode($combinedData,JSON_UNESCAPED_SLASHES);
                Log::info("combinedData: " . var_export($combinedData,true));
                
                $socket = new tcp();
                $socket->socketSend($combinedData);

            
                
            }


            return "good";
        }
    }
    public function del_task(){
        if(IS_AJAX && IS_POST){
            if($this->my_permission['deleteTask']<=0){
                ajax_return(-1,'无权操作');
            }
            $id=input('id',-1);
            if($id==0){
                ajax_return(-1,'不可删除');
            }
            $has_data=Db::table('submissionList')
                ->where('submissionID',$id)
                ->find();
            if(empty($has_data)){
                ajax_return(-1,'未知数据');
            }
            $res=Db::table('submissionList')
                ->where('submissionID',$id)
                ->delete();
            if($res!==false){
                Db::table('submissiontowerList')
                    ->where('submissionID',$id)
                    ->delete();
                ajax_return(1,'已删除');
            }
            ajax_return(-1,'删除失败');
        }
    }

    public function del_line(){
        if(IS_AJAX && IS_POST){
            if($this->my_permission['deleteTask']<=0){
                ajax_return(-1,'无权操作');
            }
            $id=input('id',-1);
            if($id==0){
                ajax_return(-1,'不可删除');
            }
            $has_data=Db::table('lineList')
                ->where('lineID',$id)
                ->find();
            if(empty($has_data)){
                ajax_return(-1,'未知数据');
            }
            $res=Db::table('towerList')
                ->where('lineID',$id)
                ->delete();
            
            // $submission=Db::table('submissiontowerList')
            //         ->where('lineID',$id)
            //         ->column('towerID');
            // Log::info("submission : " .var_export($submission,true));

            Log::info("res : " .var_export($res,true));
            if($res!==false){
                Log::info("delete not false : " );
                $deleteLine =Db::table('lineList')
                ->where('lineID',$id)
                ->delete();
                if($deleteLine!==false){
                    ajax_return(1,'已删除');
                }
            }
            ajax_return(-1,'删除失败');
        }
    }


    public function add_task(){
        if($this->my_permission['findTask']<=0){
            return $this->fetch();
        }
        if(IS_AJAX && IS_POST){
            // print("add task");
            $data=input('post.');
            Log::info("data in add_task : " . var_export($data,true));
            //P($data);
          
         
            // Log::info("data in ajax2 : " . var_export($data,true));
            if(empty($data['submissionName'])){
                ajax_return(-1,'请填写权限名称');
            }
            Db::startTrans();
            $id=$data['submissionID'];
            unset($data['submissionID']);
            $data_tower_ids=$data['towerID'];
            Log::info("data_tower_ids : " . var_export($data_tower_ids,true));

            $data_height = $data['height'];
            Log::info("data_height : " . var_export($data_height,true));
            $data_mode = $data['mode'];
            Log::info("data_mode : " . var_export($data_mode,true));


            unset($data['towerID']);
            $data['taskID']=-1;
            if($data['snCode']){
                $droneID = Db::table('drone')
                ->where('snCode',$data['snCode'])
                ->value('droneID');
                $data['droneID']= $droneID;
            }
            else {
                $data['droneID']=null;
            }

            // if($data['policyID'] === 1){
            //     $data['returnAltitude'] = $data['additionalInput'];
            // }
            // else if ($data['policyID'] === 2){
            //     $data['RTK'] = $data['additionalInput'];
            // }

            unset($data['snCode']);
            Log::info("data in ajax3 : " . var_export($data,true));
            if($id>=0){
                Log::info(" id > 0 : ".$id );
                if($this->my_permission['modifyTask']<=0){
                    ajax_return(-1,'无权操作');
                }
              
                $has_data=Db::table('submissionList')
                    ->where('submissionID',$id)
                    ->find();
                // $has_data['droneID']='';
                
                // Log::info("policyID : " . $data['policyID']);
              
                Log::info("has_data 111 : " .var_export($has_data, true));
                
                if($data['policyID'] === '1'){
                    Log::info("policyID IS 1  ");
                    Log::info("returnAltitude  : " . $has_data['returnAltitude']);
                    Log::info("additionalInput  : " . $data['additionalInput']);
                    $data['landingLongtitude'] = null;
                    $data['landingAltitude'] = null;
                    $data['landingLatitude'] = null;
                    $data['returnAltitude'] = $data['additionalInput'];
                }
                else if ($data['policyID'] === '2'){
                    Log::info("policyID IS 2  ");
                    $data['returnAltitude'] = null;
            
                } else {
                    $data['returnAltitude'] = null;
                    $data['landingLongtitude'] = null;
                    $data['landingAltitude'] = null;
                    $data['landingLatitude'] = null;
                }

                Log::info(" has_data 222 : " . var_export($has_data,true));
                Log::info(" data in controller 1: " . var_export($data, true));

                if(empty($has_data)){
                    ajax_return(-1,'未知数据');
                }

                // $data['returnAltitude'] = $has_data['returnAltitude'];
                // $returnAltitude =  $data['returnAltitude'];
                // $data['RTK'] =   $has_data['RTK'];
                // $RTK =  $data['returnAltitude'];
                // $data['returnAltitude'] =   $data['additionalInput'];
              
                unset($data['additionalInput']);
               
                unset($data['mode']);
                unset($data['height']);
                Log::info(" data in controller 2 : " . var_export($data,true));
                $res=Db::table('submissionList')
                    ->where('submissionID',$id)
                    ->update($data);

              
                // Log::info(" res: " .  $res);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'修改失败');
                }
                $submissionID=$id;
                Db::table('submissiontowerList')
                    ->where(['submissionID'=>$submissionID])
                    ->delete();

                
                $arr=[];
                foreach($data_tower_ids as $k=>$towerID){
                    $tower=Db::table('towerList')
                        ->where('towerId',$towerID)
                        ->find();
                    $towerNumber = $tower['towerNumber'];
                    Log::info("tower : " . var_export($tower,true));  
                    Log::info("tower data in controller : " . var_export($data,true)); 
                    Log::info("k : " . var_export($k,true)); 
                    if ($k === 0) {
                        $mode = null;
                        $height = null;
                    } else {
                        Log::info(" data_height count : " . count($data_height));
                        Log::info(" data_mode count : " . count($data_mode));
                        Log::info(" sequence : " . $k); 
                        if($data_height[$k - 1] === '')
                        {
                            $height = null;
                            Log::info("==== height ====: " . $data_height[$k - 1]);
                        } else {
                            $height = $data_height[$k - 1];
                            Log::info("... height ... : " . $data_height[$k - 1]);
                        }
                        if($data_mode[$k - 1] === 1)
                        {
                            if(($towerNumber - $prevTowerNumber) === 1)
                            {
                                $mode = null;
                            } else {
                                $mode = $data_mode[$k - 1];
                            }
                        }else {
                            $mode = $data_mode[$k - 1];
                        }
                        Log::info(" data_mode : " . $mode);
                        Log::info(" data_height: " . $height);
                        $prevTowerNumber = $towerNumber;
                    }
                    $arr[]=[
                        'submissionID'=>$submissionID,
                        'towerID'=>$towerID,
                        'createdTime'=>date('Y-m-d'),
                        'towerNumber'=>$k + 1,
                        'createManID'=>$this->member_id,
                        'mode'=> $mode,
                        'height'=> $height,
                        
                    ];
                    Log::info("arr in add for submissiontowerList : " . var_export($arr,true));  
                }
                if(!empty($arr)){
                    $res=Db::table('submissiontowerList')
                        ->insertAll($arr);
                    Log::info(" $res : " . $res);
                    if(!$res){
                        Db::rollback();
                        ajax_return(-1,'修改失败');
                    }
                }
                Db::commit();
                ajax_return(1,'修改成功');
            }else{
                Log::info("id < 0 : ".$id );
                Log::info("create task  : ".$id );
                if($this->my_permission['addTask']<=0){
                    ajax_return(-1,'无权操作');
                }
                $data['createdTime']=date('Y-m-d H:i:s');
                $data['createManID']=$this->member_id;
                $data['droneID']=null;
                if($data['policyID'] === '0'){
                    $data['returnAltitude'] = null;
                    $data['landingLongtitude'] = null;
                    $data['landingLatitude']= null;
                    $data['landingAltitude']= null;
                }else if($data['policyID'] === '1'){
                    $data['returnAltitude'] = $data['additionalInput'];
                    $returnAltitude =  $data['returnAltitude'];
                    $data['landingLongtitude'] = null;
                    $data['landingLatitude']= null;
                    $data['landingAltitude']= null;
                }
                else if ($data['policyID'] === '2'){
                    $data['returnAltitude'] = null;
                }
                unset($data['additionalInput']);
                Log::info(" insert submissionList : " . var_export($data,true));
                unset($data['mode']);
                unset($data['height']);
                $res=Db::table('submissionList')
                    ->insertGetId($data);
                if(!$res){
                    Db::rollback();
                    ajax_return(-1,'添加失败');
                }
                Log::info(" res : " . var_export($res,true));
                $submissionID=$res;
                $arr=[];

                $prevTowerNumber = -1;
                foreach($data_tower_ids as $k=>$towerID){
                    $tower=Db::table('towerList')
                        ->where('towerId',$towerID)
                        ->find();
                    
                    $towerNumber = $tower['towerNumber'];
                    Log::info("towerNumber : " . $towerNumber);
                    Log::info("prevTowerNumber : " . $prevTowerNumber);
                    if ($k === 0) {
                        $mode = null;
                        $height = null;
                    } else {
                        Log::info(" data_height count : " . count($data_height));
                        Log::info(" data_mode count : " . count($data_mode));
                        Log::info(" sequence : " . $k); 
                        if($data_height[$k - 1] === '')
                        {
                            $height = null;
                            Log::info("==== height ====: " . $data_height[$k - 1]);
                        } else {
                            $height = $data_height[$k - 1];
                            Log::info("... height ... : " . $data_height[$k - 1]);
                        }
                        if($data_mode[$k - 1] === 1)
                        {
                            if(($towerNumber - $prevTowerNumber) === 1)
                            {
                                $mode = null;
                            } else {
                                $mode = $data_mode[$k - 1];
                            }
                        }else {
                            $mode = $data_mode[$k - 1];
                        }
                        Log::info(" data_mode : " . $mode);
                        Log::info(" data_height: " . $height);
                        $prevTowerNumber = $towerNumber;
                    }
                    $arr[]=[
                        'submissionID'=>$submissionID,
                        'towerID'=>$towerID,
                        'createdTime'=>date('Y-m-d'),
                        'towerNumber'=>$tower['towerNumber'],
                        'createManID'=>$this->member_id,
                        'mode'=> $mode,
                        'height'=> $height,
                    ];
                }
                Log::info(" insert submissiontowerList : " . var_export($arr,true));
                //P($arr);
                if(!empty($arr)){
                    $res=Db::table('submissiontowerList')
                        ->insertAll($arr);
                    if(!$res){
                        Db::rollback();
                        ajax_return(-1,'添加失败');
                    }
                }
                Db::commit();
                ajax_return(1,'添加成功');
            }
        }


        Log::info("=== check task === : ");  
        $id=input('id',-1);
        $title='新建任务';
        $data=[];
        if($id>=0){
            $data=Db::table('submissionList')
                ->where('submissionID',$id)
                ->find();
            $title='任务详情';
            Log::info(" data : " . var_export($data,true));

            $tower_ids=Db::table('submissiontowerList')
                ->where('submissionID',$id)
                ->column('towerID');
            if(empty($tower_ids)){
                $tower_ids=[-1];
            }
            Log::info(" tower_ids : " . var_export($tower_ids,true));

            $line_ids=Db::table('towerList')
                ->where('towerId','in',$tower_ids)
                ->group('lineID')
                ->column('lineID');

            Log::info(" line_ids : " . var_export($line_ids,true));
              
            if(empty($line_ids)){
                $line_ids=[-1];
            }

            $all_towers=Db::table('towerList')
                ->where('lineID','in',$line_ids)
                ->select();
            // $all_towers=Db::table('towerList')
            //     ->where('lineID','in',$line_ids)
            //     ->where("basicID <> -1")
            //     ->select();
            Log::info(" all_towers : " . var_export($all_towers,true));

            $towers1=Db::table('towerList')
                ->where('towerID','in',$tower_ids)
                ->order('lineID asc,towerID asc')
                ->select();
            Log::info(" towers1 : " . var_export($towers1,true));

            $towers=Db::view('submission_tower_missiontype')
                ->where('submissionID',$id)
                ->order('lineID asc')
                ->select();
            Log::info(" towers2 : " . var_export($towers,true));


            $snCode = Db::view('submission_mission')
                ->where('submissionID',$id)
                ->value('snCode');
        }
        Log::info("data in add task controller : " . var_export($data, true));

        $missions=Db::table('missionList')
            ->order('missionID asc')
            ->select();

        // 查询 networkTypes 表中的记录
            $networkTypes = Db::table('networkTypes')
            ->select();

            $validMainNetwork = false;
            $validSubNetwork = false;
            
            // 遍历 networkTypes 记录，判断 isValid 字段的值
            foreach ($networkTypes as $networkType) {
                if ($networkType['networkType'] == '主网' && $networkType['isValid']) {
                    $validMainNetwork = true;
                }
                if ($networkType['networkType'] == '配网' && $networkType['isValid']) {
                    $validSubNetwork = true;
                }
            }
            
            // 查询 missionList 表中的记录
            $missionList = Db::table('missionList')
            ->select();
            
            // 根据 isValid 字段的值筛选 missionList 数据
            $filteredMissionList = array();
            
            if ($validMainNetwork && !$validSubNetwork) {
            // 只主网有效，显示前三个数据
                $filteredMissionList = array_slice($missionList, 0, 3);
            } elseif (!$validMainNetwork && $validSubNetwork) {
            // 只配网有效，显示后三个数据
             $filteredMissionList = array_slice($missionList, -3);
            } elseif ($validMainNetwork && $validSubNetwork) {
            // 主网和配网都有效，显示全部数据
                $filteredMissionList = $missionList;
            }


        $lines=Db::table('lineList')
            ->order('lineID asc')
            ->select();

        $shapes=Db::table('towerShapeList')
            ->column('towerShapeName','towerShapeNameID');

        $policys=Db::table('returnPolicy')
        ->order('policyID asc')
        ->select();
        $this->assign(compact('data','title','shapes','missions','lines','towers','towers1','all_towers','line_ids','tower_ids','policys','snCode','returnAltitude','RTK','filteredMissionList'));
        return $this->fetch();
    }

    public function get_tower(){
        Log::info(" === get_tower === ");
        $count=input('count',0);
        $lineID=input('lineID');
        $length=0;
        $html='';
        if($lineID>0 && $this->my_permission['findTask']>0){

            // use basicID find line
            // $towers=Db::table('towerList')
            //     ->where("lineID=$lineID and basicID <> -1")
            //     ->select();

            $towers=Db::table('towerList')
                ->where("lineID=$lineID")
                ->select();

            Log::info(" towers : " . var_export($towers,true));

            $length=count($towers);
            Log::info(" length : " . var_export($length,true));

            $shapes=Db::table('towerShapeList')
                ->column('towerShapeName','towerShapeNameID');
            Log::info(" shapes : " . var_export($shapes,true));
            
            foreach($towers as $k=>$v){
                $count++;
                $html.='
                <tr class="tr'.$v['towerID'].' line'.$lineID.' tower_con" data-id="'.$v['towerID'].'" data-line="'.$lineID.'">
                    <td>'.$count.'</td>
                    <td>'.$v['towerName'].'</td>
                    <td>'.$shapes[$v['towerShapeID']].'</td>
                    <td>
                        <input type="hidden" value="'.$v['towerID'].'" />
                        <a href="javascript:void(0);" class="btn" onclick="choose_tower(this)">选择 +</a>
                    </td>
                </tr>
                ';
            }
        }
        ajax_return(1,'',['length'=>$length,'html'=>$html]);
    }

    public function get_related_droneID(){
        $count=input('count',0);
        // echo $count;
        $droneID=input('droneID');
        $length=0;
        $html='';
        $response = [
            'status' => 1,
            'message' => '更新成功'
        ];
        echo json_encode($response);
        exit;
        ajax_return(1,'',['length'=>$length,'html'=>$html]);
    }

    public function login(){
        if(IS_POST){
            $data = input();
            if (empty($data['username'])) {
                ajax_return(-1, '请输入帐号');
            }
            if (empty($data['password'])) {
                ajax_return(-1, '请输入密码');
            }
            $has_user = Db::table('UserList')
                ->where(['username'=>$data['username'],'passWord'=>$data['password']])
                ->find();
            if(empty($has_user)){
                ajax_return(-1,'帐号或密码错误');
            }
            session('member_id',$has_user['userID']);
            session('member',$has_user);
            if((int)$data['remember']>0){
                Cookie::set('member_id',$has_user['userID']);
            }else{
                Cookie::set('member_id',-1);
            }
            ajax_return(1,'登录成功');
        }
        if($this->is_login){
            $this->redirect(url('index/index'));
        }
        return $this->fetch();
    }

    public function basic_list(){
        if($this->my_permission['findBasicData']<=0){
            return $this->fetch();
        }
        $line_where='1=1';
        $lineName=input('lineName','');
        if($lineName){
            $line_where.=" and lineName like '%{$lineName}%'";
        }
        $lines=Db::table('lineList')
            ->where($line_where)
            ->order('lineID desc')
            ->select();

        $lineID=input('lineID',-1);
        if($lineID>=0){
            $tower_where="lineID=$lineID";
            $towerName=input('towerName','');
            if($towerName){
                $tower_where.=" and towerName like '%{$towerName}%'";
            }
            $towers=Db::view('tower_line_towershape')
                ->where($tower_where)
                ->order('towerID desc')
                ->select();

        }
        $this->assign(compact('lines','lineName','towerName','lineID','towers'));
        return $this->fetch();
    }

    public function add_line(){
        if($this->my_permission['findBasicData']<=0){
            return $this->fetch();
        }
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            if(empty($data['lineName'])){
                ajax_return(-1,'请填写线路名称');
            }
           
            Db::startTrans();
            $id=$data['lineID'];
            unset($data['lineID']);
            if($id>=0){
                if($this->my_permission['modifyBasicData']<=0){
                    ajax_return(-1,'无权操作');
                }
                $has_data=Db::table('lineList')
                    ->where('lineID',$id)
                    ->find();
                if(empty($has_data)){
                    ajax_return(-1,'未知线路');
                }

                $res=Db::table('lineList')
                    ->where('lineID',$id)
                    ->update($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'修改失败');
                }
                Db::commit();
                ajax_return(1,'修改成功');
            }else{
                if($this->my_permission['addBasicData']<=0){
                    ajax_return(-1,'无权操作');
                }
                $data['createdTime']=date('Y-m-d H:i:s');
                $data['createManID']=$this->member_id;
                $res=Db::table('lineList')
                    ->insertGetId($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'添加失败');
                }
                Db::commit();
                ajax_return(1,'添加成功');
            }
        }
        $id=input('id',-1);
        $title='新建线路';
        $data=[];
        if($id>=0){
            $data=Db::table('lineList')
                ->where('lineID',$id)
                ->find();
            $title='线路详情';
        }
        $companys=Db::table('companyList')
            ->order('companyID asc')
            ->select();
        $towerType = Db::table("towerShapeList")
            ->order('towerShapeNameID asc')
            ->select();
        $this->assign(compact('data','title','companys','towerType'));
        return $this->fetch();
    }

    public function add_tower(){
        Log::info(" ==== add_tower ==== ");

        if(IS_AJAX && IS_POST){
            $data=input('post.');
            if(empty($data['towerName'])){
                ajax_return(-1,'请填写杆塔名称');
            }

            Db::startTrans();
            $id=$data['towerID'];
            Log::info("id: " . $id);
            Log::info("data : " . var_export($data,true));
            unset($data['towerID']);
            $location=$data['location'];
            $location=str_replace('，',',',$location);
            $location=explode(',',$location);
            unset($data['location']);
            $data['longitude']=$location[0];
            $data['latitude']=$location[1];
            $data['altitude']=$location[2];
            Log::info("data['po'] : " . var_export($data['po'], true));
            if($data['po_arr'] === '')
            {
                $data['po_arr'] = implode('', $data['po']);
            }
            Log::info("data['po_arr'] : " . var_export($data['po_arr'], true));
            unset($data['po']);
            $po=$data['po_arr'];
            unset($data['po_arr']);
   
            // unset($data['hiddenTowerShapeID']);
            $po=bindec($po);
            Log::info("po : " . var_export($po, true));
            //P($po);
            $data['photoPosition']=$po;
            $towerNumber = Db::table('towerList')
            ->where('lineID', $data['lineID'])
            ->count();
            Log::info("towerNumber : " . var_export($towerNumber, true));
            $data['towerNumber']= $towerNumber + 1;
            Log::info("data in add tower : " . var_export($data, true));
            //P($data);
            if($id>=0){
                if($this->my_permission['modifyBasicData']<=0){
                    ajax_return(-1,'无权操作');
                }
                $has_data=Db::table('towerList')
                    ->where('towerID',$id)
                    ->find();
                if(empty($has_data)){
                    ajax_return(-1,'未知杆塔');
                }

                $res=Db::table('towerList')
                    ->where('towerID',$id)
                    ->update($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'修改失败');
                }
                Db::commit();
                ajax_return(1,'修改成功');
            }else{
                if($this->my_permission['addBasicData']<=0){
                    ajax_return(-1,'无权操作');
                }
                $data['createdTime']=date('Y-m-d H:i:s');
                $data['createManID']=$this->member_id;
                $res=Db::table('towerList')
                    ->insertGetId($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'添加失败');
                }
                Db::commit();
                ajax_return(1,'添加成功');
            }
        }
        $id=input('id',-1);
        $title='新建杆塔';
        $data=[];
        if($id>=0){
            $data=Db::table('towerList')
                ->where('towerID',$id)
                ->find();
            
            Log::info("data id > 0 : " .var_export($data,true));

            $shapeInCompass=Db::table('towerShapeList')
                ->where('towerShapeNameID', $data['towerShapeID'])
                ->column('shapeInCompass');
                
            $towerShapeNameID=Db::table('towerShapeList')
                ->where('towerShapeNameID', $data['towerShapeID'])
                ->column('towerShapeNameID');

            $towerShapeName=Db::table('towerShapeList')
                ->where('towerShapeNameID', $data['towerShapeID'])
                ->column('towerShapeName');
            $towerShapeName = implode('', $towerShapeName); 
            $data['towerShapeName'] = $towerShapeName;

            $towerShapeNameID = implode('', $towerShapeNameID); 
            $data['towerShapeNameID'] = $towerShapeNameID;

            Log::info("shapeInCompass: " .var_export($shapeInCompass,true));

            // $data['po']=(string)decbin($data['photoPosition']);
            // $data['po']=str_split($data['po']);

            //combine element in array to form a string 
            $string = implode('', $shapeInCompass); 
            Log::info("string : " .$string);
            // $data['shapeInCompass'] = $string;
            // $data['po']=(string)decbin($data['photoPosition']);
            $data['po']=str_split($string);
            // $data['po']=str_split($data['po']);
            //P($data);
            $title='杆塔详情';
          
        }
        Log::info("data : " .var_export($data,true));

        $lines=Db::table('lineList')
            ->order('lineID asc')
            ->select();

        $shapes=Db::table('towerShapeList')
            ->order('towerShapeNameID asc')
            ->select();
        
        Log::info("shapes : " .var_export($shapes,true));  
        Log::info("data : " .var_export($data,true));  
        $lineID=input('lineID',-1);


        // $po_arr1 = [];
        // foreach ($shapes as $shape) {
        //     $po_arr[$shape['shapeInCompass']] = $shape['towerShapeNameID'];
        //     $po_arr1[$shape['towerShapeNameID']] = $shape['shapeInCompass'];
        // }
        // Log::info("po_arr1 first : " . var_export($po_arr1, true));
        // Log::info("po_arr first: " . var_export($po_arr, true));

        $po_arr1 = [];
        $po_arr = [];
        foreach ($shapes as $shape) {
            if (isset($shape['shapeInCompass'])) {
                Log::info("shapeInCompass : " . var_export($shape['shapeInCompass'], true));

                $po_arr1[$shape['towerShapeNameID']] = $shape['shapeInCompass'];
                // $po_arr['_' .$shape['shapeInCompass']] = $shape['towerShapeNameID'];
                $po_arr[$shape['shapeInCompass']] = $shape['towerShapeNameID'];
            } else {
                $po_arr1[$shape['towerShapeNameID']] = '';
                // $po_arr['_' .$shape['shapeInCompass']] = '';
                $po_arr[$shape['shapeInCompass']] = '';
            }
        }

        // 确保 $po_arr1 包含了所有形状的数据
        Log::info("po_arr1 second: " . var_export($po_arr1, true));
        Log::info("po_arr second: " . var_export($po_arr, true));

        // 将 $po_arr1 分配给视图  
        $this->assign(compact('po_arr1','po_arr'));
        $this->assign(compact('data','title','lines','shapes','lineID'));
        return $this->fetch();
    }

    public function del_tower(){
        if(IS_AJAX && IS_POST){
            if($this->my_permission['deleteTask']<=0){
                ajax_return(-1,'无权操作');
            }
            $id=input('id',-1);
            if($id==0){
                ajax_return(-1,'不可删除');
            }
            $has_data=Db::table('towerlist')
                ->where('towerID',$id)
                ->find();
            if(empty($has_data)){
                ajax_return(-1,'未知数据');
            }
            $res=Db::table('towerList')
                ->where('towerID',$id)
                ->delete();
            Log::info("res : " .var_export($res,true));
            if($res!==false){
                ajax_return(1,'已删除');
            }
            ajax_return(-1,'删除失败');
        }
    }



    public function import(){
        if(IS_AJAX && IS_POST){
            //P($_FILES);
            if($_FILES['file']['size']<=0){
                ajax_return(-1,'请上传txt文档');
            }

            if($_FILES['file']['size']>0){
                $file = request()->file('file');
                $savePath = './loc_file/';
                $saveName = 'loc_txt_'.date('ymd_His').'.'.pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);
                $result = $file->validate(['size'=>2097152,'ext'=>'txt,TXT'])->move($savePath, $saveName);;

                if($result){
                    $excel=$savePath.$saveName;
                    $content=file_get_contents($excel);
                    $content=json_decode($content,true)['result']['gps'][0];
                    if(empty($content)){
                        ajax_return(-1,'未识别出坐标数据，请确保数据格式正确');
                    }
                    $loc=$content['lon'].','.$content['lat'].','.$content['alt'];
                    ajax_return(1,'识别成功',['loc'=>$loc]);
                    @unlink($savePath.$saveName);
                    ajax_return(1,'操作成功');
                }else{
                    ajax_return(-1,'Excel文件保存出错');
                }
            }
            ajax_return(-1,'操作成功');
        }
    }



    public function importLine(){
        Log::info(" ==== importLine ====" );
        if(IS_AJAX && IS_POST){
            //P($_FILES);
            $lineID = $_POST['lineID'];
            $towerType = isset($_POST['towerType']) ? $_POST['towerType'] : '';
            $zoomFactor = isset($_POST['zoomFactor']) ? $_POST['zoomFactor'] : '';

            Log::info("lineID :" .var_export($lineID,true));
            Log::info("TowerType :" .var_export($towerType,true));
            Log::info("zoomFactor :" .var_export($zoomFactor,true));

            Log::info("file size :" .var_export($_FILES['file']['size'],true));

            if($_FILES['file']['size']<=0){
                ajax_return(-1,'请上传csv文档');
            }

            if($_FILES['file']['size']>0){
                $file = request()->file('file');
                $savePath = './loc_file/';
                $saveName = 'loc_txt_'.date('ymd_His').'.'.pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);
                $result = $file->validate(['size'=>2097152,'ext'=>'csv,CSV'])->move($savePath, $saveName);
                Log::info("result :" .var_export($result,true));
                if($result){
                    $excel = $savePath . $saveName;
                    Log::info("excel :" .var_export($excel,true));
                    $file = fopen($excel, "r");
                    if ($file) {
                        // 读取CSV文件的第一行，通常是表头
                        $header = fgetcsv($file);
                        // 检测表头编码格式并转换为UTF-8
                        foreach ($header as $column) {
                            $encoding = mb_detect_encoding($column, 'UTF-8,ISO-8859-1');
                            if ($encoding !== 'UTF-8') {
                                $column = iconv($encoding, 'UTF-8', $column);
                            }
                        }
                        $csvData[] = $header;
                        Log::info("header :" .var_export($header,true));

                        $sequence = 0;
                        while (($row = fgetcsv($file)) !== false) {
                            // 检测每行数据的编码格式并转换为UTF-8
                            foreach ($row as $column) {
                                $encoding = mb_detect_encoding($column, 'UTF-8,ISO-8859-1');
                                if ($encoding !== 'UTF-8') {
                                    $column = iconv($encoding, 'UTF-8', $column);
                                }
                            }
                            $sequence = $sequence + 1;
                            $csvData[] = $row;
                            Log::info("row :" .var_export($row,true));
                            $towerName = $row[0];
                            $longitude = $row[1];
                            $latitude = $row[2];
                            $altitude = $row[3];
                            Log::info("tower name :" .$towerName);
                            Log::info("longitude :" .$longitude);
                            Log::info("latitude:" .$latitude);
                            Log::info("altitude:" .$altitude);
                            Log::info("sequence :" .$sequence);
                            $arr[]=[
                                'towerName'=>$towerName,
                                'lineID' =>  $lineID,
                                'longitude'=>$longitude,
                                'latitude'=>$latitude,
                                'altitude'=>$altitude,
                                'towerNumber' => $sequence,
                                'insulatorNum'=>$zoomFactor,
                                'towerShapeID'=>$towerType,
                            ];
                        }
                        if(!empty($arr)){
                            $res=Db::table('towerList')
                                ->insertAll($arr);
                            if(!$res){
                                Db::rollback();
                                ajax_return(-1,'添加失败');
                            }
                        }
                        Db::commit();
                        ajax_return(1,'添加成功');
                        Log::info("row :" .var_export($row,true));
                        fclose($file);
                    }else {
                        Log::info("无法打开CSV文件");
                    } 
                }else{
                    ajax_return(-1,'Excel文件保存出错');
                }
            }
            ajax_return(-1,'操作成功');
        }
    }

    public function user_list(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        $where='1=1';
        $keywords=input('keywords','');
        $type=input('type',1);
        if($keywords){
            switch ($type){
                case 1:
                    $where.=" and full_name like '%{$keywords}%'";
                    break;
                case 2:
                    $where.=" and username like '%{$keywords}%'";
                    break;
                case 3:
                    $where.=" and position like '%{$keywords}%'";
                    break;
                case 4:
                    $where.=" and supervisor like '%{$keywords}%'";
                    break;
            }
        }
        $data=Db::table('UserList')
            ->where($where)
            ->order('userID desc')
            ->select();

        $groups=Db::table('groupList')
            ->column('groupName','groupID');

        $pgs=Db::table('permissionGroup')
            ->column('permission_group_name','groupID');

        $this->assign(compact('data','keywords','type','groups','pgs'));
        return $this->fetch();
    }

    public function add_user(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            if(empty($data['full_name'])){
                ajax_return(-1,'请填写人员名称');
            }
            if(empty($data['username'])){
                ajax_return(-1,'请填写用户名');
            }
            if(empty($data['passWord'])){
                ajax_return(-1,'请填写密码');
            }
            if($data['group_list_id']<0){
                ajax_return(-1,'请选择所属班组');
            }
            if($data['permissionGroupID']<0){
                ajax_return(-1,'请选择权限组');
            }
            Db::startTrans();
            $id=$data['userID'];
            unset($data['userID']);
            if($id>=0){
                if($this->my_permission['modifyUserMgr']<=0){
                    ajax_return(-1,'无权操作');
                }
                $has_data=Db::table('UserList')
                    ->where('userID',$id)
                    ->find();
                if(empty($has_data)){
                    ajax_return(-1,'未知人员');
                }

                $res=Db::table('UserList')
                    ->where('userID',$id)
                    ->update($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'修改失败');
                }
                Db::commit();
                ajax_return(1,'修改成功');
            }else{
                if($this->my_permission['addUserMgr']<=0){
                    ajax_return(-1,'无权操作');
                }
                $data['createdTime']=date('Y-m-d H:i:s');
                $data['createrManID']=$this->member_id;
                $res=Db::table('UserList')
                    ->insertGetId($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'添加失败');
                }
                Db::commit();
                ajax_return(1,'添加成功');
            }
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('UserList')
                ->where('userID',$id)
                ->find();
        }

        $groups=Db::table('groupList')
            ->order('groupID asc')
            ->select();

        $pgs=Db::table('permissionGroup')
            ->order('groupID asc')
            ->select();
        $this->assign(compact('data','groups','pgs'));
        //P($data);
        return $this->fetch();
    }

    public function check_user(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('UserList')
                ->where('userID',$id)
                ->find();
        }

        $groups=Db::table('groupList')
            ->order('groupID asc')
            ->select();

        $pgs=Db::table('permissionGroup')
            ->order('groupID asc')
            ->select();
        $this->assign(compact('data','groups','pgs'));
        //P($data);
        return $this->fetch();
    }

    public function del_user(){
        if(IS_AJAX && IS_POST){
            if($this->my_permission['deleteUserMgr']<=0){
                ajax_return(-1,'无权操作');
            }
            $id=input('id',-1);
            if($id==0){
                ajax_return(-1,'不可删除');
            }
            $has_data=Db::table('UserList')
                ->where('userID',$id)
                ->find();
            if(empty($has_data)){
                ajax_return(-1,'未知数据');
            }
            $res=Db::table('UserList')
                ->where('userID',$id)
                ->delete();
            if($res!==false){
                ajax_return(1,'已删除');
            }
            ajax_return(-1,'删除失败');
        }
    }

    public function company_list(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        $where='1=1';
        $keywords=input('keywords','');
        $type=input('type',1);
        if($keywords){
            switch ($type){
                case 1:
                    $where.=" and companyName like '%{$keywords}%'";
                    break;
                case 2:
                    $where.=" and address like '%{$keywords}%'";
                    break;
                case 3:
                    $where.=" and supervisor like '%{$keywords}%'";
                    break;
            }
        }
        $data=Db::table('companyList')
            ->where($where)
            ->order('companyID desc')
            ->select();
        $this->assign(compact('data','keywords','type'));
        return $this->fetch();
    }

    public function add_company(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            if(empty($data['companyName'])){
                ajax_return(-1,'请填写单位名称');
            }
            if(empty($data['address'])){
                ajax_return(-1,'请填写地址');
            }
            Db::startTrans();
            $id=$data['companyID'];
            unset($data['companyID']);
            if($id>=0){
                if($this->my_permission['modifyUserMgr']<=0){
                    ajax_return(-1,'无权操作');
                }
                $has_data=Db::table('companyList')
                    ->where('companyID',$id)
                    ->find();
                if(empty($has_data)){
                    ajax_return(-1,'未知单位');
                }

                $res=Db::table('companyList')
                    ->where('companyID',$id)
                    ->update($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'修改失败');
                }
                Db::commit();
                ajax_return(1,'修改成功');
            }else{
                if($this->my_permission['addUserMgr']<=0){
                    ajax_return(-1,'无权操作');
                }
                $data['createdTime']=date('Y-m-d H:i:s');
                $data['createrManID']=$this->member_id;
                $res=Db::table('companyList')
                    ->insertGetId($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'添加失败');
                }
                Db::commit();
                ajax_return(1,'添加成功');
            }
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('companyList')
                ->where('companyID',$id)
                ->find();
        }
        $this->assign(compact('data'));
        return $this->fetch();
    }

    public function check_company(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('companyList')
                ->where('companyID',$id)
                ->find();
        }
        $this->assign(compact('data'));
        return $this->fetch();
    }

    public function del_company(){
        if(IS_AJAX && IS_POST){
            if($this->my_permission['deleteUserMgr']<=0){
                ajax_return(-1,'无权操作');
            }
            $id=input('id',-1);
            if($id==0){
                ajax_return(-1,'不可删除');
            }
            $has_data=Db::table('companyList')
                ->where('companyID',$id)
                ->find();
            if(empty($has_data)){
                ajax_return(-1,'未知数据');
            }
            $res=Db::table('companyList')
                ->where('companyID',$id)
                ->delete();
            if($res!==false){
                ajax_return(1,'已删除');
            }
            ajax_return(-1,'删除失败');
        }
    }

    public function group_list(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        $where='1=1';
        $keywords=input('keywords','');
        $type=input('type',1);
        if($keywords){
            switch ($type){
                case 1:
                    $where.=" and groupName like '%{$keywords}%'";
                    break;
                case 2:
                    $where.=" and supervisor like '%{$keywords}%'";
                    break;
            }
        }
        $data=Db::table('groupList')
            ->where($where)
            ->order('groupID desc')
            ->select();
        $this->assign(compact('data','keywords','type'));
        return $this->fetch();
    }

    public function add_group(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            if(empty($data['groupName'])){
                ajax_return(-1,'请填写班组名称');
            }

            Db::startTrans();
            $id=$data['groupID'];
            unset($data['groupID']);
            if($id>=0){
                if($this->my_permission['modifyUserMgr']<=0){
                    ajax_return(-1,'无权操作');
                }
                $has_data=Db::table('groupList')
                    ->where('groupID',$id)
                    ->find();
                if(empty($has_data)){
                    ajax_return(-1,'未知班组');
                }

                $res=Db::table('groupList')
                    ->where('groupID',$id)
                    ->update($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'修改失败');
                }
                Db::commit();
                ajax_return(1,'修改成功');
            }else{
                if($this->my_permission['addUserMgr']<=0){
                    ajax_return(-1,'无权操作');
                }
                $data['createdTime']=date('Y-m-d H:i:s');
                $data['createrManID']=$this->member_id;
                $res=Db::table('groupList')
                    ->insertGetId($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'添加失败');
                }
                Db::commit();
                ajax_return(1,'添加成功');
            }
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('groupList')
                ->where('groupID',$id)
                ->find();
        }

        $companys=Db::table('companyList')
            ->order('companyID asc')
            ->select();
        $this->assign(compact('data','companys'));
        return $this->fetch();
    }

    public function check_group(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('groupList')
                ->where('groupID',$id)
                ->find();
        }

        $companys=Db::table('companyList')
            ->order('companyID asc')
            ->select();
        $this->assign(compact('data','companys'));
        return $this->fetch();
    }

    public function del_group(){
        if(IS_AJAX && IS_POST){
            if($this->my_permission['deleteUserMgr']<=0){
                ajax_return(-1,'无权操作');
            }
            $id=input('id',-1);
            if($id==0){
                ajax_return(-1,'不可删除');
            }
            $has_data=Db::table('groupList')
                ->where('groupID',$id)
                ->find();
            if(empty($has_data)){
                ajax_return(-1,'未知数据');
            }
            $res=Db::table('groupList')
                ->where('groupID',$id)
                ->delete();
            if($res!==false){
                ajax_return(1,'已删除');
            }
            ajax_return(-1,'删除失败');
        }
    }

    public function pg_list(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        $where='1=1';
        $keywords=input('keywords','');
        if($keywords){
            $where.=" and permission_group_name like '%{$keywords}%'";
        }
        $data=Db::table('PermissionGroup')
            ->where($where)
            ->order('groupID desc')
            ->select();

        $permissions=Db::table('permissionList')
            ->column('permissionName','permissionID');

        $this->assign(compact('data','keywords','permissions'));
        return $this->fetch();
    }

    public function add_pg(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            if(empty($data['permission_group_name'])){
                ajax_return(-1,'请填写权限组名称');
            }
            Db::startTrans();
            $id=$data['groupID'];
            unset($data['groupID']);
            if($id>=0){
                if($this->my_permission['modifyUserMgr']<=0){
                    ajax_return(-1,'无权操作');
                }
                $has_data=Db::table('PermissionGroup')
                    ->where('groupID',$id)
                    ->find();
                if(empty($has_data)){
                    ajax_return(-1,'未知权限组');
                }

                $res=Db::table('PermissionGroup')
                    ->where('groupID',$id)
                    ->update($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'修改失败');
                }
                Db::commit();
                ajax_return(1,'修改成功');
            }else{
                if($this->my_permission['addUserMgr']<=0){
                    ajax_return(-1,'无权操作');
                }
                $data['createdTime']=date('Y-m-d H:i:s');
                $data['createrManID']=$this->member_id;
                $res=Db::table('PermissionGroup')
                    ->insertGetId($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'添加失败');
                }
                Db::commit();
                ajax_return(1,'添加成功');
            }
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('PermissionGroup')
                ->where('groupID',$id)
                ->find();
        }
        $pl=Db::table('permissionList')
            ->order('permissionID desc')
            ->select();
        $this->assign(compact('pl','data'));
        return $this->fetch();
    }

    public function check_pg(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('PermissionGroup')
                ->where('groupID',$id)
                ->find();
        }
        $pl=Db::table('permissionList')
            ->order('permissionID desc')
            ->select();
        $this->assign(compact('pl','data'));
        return $this->fetch();
    }

    public function del_pg(){

        if(IS_AJAX && IS_POST){
            if($this->my_permission['deleteUserMgr']<=0){
                ajax_return(-1,'无权操作');
            }
            $id=input('id',-1);
            if($id==0){
                ajax_return(-1,'不可删除');
            }
            $has_data=Db::table('PermissionGroup')
                ->where('groupID',$id)
                ->find();
            if(empty($has_data)){
                ajax_return(-1,'未知数据');
            }
            $res=Db::table('PermissionGroup')
                ->where('groupID',$id)
                ->delete();
            if($res!==false){
                ajax_return(1,'已删除');
            }
            ajax_return(-1,'删除失败');
        }
    }

    public function permission_list(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        $where='1=1';
        $keywords=input('keywords','');
        if($keywords){
            $where.=" and permissionName like '%{$keywords}%'";
        }
        $data=Db::table('permissionList')
            ->where($where)
            ->order('permissionID desc')
            ->select();
        $this->assign(compact('data','keywords'));
        return $this->fetch();
    }

    public function add_permission(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            //P($data);
            $data['addTask']+=0;
            $data['deleteTask']+=0;
            $data['modifyTask']+=0;
            $data['findTask']+=0;
            $data['addBasicData']+=0;
            $data['deleteBasicData']+=0;
            $data['modifyBasicData']+=0;
            $data['findBasicData']+=0;
            $data['addUserMgr']+=0;
            $data['deleteUserMgr']+=0;
            $data['modifyUserMgr']+=0;
            $data['findUserMgr']+=0;
            $data['addData']+=0;
            $data['deleteData']+=0;
            $data['modifyData']+=0;
            $data['findData']+=0;
            if(empty($data['permissionName'])){
                ajax_return(-1,'请填写权限名称');
            }
            Db::startTrans();
            $id=$data['permissionID'];
            unset($data['permissionID']);
            if($id>=0){
                if($this->my_permission['modifyUserMgr']<=0){
                    ajax_return(-1,'无权操作');
                }
                $has_data=Db::table('permissionList')
                    ->where('permissionID',$id)
                    ->find();
                if(empty($has_data)){
                    ajax_return(-1,'未知权限');
                }

                $res=Db::table('permissionList')
                    ->where('permissionID',$id)
                    ->update($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'修改失败');
                }
                Db::commit();
                ajax_return(1,'修改成功');
            }else{
                if($this->my_permission['addUserMgr']<=0){
                    ajax_return(-1,'无权操作');
                }
                $data['createdTime']=date('Y-m-d H:i:s');
                $data['createManID']=$this->member_id;
                $res=Db::table('permissionList')
                    ->insertGetId($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'添加失败');
                }
                Db::commit();
                ajax_return(1,'添加成功');
            }
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('permissionList')
                ->where('permissionID',$id)
                ->find();
        }
        $this->assign(compact('data'));
        return $this->fetch();
    }

    public function check_permission(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('permissionList')
                ->where('permissionID',$id)
                ->find();
        }
        $this->assign(compact('data'));
        return $this->fetch();
    }

    public function del_permission(){
        if(IS_AJAX && IS_POST){
            if($this->my_permission['deleteUserMgr']<=0){
                ajax_return(-1,'无权操作');
            }
            $id=input('id',-1);
            if($id==0){
                ajax_return(-1,'不可删除');
            }
            $has_data=Db::table('permissionList')
                ->where('permissionID',$id)
                ->find();
            if(empty($has_data)){
                ajax_return(-1,'未知数据');
            }
            $res=Db::table('permissionList')
                ->where('permissionID',$id)
                ->delete();
            if($res!==false){
                ajax_return(1,'已删除');
            }
            ajax_return(-1,'删除失败');
        }
    }

    public function log_list(){
        if($this->my_permission['findData']<=0){
            return $this->fetch();
        }
        $where='1=1';
        $keywords=input('keywords','');
        if($keywords){
            $where.=" and submissionName like '%{$keywords}%'";
        }
        $data=Db::table('FlightRecords')
            ->where($where)
            ->order('FlightRecordsID desc')
            ->select();
        $this->assign(compact('data','keywords'));
        return $this->fetch();
    }

    public function log_detail(){
        if($this->my_permission['findData']<=0){
            return $this->fetch();
        }
        $id=input('id',-1);
        $data=Db::table('FlightRecords')
            ->where('FlightRecordsID',$id)
            ->find();

        $missions=Db::table('missionList')
            ->column('missionType','missionID');

        $towers=Db::table('FlightRecordsTowerList')
            ->where(['FlightRecordsID'=>$id])
            ->select();
        $this->assign(compact('data','missions','towers'));
        return $this->fetch();
    }



    public function flight_list(){
        if($this->my_permission['findData']<=0){
            return $this->fetch();
        }
        $where='1=1';
        $keywords=input('keywords','');
        if($keywords){
            $where.=" and towerShapeName like '%{$keywords}%'";
        }
        $data=Db::table('towerShapeList')
            ->where($where)
            ->order('towerShapeNameID desc')
            ->select();
        $this->assign(compact('data','keywords'));
        return $this->fetch();
    }

    public function add_flight(){
        // $po_arr=[
        //     '0000'=>1,
        //     '1100'=>2,
        //     '1111'=>3
        // ];
        // $po_arr1=[
        //     1=>'0000',
        //     2=>'1100',
        //     3=>'1111'
        // ];
        // $this->assign(compact('po_arr','po_arr1'));


        $shapes=Db::table('towerShapeList')
        ->order('towerShapeNameID asc')
        ->select();
        $po_arr1 = [];
        $po_arr = [];
        foreach ($shapes as $shape) {
            if (isset($shape['shapeInCompass'])) {
                Log::info("shapeInCompass : " . var_export($shape['shapeInCompass'], true));

                $po_arr1[$shape['towerShapeNameID']] = $shape['shapeInCompass'];
                // $po_arr['_' .$shape['shapeInCompass']] = $shape['towerShapeNameID'];
                $po_arr[$shape['shapeInCompass']] = $shape['towerShapeNameID'];
            } else {
                $po_arr1[$shape['towerShapeNameID']] = '';
                // $po_arr['_' .$shape['shapeInCompass']] = '';
                $po_arr[$shape['shapeInCompass']] = '';
            }
        }

        // 确保 $po_arr1 包含了所有形状的数据
        Log::info("po_arr1 second: " . var_export($po_arr1, true));
        Log::info("po_arr second: " . var_export($po_arr, true));
        $this->assign(compact('po_arr1','po_arr'));


        if($this->my_permission['findData']<=0){
            return $this->fetch();
        }
        if(IS_AJAX && IS_POST){
            Log::info("==== add_flight ====");
            $data=input('post.');
            if(empty($data['towerShapeName'])){
                ajax_return(-1,'请填写杆塔形状名称');
            }

            Log::info("data :" .var_export($data,true));
            $shapeInCompass = implode('', $data['po']);
            Log::info("shapeInCompass :" .$shapeInCompass);
            $data['shapeInCompass'] = $shapeInCompass;
            Log::info("data :" .var_export($data,true));
            unset($data['po']);
            Db::startTrans();
            Log::info("data :" .var_export($data,true));
            $id=$data['towerShapeNameID'];
            unset($data['towerShapeNameID']);
            if($id>=0){
                if($this->my_permission['modifyData']<=0){
                    ajax_return(-1,'无权操作');
                }
                $has_data=Db::table('towerShapeList')
                    ->where('towerShapeNameID',$id)
                    ->find();
                if(empty($has_data)){
                    ajax_return(-1,'未知数据');
                }

                $res=Db::table('towerShapeList')
                    ->where('towerShapeNameID',$id)
                    ->update($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'修改失败');
                }
                Db::commit();
                ajax_return(1,'修改成功');
            }else{
                if($this->my_permission['addData']<=0){
                    ajax_return(-1,'无权操作');
                }
                $data['createdTime']=date('Y-m-d H:i:s');
                $data['createManID']=$this->member_id;
                $res=Db::table('towerShapeList')
                    ->insertGetId($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'添加失败');
                }
                Db::commit();
                ajax_return(1,'添加成功');
            }
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('towerShapeList')
                ->where('towerShapeNameID',$id)
                ->find();
        }

        $lts=Db::table('lineTypeList')
            ->select();
        Log::info("data in add flight:" .var_export($data,true));
        Log::info("lts in add flight:" .var_export($lts,true));
        $this->assign(compact('data','lts'));
        return $this->fetch();
    }

    public function check_flight(){
        Log::info("==== check_flight ==== ");
        // $po_arr=[
        //     '0000'=>1,
        //     '1100'=>2,
        //     '1111'=>3
        // ];
        // $po_arr1=[
        //     1=>'0000',
        //     2=>'1100',
        //     3=>'1111'
        // ];
        // $this->assign(compact('po_arr','po_arr1'));
        $shapes=Db::table('towerShapeList')
        ->order('towerShapeNameID asc')
        ->select();
        $po_arr1 = [];
        $po_arr = [];
        foreach ($shapes as $shape) {
            if (isset($shape['shapeInCompass'])) {
                Log::info("shapeInCompass : " . var_export($shape['shapeInCompass'], true));

                $po_arr1[$shape['towerShapeNameID']] = $shape['shapeInCompass'];
                // $po_arr['_' .$shape['shapeInCompass']] = $shape['towerShapeNameID'];
                $po_arr[$shape['shapeInCompass']] = $shape['towerShapeNameID'];
            } else {
                $po_arr1[$shape['towerShapeNameID']] = '';
                // $po_arr['_' .$shape['shapeInCompass']] = '';
                $po_arr[$shape['shapeInCompass']] = '';
            }
        }

        // 确保 $po_arr1 包含了所有形状的数据
        Log::info("po_arr1 second: " . var_export($po_arr1, true));
        Log::info("po_arr second: " . var_export($po_arr, true));

        // 将 $po_arr1 分配给视图  
        $this->assign(compact('po_arr1','po_arr'));




        if($this->my_permission['findData']<=0){
            return $this->fetch();
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('towerShapeList')
                ->where('towerShapeNameID',$id)
                ->find();
           
        }
        Log::info(" data : " . var_export($data,true));
        $lts=Db::table('lineTypeList')
            ->select();
        Log::info(" lts : " . var_export($lts,true));
        $this->assign(compact('data','lts'));
        return $this->fetch();
    }

    public function del_flight(){
        if(IS_AJAX && IS_POST){
            if($this->my_permission['deleteData']<=0){
                ajax_return(-1,'无权操作');
            }
            $id=input('id',-1);
            if(in_array($id,[1,2,3])){
                ajax_return(-1,'该条数据不可删除');
            }
            if($id==0){
                ajax_return(-1,'不可删除');
            }
            $has_data=Db::table('towerShapeList')
                ->where('towerShapeNameID',$id)
                ->find();
            if(empty($has_data)){
                ajax_return(-1,'未知数据');
            }
            $res=Db::table('towerShapeList')
                ->where('towerShapeNameID',$id)
                ->delete();
            if($res!==false){
                ajax_return(1,'已删除');
            }
            ajax_return(-1,'删除失败');
        }
    }

    public function get_drone(){
        Log::info("==== get_drone ===" );
        $id=input('id',-1);
        $title='设备详情';
        $data=[];
        if($id>=0){
            $data=Db::table('drone')
                ->where('droneID',$id)
                ->find();

            // $tower_ids=Db::table('submissiontowerList')
            //     ->where('submissionID',$id)
            //     ->column('towerID');
            // if(empty($tower_ids)){
            //     $tower_ids=[-1];
            // }

        }
   

        if(IS_AJAX && IS_POST){
            $data=input('post.');

            $controller =  "get_drone";
            $len = strLen(json_encode($data));

            $combinedData = array(
                "controller" => $controller,
                "len" => $len,
                "data" =>  $data,
            );
            $combinedData = json_encode($combinedData);
            Log::info(" response : " . var_export($combinedData,true));

            $socket = new tcp();
            $socket->socketSend($combinedData);
            $response = array( 
                'status' =>1,
                'message' =>'上传成功',
                'data' => $data,
            );
            Log::info(" response : " . var_export($response,true));
            ajax_return($response);
            //ajax_return(1,'上传成功');
        }



        $this->assign(compact('data','title'));

        return $this->fetch();
    }




    
    public function set_related_drone(){   
        Log::info("=== set_related_drone === " );
        if($this->my_permission['findTask']<=0){
            return $this->fetch();
        }

        if(IS_AJAX && IS_POST){
            $data=input('post.');
           
            Db::startTrans();
            if (!isset($data['droneID'])) {
                $data['droneID'] = null; 
            }
            Log::info(" data " . var_export($data,true));
            Log::info(" data['droneID'] " . $data['droneID'] );
            $submissionID=$data['submissionID'];
            Log::info(" submissionID " .  $submissionID );
        
            Log::info("insert to  submissionList " );
            $res=Db::table('submissionList')
                ->where('submissionID' ,$submissionID)
                ->update($data);
            if(!$res){
                    Db::rollback();
                    ajax_return(-1,'修改失败');
            }
    
            Db::commit();
            ajax_return(1,'修改成功');
        }

        // Log::write('This is a log message', 'notice');

        $id=input('id',-1);
        $title='关联设备';
        $data=[];
        Log::info("  id " .  $id );
        if($id>=0){
            $data=Db::table('submissionList')
                ->where('submissionID',$id)
                ->find();
      
        }
        // Log::info(" data: " .var_export($data));

        $all_drones = Db::table('drone')
            ->order('droneID desc')
            ->select();

        Log::info("all_drones : " .var_export($all_drones,true));

        $drone_ids = Db::table('submissionList')
                    ->whereNotNull('droneID' )
                    ->column('droneID');
        Log::info("drone_ids : " .var_export($drone_ids,true));

        if(empty($drone_ids)){
            $drone_ids=[-1];
        }

        $this->assign(compact('data','all_drones','drone_ids','title'));
        return $this->fetch();
    }
    
    public function set_return(){

        $data=input('get.');
        $submissionID = $data['submissionID'];
        $droneID = $data['droneID'];
        // 查询数据库，根据 submissionID 获取 submissionName
        $submissionName = Db::table('submissionList')
            ->where('submissionID', $submissionID)
            ->value('submissionName');

        $snCode = Db::table('drone')
            ->where('droneID', $droneID)
            ->value('snCode');

        // Log::info("submissionID is :");
        // Log::info("submissionID :"  + $submissionID);

        // Log::info("submissionName is :");
        Log::info("submissionID :"  . $submissionID  . ' submissionName : ' . $submissionName);
        Log::info("submissionID :"  . $submissionID  . ' snCode : ' . $snCode);
        // 返回查询结果
        // return json([
        //     'submissionName' => $submissionName
        // ]);

        


        $policys=Db::table('returnPolicy')
        ->order('policyID asc')
        ->select();

        $this->assign(compact('data','policys','submissionName','snCode'));
        return $this->fetch();
    }


    public function play_video(){
        $all_drones = Db::table('drone')
        ->where($drone_where)
        ->order('droneID desc')
        ->select();
        
        $this->assign(compact('all_drones'));
        return $this->fetch();
    }



    public function check_router(){
        if(IS_AJAX && IS_POST){
            $datas=input('post.');
            Log::info(" === check_router === ");
            $flightTypeArray = [];
            foreach($datas as $data) {
                $folderPath = 'C:\Users\123\Desktop\origin_php\myphp\application\index\controller\router'; 
                $files = scandir($folderPath);
                $flight = 1; // 默认值为1
                $submissionID = (string) $data;
                Log::info("submissionID : " . $submissionID);
                if(is_array($files) && !empty($files))
                {
                    foreach ($files as $file) {
                        $files = (string) $file;
                        Log::info("files : " . $files);
                        if (strpos($files, $submissionID) === 0) {
                            $flight = (strpos($file, $submissionID) === false) ? 1 : 2;
                            Log::info("flight : " . $flight);
                            break; 
                        }
                    }
                }
                Log::info("flight : " . $flight);
                $flightTypeArray[] = $flight;
            }

        }
        return json(['flightTypeArray' => $flightTypeArray]);
    }


    public function set_tower_type(){
        $data = Db::table("towerList")
            ->order('towerID asc')
            ->select();
        $towerType = Db::table("towerShapeList")
        ->order('towerShapeNameID asc')
        ->select();
        $title='设置杆塔属性';
        $this->assign(compact('towerType','title', 'data'));
        return $this->fetch();
    }
}