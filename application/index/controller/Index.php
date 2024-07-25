<?php

namespace app\index\controller;

use app\common\model\Message;
use Mpdf\Tag\Select;
use think\Db;
use think\facade\Cookie;
use think\facade\Log;
// use think\Log;
use think\App;
use think\facade\Request;
use think\facade\Session;
use ZipArchive;

require_once("tcp.php");
$TaskPath = null;
class Index extends Base
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getHeightDifference(){
        if(IS_AJAX && IS_POST){
            $datas=input('post.');
            Log::info("getHeightDifference : " . var_export($datas,true));

            $submission_id_associated_drone =  Db::table("submission_mission")
                                        ->whereIn('submissionID',$datas)
                                        ->whereNotNull('sncode')
                                        ->column('submissionID');
            Log::info("submission_id_associated_drone : " . var_export($submission_id_associated_drone, true));

            $items = Db::table("drone_submission_homepoint")
                    ->whereIn('submissionID',$submission_id_associated_drone)
                    ->whereNotNull('sncode')
                    ->select();

            Log::info("items : " . var_export($items,true));
            foreach($items as $item){
                $firstTowerAlt = Db::view("submission_tower_missiontype")
                    ->where('submissionID', $item['submissionID'])
                    ->where('towernumber', '1')
                    ->find();

                Log::info("firstTowerAlt: " . var_export($firstTowerAlt, true));

                if($firstTowerAlt == null){
                    $submissionName = Db::view("submission_mission")
                        ->where('submissionID', $item['submissionID'])
                        ->value('submissionName');
                } else {
                    $submissionName = $firstTowerAlt['submissionName'];
                    $heightDifference = $item['homePoint'] - $firstTowerAlt['altitude'];
                    $heightDifference = number_format($heightDifference, 1);
                }

                Log::info("submissionName : " . var_export($submissionName, true));

                Log::info("firstTowerAlt altitude : " . var_export($firstTowerAlt['altitude'], true));

                Log::info("firstTowerAlt submissionName: " . var_export($firstTowerAlt['submissionName'], true));

                $result[] = [
                    "heightDifference" => $heightDifference,
                    "snCode" => $item['snCode'],
                    "taskName" => $submissionName,
                    "taskID" => $item['submissionID'],
                ];
            }
            Log::info("result in getHeightDiff : " . var_export($result,true));
            return json(['success' => true, 'result' =>  $result]);
        }
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

                

                $snCode = $combinedData['data']['snCode'];
                Log::info("snCode: " .  $snCode);
                $combinedData = json_encode($combinedData, JSON_UNESCAPED_SLASHES);
                Log::info("combinedData: " . var_export($combinedData,true));
                Log::info("combinedData['data']['snCode'] : " . var_export($combinedData['data']['snCode'],true) );
                if($snCode!== null){
                    Log::info("send to socket_connect " );

                    $missionType = Db::table('submissionList')
                        ->where('submissionID',$data)
                        ->value('missionID');

                    if($missionType === '4' || $missionType === '5'){
                        $socket = new tcp();
                        $socket->socketSend($combinedData);
                    }

                   


                    $time = date('Y-m-d H:i:s');
                    Log::info("time: " . $time );
                    $result = Db::table('submissionList')
                    ->where('submissionID',$data)
                    ->update(['excuteDate' => $time]);
                    if($result !== false){
                        Log::info("change status success !!");
                    }else{
                        Log::info("change status fail !!");
                    }

                }else{
                    Log::info("sncode is null" );
                }

               
            }
        //return $this->fetch();
        }
    }



    public function drone_log_list(){
        Log::info("===  drone_log_list ===" );
        $data = Db::table("droneLog")
             ->order('logID desc')
             ->select();
        Log::info("data in add_task : " . var_export($data,true));
        $this->assign(compact('data'));
        return $this->fetch();
    }

    public function sync_file(){
        Log::info("===  sync_file  ===" );
        $drones = Db::table('drone')
        ->where('whetherOnline', 1)
        ->order('droneID desc')
        ->select();
        Log::info("drones : " . var_export($drones,true));
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            $controller = "sync_file";
            $len = strLen(json_encode($data));
            $data['beginDate'] = strtotime($data['beginDate']);
            $data['finishDate'] = strtotime($data['finishDate']);
            Log::info("data in downloadLogFile : " . var_export($data,true));
            $combinedData = array(
                "controller" => $controller,
                "len" => $len,
                "data" =>  $data,
            );
            $combinedData = json_encode($combinedData,JSON_UNESCAPED_SLASHES);
            Log::info("combinedData : " . var_export($combinedData,true));
            $socket = new tcp();
            $socket->socketSend($combinedData);
            return "good";
        }
        $this->assign(compact('drones'));
        return $this->fetch();
    }
    public function running_download(){
        Log::info("===  running_download  ===" );
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            $controller = "running_download";
            unset($data['beginDate']);
            unset($data['finishDate']);
            $len = strLen(json_encode($data));
            // $data['beginDate'] = strtotime($data['beginDate']);
            // $data['finishDate'] = strtotime($data['finishDate']);
            Log::info("data in downloadLogFile : " . var_export($data,true));
            $combinedData = array(
                "controller" => $controller,
                "len" => $len,
                "data" =>  $data,
            );
            $combinedData = json_encode($combinedData,JSON_UNESCAPED_SLASHES);
            Log::info("combinedData : " . var_export($combinedData,true));
            $socket = new tcp();
            $socket->socketSend($combinedData);
            return "good";
        }
        return $this->fetch();
    }




    public function photograph_test(){
        Log::info("===  photograph_test  ===" );
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            $controller = "photograph_test";
            unset($data['beginDate']);
            unset($data['finishDate']);
            $len = strLen(json_encode($data));
            // $data['beginDate'] = strtotime($data['beginDate']);
            // $data['finishDate'] = strtotime($data['finishDate']);
            Log::info("data in downloadLogFile : " . var_export($data,true));
            $combinedData = array(
                "controller" => $controller,
                "len" => $len,
                "data" =>  $data,
            );
            $combinedData = json_encode($combinedData,JSON_UNESCAPED_SLASHES);
            Log::info("combinedData : " . var_export($combinedData,true));
            $socket = new tcp();
            $socket->socketSend($combinedData);
            return "good";
        }
        return $this->fetch();
    }


    public function clear_log(){
        Log::info("===  clear_log  ===" );
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            $controller = "clear_log";
            $len = strLen(json_encode($data));
            $data['beginDate'] = strtotime($data['beginDate']);
            $data['finishDate'] = strtotime($data['finishDate']);
            Log::info("data in downloadLogFile : " . var_export($data,true));
            $combinedData = array(
                "controller" => $controller,
                "len" => $len,
                "data" =>  $data,
            );
            $combinedData = json_encode($combinedData,JSON_UNESCAPED_SLASHES);
            Log::info("combinedData : " . var_export($combinedData,true));
            $socket = new tcp();
            $socket->socketSend($combinedData);
            return "good";
        }
        return $this->fetch();
    }

    public function picture_download(){
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            $controller = "picture_download";
            $len = strLen(json_encode($data));

            $data['beginDate'] = strtotime($data['beginDate']);
            $data['finishDate'] = strtotime($data['finishDate']);

            if($data['beginDate'] === false){
                $data['beginDate'] = 0;
            }
            if($data['finishDate'] === false){
                $data['finishDate'] = 0;
            }
            Log::info("data in downloadLogFile : " . var_export($data,true));

            $combinedData = array(
                "controller" => $controller,
                "len" => $len,
                "data" =>  $data,
            );
            $combinedData = json_encode($combinedData,JSON_UNESCAPED_SLASHES);
            Log::info("combinedData : " . var_export($combinedData,true));
            $socket = new tcp();
            $socket->socketSend($combinedData);
            return "good";
        }
    }


    public function downloadLogFile() {
        Log::info("=== downloadLogFile ===" );
         if(IS_AJAX && IS_POST){
            $data=input('post.');
            Log::info("data in downloadLogFile : " . var_export($data,true));
            $filePath = Db::table("droneLog")
            ->where("logID",$data['logID'])
            ->value('logName');
        
            Log::info("filePath : " . var_export($filePath,true));
            $filePath = "C:/Users/Administrator/Desktop/txzf_server/droneLog/" . $filePath;
            // $filePath = "C:/Users/Administrator/Desktop/console_socket/console_socket/x64/Debug/droneLog/" . $filePath;
            Log::info("filePath1 : " . var_export($filePath,true));

            if(file_exists($filePath)){
                $fileContent = file_get_contents($filePath);
                $base64FileContent = base64_encode($fileContent);
                $mime = mime_content_type($filePath);
                Log::info("mime : " . var_export($mime,true));
                Log::info("fileContent : " . var_export($fileContent,true));
                Log::info("fileContent length : " . mb_strlen($fileContent));
                $file_content_utf8 = mb_convert_encoding($fileContent, 'UTF-8', 'auto');
                Log::info("file_content_utf8 length : " . mb_strlen($file_content_utf8));
                $fileName = basename($filePath);
                //Log::info("file_content_utf8 : " . var_export($file_content_utf8,true));
                Log::info("fileName : " . var_export($fileName,true));
                header("Content-Type: ".$mime);
                header("Content-Disposition: attachment; filename=" . $fileName);
                Log::info("file info get success");
                ajax_return(0,"success", ['fileContent' => $base64FileContent, 'fileName' => $fileName]);
                //ajax_return(0,"success", ['fileContent' => $file_content_utf8, 'fileName' => $fileName]);
            }else {
                ajax_return(-1,'找不到相关文件');
            }
       }
    }
       

    public function deleteLogFile() {
        if(IS_AJAX && IS_POST){
            if($this->my_permission['deleteTask']<=0){
                ajax_return(-1,'无权操作');
            }
            $id=input('id',-1);
            if($id==0){
                ajax_return(-1,'不可删除');
            }
            $has_data=Db::table('droneLog')
                ->where('logId',$id)
                ->find();
            if(empty($has_data)){
                ajax_return(-1,'未知数据');
            }
            $res=Db::table('droneLog')
                ->where('logId',$id)
                ->delete();
            if($res!==false){
                ajax_return(1,'已删除');
            }
            ajax_return(-1,'删除失败');
        }
    }
   
    public function gettasklist(){
        Log::info(" === getTaskList === " );

        if(IS_GET){
            // $data=input('post.');
            // Log::info("id : " . var_export($data,true));

            $tasks=Db::view('submission_mission')
                ->select();

            Log::info("data in getTimeForFile : " . var_export($tasks, true));
            foreach($tasks as $task){
                if($task['completeStatus'] == 3){

                    $completeTasks[]=[
                        'submissionID'=>$task['submissionID'],
                        'submissionName'=>$task['submissionName'],
                        'towerNumber'=>$task['tower_num'],
                        'excuteTime'=>$task['excuteDate'],
                    ];
                    // $jsonFilePath = './loc_file/'. $task['submissionID'] .'.json';
                    // Log::info("jsonFilePath: " . var_export($jsonFilePath, true));
                    // if(file_exists( $jsonFilePath)){
                    //     Log::info("file_exists ");
                    //     $jsonContent = json_decode(file_get_contents($jsonFilePath),true);
                    //     Log::info("jsonContent: " . var_export($jsonContent, true));
                    //     if($jsonContent){
                    //         $combinedData[] = [
                    //             'submissionID'=>$task['submissionID'],
                    //             'finishedTime'=>$task['planDate'],
                    //             'excuteTime'=>$task['excuteDate'],
                    //             'fileInfor' => $jsonContent,
                    //         ];
                    //     }
                    // }  
                } 
            }
           // Log::info("combinedData : " .var_export($combinedData,true));

           return json($completeTasks);
        }
        //$this->assign(compact('data'));
    }


    public function gettaskdetail(){
        Log::info(" === getTaskInfor === " );
        if(IS_GET){
            $data = input('id');

            $id = intval($data);

            $task=Db::table('submissionList')
                ->where('submissionID',$id)
                ->find();

            Log::info("id : " . var_export($id,true));

            $towerInfos = Db::view('submission_tower_missiontype')
            ->where('submissionID',$id)
            ->select();
            foreach($towerInfos as $towerInfo){
                $lineName = Db::table('lineList')
                 ->where('lineID',$towerInfo['lineID'])
                 ->value('lineName');

                Log::info("lineName : " . var_export($lineName,true));

                $towerDetail[] = [
                    'towerID' => $towerInfo['towerID'],
                    'towerName' => $towerInfo['towerName'],
                    'lineName' => $lineName,
                ];
            }

            Log::info("towerInfo : " . var_export($towerInfo,true));

            Log::info("task : " . var_export($task,true));

           // $jsonFilePath = './loc_file/'. $task['submissionID'] .'.json';
            //'./Desktop/console_socket/console_socket'

            $combinedData = [
                'submissionID'=>$task['submissionID'],
                'finishedTime'=>$task['planDate'],
                'excuteTime'=>$task['excuteDate'],
                'towerDetail' => $towerDetail,
                // 'jsonInfor' => $jsonContent,
            ];

            // $jsonFilePath = "C:/Users/123/Desktop/origin_php/myphp/public/ddd/" . $task['submissionID']. '.json';
            // //$jsonFilePath = '../../../console_socket/console_socket/x64/Debug/route/'. $task['submissionID'];
            // Log::info("jsonFilePath: " . var_export($jsonFilePath, true));
            // if(file_exists( $jsonFilePath)){
            //     Log::info("file_exists " . var_export(file_get_contents($jsonFilePath),true));
            //     $jsonContent = json_decode(file_get_contents($jsonFilePath),true);
            //     // Log::info("jsonContent: " . var_export($jsonContent, true));
            //     // if($jsonContent){
            //         $combinedData[] = [
            //             'submissionID'=>$task['submissionID'],
            //             'finishedTime'=>$task['planDate'],
            //             'excuteTime'=>$task['excuteDate'],
            //             'towerDetail' => $towerDetail,
            //             // 'jsonInfor' => $jsonContent,
            //         ];
            //     //}
            // }      
            Log::info("combinedData : " . var_export($combinedData, true));
            return json($combinedData);
        }
        //$this->assign(compact('data'));
    }


    public function getHeartbeatInfo()
    {
        // 检查是否是 AJAX 请求和 POST 请求
        if ($this->request->isAjax() && $this->request->isPost()) {
   
            try {
            // 创建 TCP 套接字对象
                $currentTime = time();
                Log::info("currentTime not receive : " . $currentTime);
                $socket = new tcp();

                // 接收数据
                $message = $socket->socketReceive();

                if ($message !== false) {
                    // 记录接收到的消息到日志
                    Log::info("Received message: " . $message);
                    $currentTime = time();
                    Log::info("currentTime: " . $currentTime);
                    // 返回接收到的数据给前端
                    return json(['data' => $message]);
                } else {
                    // 处理接收失败的情况
                    $currentTime = time();
                    Log::info("fail time :".$currentTime);
                    //return json(['error' => 'Failed to receive data']);
                    return ;
                }
            } catch (Exception $e) {
                $currentTime = time();
                Log::info("Exception time :" .$currentTime);
                //return json(['error' => $e->getMessage()]);
                return ;
            }
        }

        return $this->fetch();
    }


    public function index(){
        Log::info("==== index ==="); 
        if($this->my_permission['findTask']<=0){
            return $this->fetch();
        }
        Log::info("member id: " . $this->member_id); 
        $task_name=input('task_name','');
        $task_where='1=1';
        if($task_name){
            $task_where.=" and submissionName like '%{$task_name}%'";
        }

        $permission=Db::view('user_info')
                    ->where('userID', $this->member_id)
                    ->find();
        Log::info("permission : " . var_export($permission,true));
        Log::info("permissionGroupID : " . var_export($permission['permissionGroupID'],true));
        $companyID = $permission['companyID'];
        Log::info("companyID : " . var_export( $companyID,true));
        if($permission['permissionGroupID'] == 0){
            Log::info("=== superadmin ==="); 
            $tasks=Db::view('submission_mission')
            ->where($task_where)
            ->order('submissionID desc')
            ->select();
        }else if($permission['permissionGroupID'] == 3){
            Log::info("=== county admin  ==="); 

            $countyID = Db::table('companyList')
                ->where("companyID",$companyID)
                ->value('countyCompanyID');

            Log::info("countyID : " . var_export( $countyID,true));   

            $companyArray=Db::table('companyList')
                ->where("countyCompanyID", $countyID)
                ->column('companyID');
            Log::info("companyArray : " . var_export( $companyArray,true));

            $tasks = Db::view('submission_mission')
                ->join('UserList', 'submission_mission.createManID = UserList.userID')
                ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
                ->join('companyList', 'GroupList.companyID = companyList.companyID')
                ->whereIn('companyList.companyID',$companyArray)
                ->where($task_where)
                ->order('submissionID desc')
                ->select();

            Log::info("tasks : " . var_export( $tasks,true));
        
        }else if($permission['permissionGroupID'] == 1){
            Log::info("=== company admin  ==="); 
            $tasks = Db::view('submission_mission')
                ->join('UserList', 'submission_mission.createManID = UserList.userID')
                ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
                ->join('companyList', 'GroupList.companyID = companyList.companyID')
                ->where('companyList.companyID', $companyID)
                ->where($task_where)
                ->order('submissionID desc')
                ->select();
            Log::info("submissions: " . var_export($tasks, true));
        }
        else {
            Log::info("=== staff ===");
            $tasks=Db::view('submission_mission')
            ->where($task_where)
            ->where('createmanID', $this->member_id)
            ->order('submissionID desc')
            ->select();
        }
        foreach($tasks as &$task){
            Log::info("task : " . var_export($task,true));    
            if($task['missionID'] == 5){
                Log::info("missionID == 5");    
                $towerNumber=Db::table('uploadRouteSubmissionList')
                    ->where('submissionID', $task['submissionID'])
                    ->value('towerNumber');
                Log::info("towerNumber : " . var_export($towerNumber,true));    
                $task['tower_num'] = $towerNumber;
            }
        }
       
        Log::info("task change : " . var_export($tasks,true));    
        $submissionID=input('submissionID',-1);
        Log::info("submissionID change : " . var_export($submissionID,true));

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
            Log::info("towers in index : " . var_export($towers,true));  
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

        
        Log::info("permission['playLive']: " . var_export($permission['playLive'],true));  
        //P($tasks);
        $this->assign(compact('tasks','task_name','towers','submissionID','related_drones','permission'));

        $drone_sncode=input('snCode','');

        $drone_where='1=1';

        if($drone_sncode){
            $drone_where.=" and snCode like '%{$drone_sncode}%'";
        }

        if($permission['permissionGroupID'] == 0){
            Log::info("=== superadmin ==="); 
            $all_drones = Db::table('drone')
                ->where('whetherOnline', 1)
                ->where($drone_where)
                ->order('droneID desc')
                ->select();
        }else if($permission['permissionGroupID'] == 3){
            $all_drones = Db::table('drone')
            ->whereIn('companyID',$companyArray)
            ->where('whetherOnline', 1)
            ->where($drone_where)
            ->order('droneID desc')
            ->select();
        }else {
            Log::info("=== not superadmin ==="); 
            $all_drones = Db::table('drone')
                ->where('companyID',$permission['companyID'])
                ->where('whetherOnline', 1)
                ->where($drone_where)
                ->order('droneID desc')
                ->select();
        }



        $droneID=input('droneID',-1);

        if(IS_AJAX && IS_POST){
            $datas=input('post.');
            $coordinates = [];
            Log::info("datas in index: " . var_export($datas,true));
            $submissionID = $datas['submissionID'];

            $taskType = Db::table('submissionList')
                ->where('submissionID',$submissionID)
                ->value('missionID');
            
            if($taskType === "5"){
                Log::info("taskType:" . $taskType);

                $taskFilePath = Db::table('uploadRouteSubmissionList')
                    ->where('submissionID',$submissionID)
                    ->value('filePath');

                $taskFilePath = '.'. DIRECTORY_SEPARATOR . 'importRoute' . DIRECTORY_SEPARATOR . $taskFilePath;
                Log::info("taskFilePath:" . $taskFilePath);

                $extension = strtolower(pathinfo($taskFilePath, PATHINFO_EXTENSION));
                Log::info("extension:" . $extension);

                $extension = strtolower(pathinfo($taskFilePath, PATHINFO_EXTENSION));

                if($extension == 'kml'){
                    $doc = new \DOMDocument('1.0', 'utf-8');
                    $doc->load($taskFilePath);
                    Log::info(" doc create ");
                    $root = $doc->documentElement;
                    $namespace = $root->getAttribute('xmlns:wpml');
                    Log::info(" namespace: " . $namespace);
                    $wpmlNamespace = $namespace;
                    $placemarks = $doc->getElementsByTagName('Placemark');
                    foreach ($placemarks as $placemark) {
                        $point = $placemark->getElementsByTagName('Point')->item(0);
                        $coordinatesNode = $point->getElementsByTagName('coordinates')->item(0);
                        if ($coordinatesNode) {
                            $coordinatesString = $coordinatesNode->nodeValue;
                            $coordinatesArray = explode(',', $coordinatesString);
                            if (count($coordinatesArray) == 2) {
                                $longitude = (double)trim($coordinatesArray[0]);
                                $latitude = (double)trim($coordinatesArray[1]);
                                Log::info(" longitude :" . $longitude);
                                Log::info(" latitude :" . $latitude);
                                $coordinates[] = [
                                    'longitude' => $longitude,
                                    'latitude' => $latitude,
                                ];
                            }
                        }
                        Log::info("coordinates: " . var_export($coordinates,true));  
                    }
                }else if($extension == 'json'){
                    Log::info("json file path : " . $taskFilePath);  
                    $jsonString = file_get_contents($taskFilePath);
                    $data = json_decode($jsonString, true);

                    if ($data && isset($data['points']) && is_array($data['points'])) {
                        foreach($data['points'] as $point){
                            $lat = $point['lat'];
                            $lng = $point['lng'];
                            $coordinates[] = [
                                'longitude' => $lng,
                                'latitude' => $lat,
                            ];
                        }
                    }
                }
                $towers = $coordinates;
            } else{
                Log::info("submissionID: " . var_export($submissionID,true));
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
                
            }

            Log::info("towers in index : " . var_export($towers,true));
            ajax_return(1,"success", $towers);

        }
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


    public function getDroneList(){

        $permission=Db::view('user_info')
            ->where('userID', $this->member_id)
            ->find();
        $drone_where='1=1';
        $drone_sncode=input('snCode','');
        if($drone_sncode){
            $drone_where.=" and snCode like '%{$drone_sncode}%'";
        }
        if(IS_AJAX && IS_POST){
            $datas=input('post.');
            Log::info("datas in index: " . var_export($datas,true));
            if($permission['permissionGroupID'] == 0){
                Log::info("=== superadmin ==="); 
                $all_drones = Db::table('drone')
                    ->where('whetherOnline', 1)
                    ->where($drone_where)
                    ->order('droneID desc')
                    ->select();

            }else if ($permission['permissionGroupID'] == 3){
                $countyID = Db::table('companyList')
                ->where("companyID",$permission['companyID'])
                ->value('countyCompanyID');

                Log::info("countyID : " . var_export( $countyID,true));   

                $companyArray=Db::table('companyList')
                    ->where("countyCompanyID", $countyID)
                    ->column('companyID');
                Log::info("companyArray : " . var_export( $companyArray,true));

                $all_drones = Db::table('drone')
                    ->whereIn('companyID', $companyArray)
                    ->where('whetherOnline', 1)
                    ->where($drone_where)
                    ->order('droneID desc')
                    ->select();
                Log::info("all_drones : " . var_export( $all_drones,true));


            }else {
                Log::info("aaaa"); 
                $all_drones = Db::table('drone')
                    ->where('companyID',$permission['companyID'])
                    ->where('whetherOnline', 1)
                    ->where($drone_where)
                    ->order('droneID desc')
                    ->select();     
            }

            $task_name=input('task_name','');
            $task_where='1=1';
            if($task_name){
                $task_where.=" and submissionName like '%{$task_name}%'";
            }
    
            $permission=Db::view('user_info')
                        ->where('userID', $this->member_id)
                        ->find();
            Log::info("permission : " . var_export($permission,true));
            Log::info("permissionGroupID : " . var_export($permission['permissionGroupID'],true));
            $companyID = $permission['companyID'];
            Log::info("companyID : " . var_export( $companyID,true));

            if($permission['permissionGroupID'] == 0){
                Log::info("=== superadmin ==="); 
                $tasks=Db::view('submission_mission')
                ->where($task_where)
                ->order('submissionID desc')
                ->select();
    
            }else if($permission['permissionGroupID'] == 3){

                $tasks = Db::view('submission_mission')
                    ->join('UserList', 'submission_mission.createManID = UserList.userID')
                    ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
                    ->join('companyList', 'GroupList.companyID = companyList.companyID')
                    ->whereIn('companyList.companyID', $companyArray)
                    ->where($task_where)
                    ->order('submissionID desc')
                    ->select();
                Log::info("tasks : " . var_export( $tasks,true));
            }
            else if($permission['permissionGroupID'] == 1){
                Log::info("=== company admin  ==="); 
                $tasks = Db::view('submission_mission')
                    ->join('UserList', 'submission_mission.createManID = UserList.userID')
                    ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
                    ->join('companyList', 'GroupList.companyID = companyList.companyID')
                    ->where('companyList.companyID', $companyID)
                    ->where($task_where)
                    ->order('submissionID desc')
                    ->select();
                Log::info("submissions: " . var_export($tasks, true)); 
            }
            else {
                Log::info("=== staff  ===");
                $tasks=Db::view('submission_mission')
                ->where($task_where)
                ->where('createmanID', $this->member_id)
                ->order('submissionID desc')
                ->select();
            }
            ajax_return(1,"success", ['all_drones' => $all_drones, 'tasks' => $tasks]);

        }
    }
 
    public function uploadTask(){

        if(IS_AJAX && IS_POST){
            $datas=input('post.');
            Log::info("uploadTask controller send task info to drone ");
            Log::info("data in ajax : " . var_export($datas,true));
            $controller =  "index";
            foreach($datas as $data) {

                $taskInfo = Db::view('submission_mission')
                ->where('submissionID', $data['submissionID'])
                ->find();

                $snCode = $taskInfo['snCode'];
                $submissionType = $taskInfo['missionID'];
                $policyID = $taskInfo['policyID'];

                // $snCode = Db::view('submission_mission')
                // ->where('submissionID',$data['submissionID'])
                // ->value('snCode');
                // Log::info("++++ data submissionID ： " . var_export($data['submissionID'],true));

                Log::info("====== task Info : " . var_export($taskInfo,true));
                Log::info("+++++++++++++++++++snCode ： " . var_export($snCode,true));

                $networkType = Db::table('networkTypes')
                ->where('isValid',1)
                ->value('networkType');

                $timestamp = time();


                // $submissionType = Db::view('submission_mission')
                // ->where('submissionID',$data['submissionID'])
                // ->value('missionID');
                
                //chinese can't display
                Log::info("networkType: " . $networkType);
                Log::info("submissionType: " . $submissionType);
                Log::info("time: " . $timestamp);

                $rtk = 1;
                $record = 1;
                $imgwidth = 1;
                $imgheight = 1;
                // $flight = $data['flight'];

                // $policyID = Db::view('submission_mission')
                // ->where('submissionID',$data['submissionID'])
                // ->value('policyID');
                Log::info("policyID : " . $policyID);
                if($policyID == 0){ 
                    //return from origin line  
                    $return = 255;
                } else if ($policyID == 1){
                    //return need altitude
                    $returnAltitude = (double)$taskInfo['returnAltitude'];
                }else if($policyID == 2){
                    //return from other point  
                    $return = 254;
                    $landingLongtitude = (double)$taskInfo['landingLongtitude'];
                    $landingLatitude = (double)$taskInfo['landingLatitude'];
                    $landingAltitude = (double)$taskInfo['landingAltitude'];
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
                    "landingLongtitude" => isset($landingLongtitude)?$landingLongtitude:null,
                    "landingLatitude" =>  isset($landingLatitude)?$landingLatitude:null,
                    "landingAltitude" => isset($landingAltitude)?$landingAltitude:null,
                    "continueFlightTypeID" => $taskInfo['continueFlightTypeID'],
                    "continueFlightHeight" => $taskInfo['continueFlightHeight'],
                );

                // $all_data = json_encode($all_data,JSON_UNESCAPED_SLASHES);
                Log::info("all_data: " . var_export($all_data,true));
                
                $len = strLen(json_encode($all_data));

                $combinedData = array(
                    "controller" => $controller,
                    "len" => $len,
                    "data" =>  $all_data,
                );
                $snCode = $combinedData['data']['snCode'];
                Log::info("snCode: " .  $snCode);
                $combinedData = json_encode($combinedData, JSON_UNESCAPED_SLASHES);
                Log::info("combinedData: " . var_export($combinedData,true));
                if($snCode !== null){
                    Log::info("send to socket_connect " . var_export($submissionType,true));
                    if($submissionType === '4'){
                        $droneType = Db::table('drone')
                        ->where('snCode',$snCode)
                        ->value('droneType');

                        $towerFixedType = Db::view('submission_tower_missiontype')
                            ->where('submissionID', $data['submissionID'])
                            ->column('fixedtype');
                        Log::info("droneType: " . var_export($droneType,true));
                        Log::info("towerFixedType: " . var_export($towerFixedType,true));

                        if($droneType === "M3T"){
                            if(in_array("1",$towerFixedType)){
                                Log::info("tower'; list not fixed");
                                return json(['success' => false, 'message' => '根据机型修正杆塔高度后可上传任务']);
                            }
                        }else if($droneType === "M30T"){
                            if(in_array("2",$towerFixedType)){
                                return json(['success' => false, 'message' => '根据机型修正杆塔高度后可上传任务']);
                            }
                        }
                    }
                    

                    $socket = new tcp();
                    // Log::info("combinedData: " . var_export($combinedData,true));
                    $socket->socketSend($combinedData);
                    return json(['success' => true]);
                }else{
                    Log::info("sncode is null");
                }
               
                
                
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
                if($has_data['missionID'] == 5){
                    Log::info(" delete missionID : " . var_export($has_data['missionID'],true));
                    Db::table('uploadRouteSubmissionList')
                    ->where('submissionID',$id)
                    ->delete();

                    Db::table('waypointList')
                    ->where('uploadRouteSubmissionID',$id)
                    ->delete();
                }
                else {
                    Db::table('submissiontowerList')
                    ->where('submissionID',$id)
                    ->delete();
                }


                
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
            Log::info("data in add_task post: " . var_export($data,true));
            // P($data);
            // Log::info("data in ajax2 : " . var_export($data,true));

            if(empty($data['submissionName'])){
                ajax_return(-1,'请填写任务名称');
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
                $photoType = $data['photoType'];
                unset($data['additionalInput']);
                unset($data['photoType']);
                unset($data['mode']);
                unset($data['height']);
                Log::info(" data in controller 2 : " . var_export($data,true));
                $res=Db::table('submissionList')
                    ->where('submissionID',$id)
                    ->update($data);

                if($data['missionID'] == 5){
                    Log::info(" ==== mission ID is 5 ==== ");
                    $waypoints=Db::table('waypointList')
                        ->where('uploadRouteSubmissionID',$id)
                        ->where('waypointType','<>',6)
                        ->select();
                    Log::info("mission waypoints : " . var_export($waypoints,true));

                    $i = 0;
                    foreach($waypoints as $waypoint){
                        Log::info("waypoint : " . var_export($waypoint,true));
                        Log::info("photoType : " . var_export($photoType[$i],true));
                        $waypoint['waypointType'] = $photoType[$i];

                        $newWaypoint=Db::table('waypointList')
                            ->where('uploadRouteSubmissionID', $id)
                            ->where('waypointNumber', $waypoint['waypointNumber'] )
                            ->update($waypoint);

                        if($newWaypoint===false){
                            Db::rollback();
                            ajax_return(-1,'修改失败');
                        }
                        $i = $i + 1;
                    }

                    $waypointList=Db::table('waypointList')
                        ->where('uploadRouteSubmissionID',$id)
                        ->select();

                    $fileName = Db::table('uploadRouteSubmissionList')
                        ->where('submissionID',$id)
                        ->value('filePath');

                    Log::info("filePath : " . var_export($fileName,true));

                    
                    $this->modifyXMLfile($fileName, $waypointList);

                    Log::info("newWaypoint : " . var_export($newWaypoint,true));

                }
              
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

                    $mode = $data_mode[$k];
                    if($data_mode[$k] == "0"){
                        $data_height[$k] = "0";
                    }
                    $height = $data_height[$k];
                    
                    // if ($k === 0) {
                    //     $mode = null;
                    //     $height = null;
                    // } else {
                    //     Log::info(" data_height count : " . count($data_height));
                    //     Log::info(" data_mode count : " . count($data_mode));
                    //     Log::info(" sequence : " . $k); 
                    //     if($data_height[$k - 1] === '')
                    //     {
                    //         $height = null;
                    //         Log::info("==== height ====: " . $data_height[$k - 1]);
                    //     } else {
                    //         $height = $data_height[$k - 1];
                    //         Log::info("... height ... : " . $data_height[$k - 1]);
                    //     }
                    //     if($data_mode[$k - 1] === 1)
                    //     {
                    //         if(($towerNumber - $prevTowerNumber) === 1)
                    //         {
                    //             $mode = null;
                    //         } else {
                    //             $mode = $data_mode[$k - 1];
                    //         }
                    //     }else {
                    //         $mode = $data_mode[$k - 1];
                    //     }
                    //     Log::info(" data_mode : " . $mode);
                    //     Log::info(" data_height: " . $height);
                    //     $prevTowerNumber = $towerNumber;
                    // }
                    $arr[]=[
                        'submissionID'=>$submissionID,
                        'towerID'=>$towerID,
                        'createdTime'=>date('Y-m-d H:i:s'),
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
                Log::info("data in create task : " . var_export($data,true));
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

                    Log::info("k in table ids : " . $k);

                    if($k > count($data_height) - 1  && $k > count($data_mode) - 1)
                    {
                        $mode = 0;
                        $height = 0;
                    } else {
                        $mode = $data_mode[$k];
                        $height = $data_height[$k];
                    }


                    // if($k > count($data_height)  && $k > count($data_mode)){
                    //     Log::info(" k > : count($data_height)" . count($data_mode) ." k: ". $k);
                    //     $mode = "0";
                    //     $height = "0";
                    // } else {
                    //     $mode = $data_mode[$k];
                    //     $height = $data_height[$k];
                    // }
                   
                    
                    // if ($k === 0) {
                    //     $mode = null;
                    //     $height = null;
                    // } else {
                    //     Log::info(" data_height count : " . count($data_height));
                    //     Log::info(" data_mode count : " . count($data_mode));
                    //     Log::info(" sequence : " . $k); 
                    //     if($data_height[$k - 1] === '')
                    //     {
                    //         $height = null;
                    //         Log::info("==== height ====: " . $data_height[$k - 1]);
                    //     } else {
                    //         $height = $data_height[$k - 1];
                    //         Log::info("... height ... : " . $data_height[$k - 1]);
                    //     }
                    //     if($data_mode[$k - 1] === 1)
                    //     {
                    //         if(($towerNumber - $prevTowerNumber) === 1)
                    //         {
                    //             $mode = null;
                    //         } else {
                    //             $mode = $data_mode[$k - 1];
                    //         }
                    //     }else {
                    //         $mode = $data_mode[$k - 1];
                    //     }
                    //     Log::info(" data_mode : " . $mode);
                    //     Log::info(" data_height: " . $height);
                    //     $prevTowerNumber = $towerNumber;
                    // }


                    $arr[]=[
                        'submissionID'=>$submissionID,
                        'towerID'=>$towerID,
                        'createdTime'=>date('Y-m-d H:i:s'),
                        'towerNumber'=> $k + 1 ,
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
                ->order('towerNumber asc')
                ->select();
            Log::info(" towers2 : " . var_export($towers,true));


            $snCode = Db::view('submission_mission')
                ->where('submissionID',$id)
                ->value('snCode');


            $waypoints = Db::table('waypointList')
                ->order('waypointNumber asc')
                ->where('uploadRouteSubmissionID',$id)
                ->select();
    
        }
        Log::info("data in add task controller : " . var_export($data, true));

        $missions=Db::table('missionList')
            ->order('missionID asc')
            ->select();
        
        $continueFlightTypes = Db::table('continueFlightTypeList')
            ->order('continueFlightTypeID asc')
            ->select();


        // 查询 networkTypes 表中的记录
            $networkTypes = Db::table('networkTypes')
            ->select();

            $validMainNetwork = false;
            $validSubNetwork = false;
            
            // 遍历 networkTypes 记录，判断 isValid 字段的值
            foreach ($networkTypes as $networkType) {
                if ($networkType['networkType'] == 'main' && $networkType['isValid']) {
                    $validMainNetwork = true;
                }
                if ($networkType['networkType'] == 'distribution' && $networkType['isValid']) {
                    $validSubNetwork = true;
                }
            }
            
            // 查询 missionList 表中的记录
            $missionList = Db::table('missionList')
            ->select();
            
            // 根据 isValid 字段的值筛选 missionList 数据
            $filteredMissionList = array();
            
            if ($validMainNetwork && !$validSubNetwork) {
            // 只主网有效，从索引0开始获取3个元素
                $filteredMissionList = array_slice($missionList, 0, 3);
            } elseif (!$validMainNetwork && $validSubNetwork) {
            // 只配网有效，显示后三个数据

            //从索引3开始获取两个元素
            $filteredMissionList = array_slice($missionList,3,2);


            //从索引倒数第二个元素开始获取所有剩余元素
            //$filteredMissionList = array_slice($missionList,-2);

            } elseif ($validMainNetwork && $validSubNetwork) {
            // 主网和配网都有效，显示全部数据
                $filteredMissionList = array_slice($missionList, 0, 5);
            }
        
        $permission=Db::view('user_info')
            ->where('userID', $this->member_id)
            ->find();
        Log::info("permission : " . var_export($permission,true));
        Log::info("permissionGroupID : " . var_export($permission['permissionGroupID'],true));
        $companyID = $permission['companyID'];
        Log::info("companyID : " . var_export( $companyID,true));
        if($permission['permissionGroupID'] == 0){
            Log::info("=== superadmin ==="); 
            $lines=Db::table('lineList')
                ->order('lineID desc')
                ->select();
        }else if ($permission['permissionGroupID'] == 3){
            $countyID = Db::view('companyList')
            ->where("companyID",$companyID)
            ->value('countyCompanyID');

            Log::info("countyID : " . var_export( $countyID,true));   

            $companyArray=Db::table('companyList')
                ->where("countyCompanyID", $countyID)
                ->column('companyID');
            Log::info("companyArray : " . var_export( $companyArray,true));

            $lines = Db::table('lineList')
                ->join('UserList', 'lineList.createManID = UserList.userID')
                ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
                ->join('companyList', 'GroupList.companyID = companyList.companyID')
                ->whereIn('companyList.companyID', $companyArray)
                ->select();

        }else{
            Log::info("=== company admin  ==="); 
            $lines = Db::table('lineList')
                ->join('UserList', 'lineList.createManID = UserList.userID')
                ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
                ->join('companyList', 'GroupList.companyID = companyList.companyID')
                ->where('companyList.companyID', $companyID)
                ->select();
            Log::info("lines: " . var_export($lines, true)); 
        }  
    
        // if($this->member_id == 0){
        //     Log::info("=== super admin ===");
        //     $lines=Db::table('lineList')
        //     ->order('lineID asc')
        //     ->select();
        // }else {
        //     $userGroup = Db::table('userList')
        //     ->where('userID',$this->member_id)
        //     ->value('group_list_id');
        //     Log::info("userGroup: " . $userGroup); 
        //     $lines = Db::table('lineList')
        //         ->join('UserList', 'lineList.createManID = UserList.userID')
        //         ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
        //         ->where('GroupList.groupID', $userGroup)
        //         ->order('lineID desc')
        //         ->select();
        //     Log::info("tasks: " .  var_export($lines,true)); 
        //     $lines=Db::table('lineList')
        //     ->order('lineID asc')
        //     ->select();
        // }
       
        $shapes=Db::table('towerShapeList')
            ->column('towerShapeName','towerShapeNameID');

        $policys=Db::table('returnPolicy')
        ->order('policyID asc')
        ->select();

        $photoType=Db::table('photoTypeList')
        ->order('photoTypeID asc')
        ->select();
        
        $towerStatus = Db::table('towerStatus')
            ->order('id asc')
            ->select();
        
        Log::info("towers in add task aaaaa : " . var_export($towers, true)); 
        Log::info("tower status  : " . var_export($towerStatus, true)); 
        $this->assign(compact('data','continueFlightTypes','permission','towerStatus','title','shapes','missions','lines','towers','towers1','all_towers','line_ids','tower_ids','policys','snCode','returnAltitude','RTK','filteredMissionList','waypoints','photoType'));
        return $this->fetch();
    }

    public function modifyXMLfile($fileName, $waypointList){
        Log::info(" ====modifyXMLfile==== ");
        $Path = '.'. DIRECTORY_SEPARATOR . 'importRoute' . DIRECTORY_SEPARATOR . $fileName;
        Log::info("path : " . var_export($Path, true));
        $dom = new \DOMDocument;
        $dom->load($Path);
        $placemarks = $dom->getElementsByTagName('Placemark');
        $placemarkCount = $placemarks->length;
        Log::info(" placemarks : " . var_export($placemarkCount, true));

        foreach ($placemarks as $index =>$placemark) {
            $txzftypeNodes = $placemark->getElementsByTagName('txzftype');
            Log::info(" index : " . var_export($index, true));

            if($waypointList[$index]['waypointType'] == 1){
                $value = 'wz';
            }else if($waypointList[$index]['waypointType'] == 2){
                $value = 'bp';
            }else if($waypointList[$index]['waypointType'] == 3){
                $value = 'ts';
            }else if($waypointList[$index]['waypointType'] == 4){
                $value = 'tj';
            }else if($waypointList[$index]['waypointType'] == 5){
                $value = 'jj';
            }else if($waypointList[$index]['waypointType'] == 6){
                $value = 'ld';
            }else if($waypointList[$index]['waypointType'] == 7){
                $value = 'jyz';
            }else if($waypointList[$index]['waypointType'] == 8){
                $value = 'nothing';
            }
            if ($txzftypeNodes->length > 0) {
                Log::info("=== node already exist === ");
                $txzftypeNode = $txzftypeNodes->item(0);
           
                $txzftypeNode->nodeValue = $value;
            } else {
                Log::info("=== create node === ");

                $newTxzftypeNode = $dom->createElement('txzftype', $value);
                $placemark->appendChild($newTxzftypeNode);
            }
        }
        $dom->save($Path);
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

    public function logout(){
        Log::info("==== logout ==== " ); 
        Session::delete($this->member_id);
        cookie(null);
        session(null);
        return json(['status'=> 'succuss','message'=>'logout']);
    }
    
    public function login(){
        if(IS_POST){
            $data = input();
            if (empty($data['username'])) {
                ajax_return(-1, '请输入账号');
            }
            if (empty($data['password'])) {
                ajax_return(-1, '请输入密码');
            }
            $has_user = Db::table('UserList')
                ->where(['username'=>$data['username'],'passWord'=>$data['password']])
                ->find();
            if(empty($has_user)){
                ajax_return(-1,'账号或密码错误');
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

        $permission=Db::view('user_info')
        ->where('userID', $this->member_id)
        ->find();
        Log::info("permission : " . var_export($permission,true));
        Log::info("permissionGroupID : " . var_export($permission['permissionGroupID'],true));
        $companyID = $permission['companyID'];
        Log::info("companyID : " . var_export( $companyID,true));
        if($permission['permissionGroupID'] == 0){
            Log::info("=== superadmin ==="); 
            $lines=Db::table('lineList')
                ->where($line_where)
                ->order('lineID desc')
                ->select();
        } else if ($permission['permissionGroupID'] == 3){
            $countyID = Db::view('companyList')
            ->where("companyID",$companyID)
            ->value('countyCompanyID');

            Log::info("countyID : " . var_export( $countyID,true));   

            $companyArray=Db::table('companyList')
                ->where("countyCompanyID", $countyID)
                ->column('companyID');
            Log::info("companyArray : " . var_export( $companyArray,true));

            $lines = Db::table('lineList')
            ->join('UserList', 'lineList.createManID = UserList.userID')
            ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
            ->join('companyList', 'GroupList.companyID = companyList.companyID')
            ->whereIn('companyList.companyID', $companyArray)
            ->order('lineID desc')
            ->select();
        }else{
            Log::info("=== company admin  ==="); 
            $lines = Db::table('lineList')
                ->join('UserList', 'lineList.createManID = UserList.userID')
                ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
                ->join('companyList', 'GroupList.companyID = companyList.companyID')
                ->where('companyList.companyID', $companyID)
                ->order('lineID desc')
                ->select();
            Log::info("lines: " . var_export($lines, true)); 
        }  



        // if($this->member_id == 0){
        //     Log::info(" super admin : " . $this->member_id);
        //     $lines=Db::table('lineList')
        //     ->where($line_where)
        //     ->order('lineID desc')
        //     ->select();
        // } else {

            // $userGroup = Db::table('userList')
            // ->where('userID',$this->member_id)
            // ->value('group_list_id');

            // Log::info("userGroup: " . $userGroup); 

            // $lines = Db::table('lineList')
            //     ->join('UserList', 'lineList.createManID = UserList.userID')
            //     ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
            //     ->where('GroupList.groupID', $userGroup)
            //     ->order('lineID desc')
            //     ->select();
            
            // Log::info("tasks: " .  var_export($lines,true)); 

            // $lines=Db::table('lineList')
            // ->where($line_where)
            // ->where("createManID",$this->member_id)
            // ->order('lineID desc')
            // ->select();
        //}
        

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

        $permission=Db::view('user_info')
        ->where('userID', $this->member_id)
        ->find();

        $companys=Db::table('companyList')
            ->order('companyID asc')
            ->select();
        $towerType = Db::table("towerShapeList")
            ->order('towerShapeNameID asc')
            ->select();
        $this->assign(compact('data','title','companys','towerType','permission'));
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
            // $location=$data['location'];
            // $location=str_replace('，',',',$location);
            // $location=explode(',',$location);
            // unset($data['location']);
            // $data['longitude']=$location[0];
            // $data['latitude']=$location[1];
            // $data['altitude']=$location[2];
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
            //$po=bindec($po);
            $bitdata = 0;
            if ($po[0] == 1) {
                $bitdata |= 1;
            }
            else {
                $bitdata &= 0;
            }
            if ($po[3] == 1) {
                $bitdata |= 2;
            }
            else {
                $bitdata &= 0xfffd;
            } 
            if ($po[1] == 1) {
                $bitdata |= 4;
            }
            else {
                $bitdata &= 0xfffb;
            }
            if ($po[2] == 1) {
                $bitdata |= 8;
            }
            else {
                $bitdata &= 0xfff7;
            }
           // $po = $bitdata;
            Log::info("po : " . var_export($bitdata, true));
            //P($po);
            $data['photoPosition']=$bitdata;
            Log::info("data in add tower : " . var_export($data, true));
            //P($data);
            if($id>=0){
                if($this->my_permission['modifyBasicData']<=0){
                    ajax_return(-1,'无权操作');
                }
                $data['insulatorNum'] = $data['insulatorNum']*10;
                Log::info("data['insulatorNum'] " .$data['insulatorNum']);
                $has_data=Db::table('towerList')
                    ->where('towerID',$id)
                    ->find();
                if(empty($has_data)){
                    ajax_return(-1,'未知杆塔');
                }
       
                Log::info("has_data : " . var_export($has_data, true));
                $data['towerNumber'] = $has_data['towerNumber'];
                Log::info("towerNumber : " . var_export($data['towerNumber'], true));
                Log::info("data : " . var_export($data, true));
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
                $data['insulatorNum'] = $data['insulatorNum']*10;
                Log::info("data['insulatorNum'] " .$data['insulatorNum']);
                $data['createdTime']=date('Y-m-d H:i:s');
                $data['createManID']=$this->member_id;
          
                $towerNumber = Db::table('towerList')
                ->where('lineID', $data['lineID'])
                ->count();
            
                $data['towerNumber']= $towerNumber + 1;
    
                Log::info("towerNumber : " . var_export($towerNumber, true));
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
           // $data['insulatorNum'] = $data['insulatorNum']*10;
            //Log::info("data['insulatorNum'] " .$data['insulatorNum']);
            Log::info("data id > 0 : " .var_export($data,true));
            if($data["towerShapeID"] == 34){
              //  $shapeInCompass = decbin($data["photoPosition"]);
                $string1 = str_pad(decbin($data["photoPosition"]), 4, '0', STR_PAD_LEFT);
                //$string = $shapeInCompass; 
                Log::info("string origin : " .$string1);
                $string[] = 0;
                if($string1[3] == 1){
                    $string[0] = 1;
                }
                Log::info("string  3 : " .$string);
                if ($string1[2] == 1){
                    $string[3] = 1;
                }
                Log::info("string 2 : " .$string);
                if ($string1[0] == 1){
                    $string[2] = 1;

                }
                if ($string1[1] == 1){
                    $string[1] = 1;
                }
                $string = implode($string);
               
                Log::info("string : " .$string);
            }
            else{
                $shapeInCompass=Db::table('towerShapeList')
                ->where('towerShapeNameID', $data['towerShapeID'])
                ->column('shapeInCompass');

                Log::info("shapeInCompass : " .$shapeInCompass);

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

                $string = implode('', $shapeInCompass); 
                Log::info("string : " .$string);
             
            }
         
            Log::info("shapeInCompass: " .var_export($shapeInCompass,true));
            // $data['po']=(string)decbin($data['photoPosition']);
            // $data['po']=str_split($data['po']);

            //combine element in array to form a string 
         
            // $data['shapeInCompass'] = $string;
            // $data['po']=(string)decbin($data['photoPosition']);
            $data['po']=str_split($string);
            // $data['po']=str_split($data['po']);
            //P($data);
            $title='杆塔详情';
          
        }
        Log::info("data : " .var_export($data,true));
        //$data['insulatorNum'] = $data['insulatorNum']*10;
       // Log::info("data['insulatorNum'] " .$data['insulatorNum']);

        $lines=Db::table('lineList')
            ->order('lineID asc')
            ->select();

        $permission=Db::view('user_info')
            ->where('userID', $this->member_id)
            ->find();
        $companyID = $permission['companyID'];

        Log::info("permission : " . var_export($permission,true));
        Log::info("companyID : " . var_export( $companyID,true));
        Log::info("permissionGroupID : " . var_export($permission['permissionGroupID'],true));

        if($permission['permissionGroupID'] == 0){
            Log::info("=== superadmin ==="); 
            $shapes=Db::table('towerShapeList')
                ->order('towerShapeNameID asc')
                ->select();
        } else if ($permission['permissionGroupID'] == 3){
            $countyID = Db::view('companyList')
            ->where("companyID",$companyID)
            ->value('countyCompanyID');

            Log::info("countyID : " . var_export( $countyID,true));   

            $companyArray=Db::table('companyList')
                ->where("countyCompanyID", $countyID)
                ->column('companyID');
            Log::info("companyArray : " . var_export( $companyArray,true));

            $query = Db::table('towerShapeList')
            ->join('UserList', 'towerShapeList.createManID = UserList.userID')
            ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
            ->join('companyList', 'GroupList.companyID = companyList.companyID')
            ->whereIn('companyList.companyID', $companyID)
            ->column('towerShapeNameID');

            $shapes1 =  Db::table('towerShapeList')
                ->where('createManID', 0)
                ->select();
            Log::info("shapes1 : " .var_export($shapes1,true)); 
            $shapes2 =  Db::table('towerShapeList')
                ->whereIN('towerShapeNameID', $query)
                ->select();
            Log::info("shapes2 : " .var_export($shapes2,true));
            $shapes = array_merge($shapes1,$shapes2);
            Log::info("shapes : " .var_export($shapes,true));

        }else{
            Log::info("company admin and staff"); 
            $query = Db::table('towerShapeList')
                ->join('UserList', 'towerShapeList.createManID = UserList.userID')
                ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
                ->join('companyList', 'GroupList.companyID = companyList.companyID')
                ->where('companyList.companyID', $companyID)
                ->column('towerShapeNameID');
            Log::info("query : " .var_export($query,true)); 
            $shapes1 =  Db::table('towerShapeList')
                ->where('createManID', 0)
                ->select();
            Log::info("shapes1 : " .var_export($shapes1,true)); 
            $shapes2 =  Db::table('towerShapeList')
                ->whereIN('towerShapeNameID', $query)
                ->select();
            Log::info("shapes2 : " .var_export($shapes2,true));
            $shapes = array_merge($shapes1,$shapes2);
            Log::info("shapes : " .var_export($shapes,true));
        }  

        // $shapes=Db::table('towerShapeList')
        //     ->order('towerShapeNameID asc')
        //     ->select();
        
        Log::info("shapes : " .var_export($shapes,true));  
        Log::info("data : " .var_export($data,true));  
        $lineID=input('lineID',-1);

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
        $this->assign(compact('data','title','lines','shapes','lineID','permission'));
        return $this->fetch();
    }
    public function update_line_alt(){
        Log::info(" ==== update_line_alt ==== ");
        if(IS_AJAX && IS_POST){
            Log::info("Post data: " .var_export($_POST,true));
            $towerID = $_POST['towerID'];
            $height = $_POST['height'];
            $towerAlt = $_POST['towerAlt'];
            $selectedType = $_POST['selectedType'];
            Log::info("towerID: " . var_export($towerID, true));
            Log::info("height: " . var_export($height, true));
            Log::info("towerAlt: " . var_export($towerAlt, true));
            Log::info("selectedType: " . var_export($selectedType, true));
            if(isset($towerID) && isset($height)&&($height !== '')){

                $diff = $height - $towerAlt;

                $lineID = Db::table('towerList')
                    ->where('towerID',$towerID)
                    ->value('lineID');
                Log::info("lineID: " . var_export($lineID, true));

                    // 通过lineID获取需要更新的记录
                $towers = Db::table('towerList')->where('lineID', $lineID)->select();
                $updateCount = 0;
        
                    // 遍历所有符合条件的记录并更新
                foreach ($towers as $tower) {
                    $towerAlt = $tower['altitude'];
                    $newAlt = $towerAlt + $diff;
        
                    $res = Db::table('towerList')
                        ->where('towerID', $tower['towerID'])
                        ->update(['altitude' => $newAlt,
                                'fixedtype' => $selectedType]);
        
                    if ($res) {
                        $updateCount++;
                    }
                }
        
                if ($updateCount > 0) {
                    Log::info("更新成功, 更新记录数: " . $updateCount);
                    return json(['success' => true, 'message' => '高度更新成功', 'updateCount' => $updateCount]);
                } else {
                    Log::info("没有记录被更新");
                    return json(['success' => false, 'message' => '没有记录被更新']);
                }

            } else {
                Log::info("无效的输入");
                return json(['success' => false, 'message' => '无效的输入']);
            }


        }
    }



    public function update_tower_alt(){
        Log::info(" ==== update_tower_alt ==== ");
        if(IS_AJAX && IS_POST){
            Log::info("Post data: " .var_export($_POST,true));
            $towerID = $_POST['towerID'];
            $height = $_POST['height'];
            $towerAlt = $_POST['towerAlt'];
            $selectedType = $_POST['selectedType'];
            Log::info("towerID: " . var_export($towerID, true));
            Log::info("height: " . var_export($height, true));
            Log::info("towerAlt: " . var_export($towerAlt, true));
            Log::info("selectedType: " . var_export($selectedType, true));
            if(isset($towerID) && isset($height) && ($height !== '')){
                $diff = $height - $towerAlt;
                Log::info("diff: " . var_export($diff, true));
                $res = Db::table('towerList')
                    ->where('towerID',$towerID)
                    ->update(['altitude' => ($towerAlt + $diff),
                            'fixedtype' => $selectedType]);
                if($res){
                    return json(['success' => true, 'message' => '高度更新成功']);
                }else {
                    return json(['success' => false, 'message' => '高度更新失败']);
                }

            }else{
                return json(['success' => false, 'message' => '无效输入']);
            }
        }
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
                $updateTowerNumber = Db::table('towerList')
                                    ->where('lineID', $has_data['lineID'])
                                    ->where('towerNumber','>', $has_data['towerNumber'])
                                    ->setDec('towerNumber',1);
                if($updateTowerNumber){
                    ajax_return(1,'已删除');
                }else{
                    ajax_return(-1,'删除失败');
                }
            }else{
                ajax_return(-1,'删除失败');
            }


           
        }
    }

    public function uploadKml(){
        Log::info(" ==== uploadKml ==== ");
        if(IS_AJAX && IS_POST){
            //P($_FILES);
            if($_FILES['file']['size']<=0){
                ajax_return(-1,'请正确上传文件');
            }
            
            if($_FILES['file']['size']>0){
                $file = request()->file('file');
                $fileName = $file->getInfo('name');
                Log::info("name : " . var_export($file->getInfo('name'),true));
                $savePath = '.' . DIRECTORY_SEPARATOR .'importRoute' .DIRECTORY_SEPARATOR;
                $saveName = 'importRoute_'.date('ymd_His').'.'.pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);

                $extension = strtolower(pathinfo($file->getInfo('name'), PATHINFO_EXTENSION));
                if($extension === 'kmz'){
                    Log::info("extension :" .var_export($extension,true));
                    $saveName = 'loc_txt_'.date('ymd_His').'.'.'zip';
                    Log::info("saveName :" .var_export($saveName,true));
                    $kmlPath = $savePath . $saveName;
                    $result = $file->validate(['ext'=>'kml,KML,kmz,KMZ'])->move($savePath, $saveName);
                    Log::info("result==== :" .var_export($kmlPath,true));               
                }else{
                    $result = $file->validate(['ext'=>'kml,KML,json,JSON,zip,kmz,KMZ'])->move($savePath, $saveName);
                }



              
                if($result){
                    $extension = strtolower(pathinfo($file->getInfo('name'), PATHINFO_EXTENSION));
                    Log::info("extension: " .$extension);
                    if ($extension == 'kml') {
                        $kmlFilePath=$savePath.$saveName;
                        $coordinates = $this->parseKML($kmlFilePath,$fileName,$saveName);
                    } elseif ($extension == 'json') {
                        $jsonFilePath=$savePath.$saveName;
                        $this->parseJSON($jsonFilePath, $fileName, $saveName);
                    } elseif($extension == 'zip' || $extension == 'kmz'){
                        $zipFilePath=$savePath.$saveName;
                        $this->parseZip($zipFilePath, $fileName, $saveName);
                    }
                   
                }else{
                    ajax_return(-1,'Excel文件保存出错');
                }
            }
            ajax_return(1,'操作成功');
        }
    }

    function parseZip($zipFilePath, $fileName, $saveName){
        Log::info("parse zip");
        Log::info("fileName " . $fileName);
        $taskName = pathinfo($fileName, PATHINFO_FILENAME);
        $zip = new ZipArchive;
        Log::info("saveName " . $saveName);

        $unzipPath = dirname($zipFilePath);
        $unzipName = pathinfo($saveName, PATHINFO_FILENAME);
        Log::info("unzipPath " . $unzipPath);

        $unzipPath1 = $unzipPath . DIRECTORY_SEPARATOR .$unzipName;
        Log::info("unzipPath1 " . $unzipPath1);
        if($zip->open($zipFilePath)){
            $zip->extractTo($unzipPath1);
            $zip->close();
            $this->recursiveDirTraversal($unzipPath1, $taskName);
        }
    }

    function recursiveDirTraversal($unzipPath1,$taskName){
        $dir = new \DirectoryIterator($unzipPath1);
        Log::info("unzipPath1 " . $unzipPath1);
        $saveName = pathinfo($unzipPath1, PATHINFO_FILENAME);
        Log::info("saveName " . $saveName);
        // global $TaskPath;
        // if($TaskPath === null){
        //     $TaskPath = substr(strrchr($unzipPath1,'\\'),1);
        //     Log::info("TaskPath " . $TaskPath);
        // }
        foreach($dir as $fileInfo){
            Log::info("fileInfo " . $fileInfo);

            if(!$fileInfo->isDot()){
                $filePath = $fileInfo->getPathname();
                Log::info("filePath " . $filePath);
                if($fileInfo->isDir()){
                    $this->recursiveDirTraversal($filePath, $taskName);
                }else{
                    $extension = strtolower(pathinfo($filePath,PATHINFO_EXTENSION));
                    if($extension === 'kml'){
                        Log::info("KML NAME  " . $filePath);
                        // Log::info('position: ' . strpos($filePath, 'importRoute'. DIRECTORY_SEPARATOR));
                        $TaskPath = substr($filePath,14);
                        Log::info("TaskPath " . $TaskPath);
                        Db::startTrans();
                        $doc = new \DOMDocument('1.0', 'utf-8');
                        $doc->load($filePath);
                        Log::info(" doc create ");
                        $root = $doc->documentElement;
                        $namespace = $root->getAttribute('xmlns:wpml');
                        Log::info(" namespace: " . $namespace);


                        $wpmlNamespace = $namespace;
                        $placemarks = $doc->getElementsByTagName('Placemark');
                        $placemarkCount = $placemarks->length;
                        Log::info(" placemarkCount : ".$placemarkCount);
                
                        $permission=Db::view('user_info')
                        ->where('userID', $this->member_id)
                        ->find();
                        $companyID = $permission['companyID'];
                        Log::info("companyID : " . var_export( $companyID,true));
                
                        $newTask['submissionName'] = $taskName;
                        $newTask['missionID'] = 5;
                        $newTask['taskID'] = -1;
                        $newTask['flightHeight'] = 20;
                        $newTask['policyID'] = 0;
                        $newTask['createdTime'] = date('Y-m-d H:i:s');
                        $newTask['createManID'] = $this->member_id;
                        Log::info("newTask : " . var_export($newTask,true));
                
                        $newSubmissionId=Db::table('submissionList')
                        ->insertGetId($newTask);
                
                        if($newSubmissionId===false){
                            Db::rollback();
                            ajax_return(-1,'添加失败');
                        }
                        Log::info("newSubmissionId : " . var_export($newSubmissionId,true));
                
                
                
                        $uploadRouteSubmission['submissionID'] = $newSubmissionId;
                        $uploadRouteSubmission['filePath'] = $TaskPath;
                        $uploadRouteSubmission['towerNumber'] =  $placemarkCount;
                
                        $relateSubmissionIDFilePath=Db::table('uploadRouteSubmissionList')
                        ->insertGetId($uploadRouteSubmission);
                
                
                        if($relateSubmissionIDFilePath===false){
                            Db::rollback();
                            ajax_return(-1,'添加失败');
                        }
                
                        $waypointNumber = 1;
                        foreach ($placemarks as $placemark) {
                            $actionNode = $placemark->getElementsByTagNameNS($wpmlNamespace, 'actionGroup')->item(0);
                            if($actionNode){
                                $action = 1;
                                $photo = $placemark->getElementsByTagNameNS($wpmlNamespace, 'orientedFilePath')->item(0)->textContent;
                                $waypointPhoto = $unzipPath1 . DIRECTORY_SEPARATOR . 'res' .DIRECTORY_SEPARATOR. 'image' . DIRECTORY_SEPARATOR . $photo;
                                Log::info(" waypointPhoto :" . $waypointPhoto);
                                $waypointPhoto = substr($waypointPhoto, 1);
                            }else {
                                $action = 6;
                                $waypointPhoto = null;
                            }

                            Log::info(" action :" . $action);
                            $waypoint['uploadRouteSubmissionID']  = $newSubmissionId;
                            $waypoint['waypointNumber'] = $waypointNumber;
                            $waypoint['waypointType'] = $action;
                            $waypoint['waypointPhoto'] = $waypointPhoto;
                            $waypoint['waypointName'] = "航点" . $waypointNumber;
                
                            Log::info("waypointName " . var_export($waypoint['waypointName'],true));
                
                            $newWaypoint=Db::table('waypointList')
                                    ->insertGetId($waypoint);
                
                            Log::info("newWaypoint : " . var_export($newWaypoint,true));
                            $waypointNumber = $waypointNumber + 1;
            
                            if($newWaypoint===false){
                                Db::rollback();
                                ajax_return(-1,'添加失败');
                            }
                        }

                        Db::commit();
                    }
                }
            }
        }
    }

    function parseJSON($jsonFilePath, $fileName, $saveName){
        Log::info(" ==== parseJSON ==== ");
        $jsonString = file_get_contents($jsonFilePath);
        $data = json_decode($jsonString, true);

        $taskname = $data['taskname'];
        Log::info("taskname : " . var_export($taskname, true));

        Db::startTrans();

        $permission=Db::view('user_info')
            ->where('userID', $this->member_id)
            ->find();

        $companyID = $permission['companyID'];
        Log::info("companyID : " . var_export($companyID,true));

        $newTask['submissionName'] = $taskname;
        $newTask['missionID'] = 5;
        $newTask['taskID'] = -1;
        $newTask['flightHeight'] = 20;
        $newTask['policyID'] = 0;
        $newTask['createdTime'] = date('Y-m-d H:i:s');
        $newTask['createManID'] = $this->member_id;
        Log::info("newTask : " . var_export($newTask,true));

        $newSubmissionId=Db::table('submissionList')
        ->insertGetId($newTask);

        if($newSubmissionId===false){
            Db::rollback();
            ajax_return(-1,'添加失败');
        }
        Log::info("newSubmissionId : " . var_export($newSubmissionId,true));

        if ($data && isset($data['points']) && is_array($data['points'])) {
            $pointsCount = count($data['points']);
            Log::info("pointsCount : " . var_export($pointsCount, true));
            $i = 1;
            foreach($data['points'] as $point){
                $waypointName = $point['towerName'];
                Log::info("towerName : " . var_export($waypointName, true));
                Log::info("yawPitchArray : " . var_export($point['yawPitchArray'], true));

                //if(isset($point['yawPitchArray'])){
                if(empty($point['yawPitchArray'])){
                    Log::info(" == yawPitchArray unexist == ");
                    $photoTypeID = 6;
                } else {
                    Log::info(" == yawPitchArray == ");
                    $photoType = $point['yawPitchArray'][0]['keyName'];
                    Log::info("photoType : " . var_export($photoType, true));
                    if(strpos($photoType,"挂点") !== false){
                        $photoTypeID = 5;
                    }else if(strpos($photoType,"地线") !== false){
                        $photoTypeID = 4;
                    }else if(strpos($photoType,"塔全貌") !== false){
                        $photoTypeID = 3;
                    }else if(strpos($photoType,"杆号牌") !== false){
                        $photoTypeID = 2;
                    }else {
                        $photoTypeID = 1;
                    }

                }
                Log::info("photoTypeID : " . var_export($photoTypeID, true));
                $newWaypoint['uploadRouteSubmissionID'] = $newSubmissionId;
                $newWaypoint['waypointNumber'] = $i;
                $newWaypoint['waypointType'] = $photoTypeID;
                $newWaypoint['waypointName'] = "航点" . $i;

                $waypointList = Db::table('waypointList')
                    ->insertGetId($newWaypoint);

                if($waypointList===false){
                    Db::rollback();
                    ajax_return(-1,'添加失败');
                }

                $i = $i + 1;
            }
        }




        $uploadRouteSubmission['submissionID'] = $newSubmissionId;
        $uploadRouteSubmission['filePath'] = $saveName;
        $uploadRouteSubmission['towerNumber'] =  $pointsCount;

        $relateSubmissionIDFilePath=Db::table('uploadRouteSubmissionList')
        ->insertGetId($uploadRouteSubmission);

        if($relateSubmissionIDFilePath===false){
            Db::rollback();
            ajax_return(-1,'添加失败');
        }

        Db::commit();
    }



    function parseKML($kmlFilePath,$fileName,$saveName) {
        Log::info(" ==== parseKML ==== ");
        $coordinates = [];
        Db::startTrans();
        $doc = new \DOMDocument('1.0', 'utf-8');
        $doc->load($kmlFilePath);
        Log::info(" doc create ");
        $root = $doc->documentElement;
        $namespace = $root->getAttribute('xmlns:wpml');
        Log::info(" namespace: " . $namespace);

        $wpmlNamespace = $namespace;
        $placemarks = $doc->getElementsByTagName('Placemark');
        $placemarkCount = $placemarks->length;
        Log::info(" placemarkCount : ".$placemarkCount);

        $permission=Db::view('user_info')
        ->where('userID', $this->member_id)
        ->find();
        $companyID = $permission['companyID'];
        Log::info("companyID : " . var_export( $companyID,true));

        $newTask['submissionName'] = $fileName;
        $newTask['missionID'] = 5;
        $newTask['taskID'] = -1;
        $newTask['flightHeight'] = 20;
        $newTask['policyID'] = 0;
        $newTask['createdTime'] = date('Y-m-d H:i:s');
        $newTask['createManID'] = $this->member_id;
        Log::info("newTask : " . var_export($newTask,true));

        $newSubmissionId=Db::table('submissionList')
        ->insertGetId($newTask);

        if($newSubmissionId===false){
            Db::rollback();
            ajax_return(-1,'添加失败');
        }
        Log::info("newSubmissionId : " . var_export($newSubmissionId,true));



        $uploadRouteSubmission['submissionID'] = $newSubmissionId;
        $uploadRouteSubmission['filePath'] = $saveName;
        $uploadRouteSubmission['towerNumber'] =  $placemarkCount;

        $relateSubmissionIDFilePath=Db::table('uploadRouteSubmissionList')
        ->insertGetId($uploadRouteSubmission);


        if($relateSubmissionIDFilePath===false){
            Db::rollback();
            ajax_return(-1,'添加失败');
        }

        $waypointNumber = 1;
        foreach ($placemarks as $placemark) {
           // $point = $placemark->getElementsByTagName('Point')->item(0);

            $actionNode = $placemark->getElementsByTagNameNS($wpmlNamespace, 'actionGroup')->item(0);
            if($actionNode){
                $action = 1;
                $txzfTypeNode = $placemark->getElementsByTagName('txzftype')->item(0);
                if ($txzfTypeNode) {
                    $txzfType = $txzfTypeNode->nodeValue;
                    Log::info("txzftype: " . $txzfType);
                    if($txzfType == 'ts'){
                        $action = 3;
                    }else if($txzfType == 'tj'){
                        $action = 4;
                    }else if($txzfType == 'jj'){
                        $action = 5;
                    }else if($txzfType == 'jyz'){
                        $action = 7;
                    }else if($txzfType == 'bp'){
                        $action = 2;
                    }else if($txzfType == 'nothing'){
                        $action = 8;
                    }
                } else {
                    Log::info("txzftype element not found");
                }

                Log::info(" action :" . $action);
            }else {
                $action = 6;
                Log::info(" action is null");
            }

            $waypoint['uploadRouteSubmissionID']  = $newSubmissionId;
            $waypoint['waypointNumber'] = $waypointNumber;
            $waypoint['waypointType'] = $action;
            $waypoint['waypointName'] = "航点" . $waypointNumber;

            Log::info("waypointName " . var_export($waypoint['waypointName'],true));

            $newWaypoint=Db::table('waypointList')
                    ->insertGetId($waypoint);

            Log::info("newWaypoint : " . var_export($newWaypoint,true));
            $waypointNumber = $waypointNumber + 1;


            if($newWaypoint===false){
                Db::rollback();
                ajax_return(-1,'添加失败');
            }

        }

        Db::commit();


        // $newLine['lineName'] = $fileName;
        // $newLine['companyID'] = $companyID;
        // $newLine['createdTime'] = date('Y-m-d H:i:s');
        // $newLine['createManID'] = $this->member_id;

        // Log::info("newLine : " . var_export( $newLine,true));
        // $res=Db::table('lineList')
        // ->insertGetId($newLine);
        // if($res===false){
        //     Db::rollback();
        //     ajax_return(-1,'添加失败');
        // }
        // Log::info("res : " . var_export($res,true));


        // $towerNumber = 1;
        // foreach ($placemarks as $placemark) {
        //     $point = $placemark->getElementsByTagName('Point')->item(0);
        //     $coordinatesNode = $point->getElementsByTagName('coordinates')->item(0);

        //     $indexNode = $placemark->getElementsByTagNameNS($wpmlNamespace, 'index')->item(0);
        //     if($indexNode){
        //         $index = $indexNode->nodeValue;
        //         Log::info(" $index :" . $index);
        //     }

        //     $gimbalPitchAngleNode = $placemark->getElementsByTagNameNS($wpmlNamespace, 'gimbalPitchAngle')->item(0);
        //     if($gimbalPitchAngleNode){
        //         $gimbalPitchAngle = $gimbalPitchAngleNode->nodeValue;
        //         Log::info(" gimbalPitchAngle :" . $gimbalPitchAngle);
        //     }else {
        //         $gimbalPitchAngle = 0;
        //         Log::info(" useGlobalSpeed is null");
        //     }

        //     $heightNode = $placemark->getElementsByTagNameNS($wpmlNamespace, 'height')->item(0);
        //     if($heightNode){
        //         $height = $heightNode->nodeValue;
        //         Log::info(" height :" . $height);
        //     }else {
        //         $height = 0;
        //         Log::info(" height is null");
        //     }


        //     $focalLengthNode = $placemark->getElementsByTagNameNS($wpmlNamespace, 'focalLength')->item(0);
        //     if($focalLengthNode){
        //         $focalLength = $focalLengthNode->nodeValue;
        //         Log::info(" focalLength :" . $focalLength);
        //     }else {
        //         Log::info(" focalLength is null");
        //         $focalLength = 0;
        //     }
         
        //     $useGlobalSpeedNode = $placemark->getElementsByTagNameNS($wpmlNamespace, 'useGlobalSpeed')->item(0);
        //     if($useGlobalSpeedNode){
        //         $useGlobalSpeed = $useGlobalSpeedNode->nodeValue;
        //         Log::info(" useGlobalSpeed :" . $useGlobalSpeed);
        //     }else {
        //         Log::info(" useGlobalSpeed is null");
        //         $useGlobalSpeed = 0;
        //     }

        //     if ($coordinatesNode) {
        //         $coordinatesString = $coordinatesNode->nodeValue;
        //         $coordinatesArray = explode(',', $coordinatesString);
        //         Log::info(" count :" . count($coordinatesArray));
        //         if (count($coordinatesArray) == 2) {
        //             $longitude = (double) trim($coordinatesArray[0]);
        //             $latitude = (double) trim($coordinatesArray[1]);
        //             Log::info(" longitude :" . $longitude);
        //             Log::info(" latitude :" . $latitude);
        //             $coordinates[] = [
        //                 'longitude' => $longitude,
        //                 'latitude' => $latitude,
        //             ];
        //         }
        //     }

        //     $newTower['towerName'] = $index;
        //     $newTower['lineID'] = $res;
        //     $newTower['towerNumber'] = $towerNumber;
        //     $newTower['photoPosition'] = 0;
        //     $newTower['towerShapeID'] = 34;
        //     $newTower['longitude'] =  $longitude;
        //     $newTower['latitude'] =  $latitude;
        //     $newTower['altitude'] = (double)$height;
        //     $newTower['insulatorNum'] = $focalLength;
        //     $newTower['createdTime'] = date('Y-m-d H:i:s');
        //     $newTower['createManID'] = $this->member_id;
        //     Log::info("newTower : " . var_export($newTower,true));

        //     $towerID=Db::table('towerList')
        //         ->insertGetId($newTower);
        //     if($towerID===false){
        //         Db::rollback();
        //         ajax_return(-1,'添加失败');
        //     }
        //     Log::info("insert towerList success ");

        //     $relateTowerAndSubmission['submissionID'] = $newSubmissionId;
        //     $relateTowerAndSubmission['towerID'] = $towerID;
        //     $relateTowerAndSubmission['createdTime'] = date('Y-m-d H:i:s');
        //     $relateTowerAndSubmission['createManID'] = $this->member_id;
        //     $relateTowerAndSubmission['towerNumber'] = $towerNumber;
        //     if($towerNumber !== 1){
        //         $relateTowerAndSubmission['mode'] = 0;
        //     }
        //     Log::info("relateTowerAndSubmission : " . var_export($relateTowerAndSubmission,true));

        //     $relateTowerAndSubmissionID=Db::table('submissiontowerList')
        //         ->insertGetId($relateTowerAndSubmission);
        //     if($relateTowerAndSubmissionID === false){
        //         Db::rollback();
        //         ajax_return(-1,'添加失败');
        //     }
        
        //     Log::info("insert submissiontowerList success ");
        //     Db::commit();
        //     $towerNumber = $towerNumber + 1;
        // }
    
        return $coordinates;
    }


    public function import(){
        Log::info(" ==== import ==== ");
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
                    Log::info(" content ". var_export( $content, true));
                    $content=json_decode($content,true)['result']['gps'][0];
                    Log::info(" content ". var_export( $content, true));
                    if(empty($content)){
                        ajax_return(-1,'未识别出坐标数据，请确保数据格式正确');
                    }
                    //$loc=$content['lon'].','.$content['lat'].','.$content['alt'];

                    $lon = $content['lon'];
                    $lat = $content['lat'];
                    $alt = $content['alt'];

                    ajax_return(1,'识别成功',['lon'=>$lon,'lat'=>$lat,'alt'=>$alt]);
                    @unlink($savePath.$saveName);
                    ajax_return(1,'操作成功');
                }else{
                    ajax_return(-1,'Excel文件保存出错');
                }
            }
            ajax_return(-1,'操作成功');
        }
    }

    public function importKMLLine(){
        Log::info(" ==== importKMLLine ====" );
        
        if(IS_AJAX && IS_POST){
            //P($_FILES);
            $lineID = $_POST['lineID'];
            $towerOriginName = isset($_POST['towerName']) ? $_POST['towerName'] : '';
            $towerType = isset($_POST['towerType']) ? $_POST['towerType'] : '';
            $towerSquence = isset($_POST['towerSquence']) ? $_POST['towerSquence'] : '';
         
            Log::info("lineID :" .var_export($lineID,true));
            Log::info("TowerType :" .var_export($towerType,true));
            Log::info("towerName :" .var_export($towerOriginName,true));
            Log::info("towerSquence :" .var_export($towerSquence,true));

            $shapeInCompass = Db::table('towerShapeList')
                ->where('towerShapeNameID', $towerType)
                ->value('shapeInCompass');
            Log::info("photoPosition :" . var_export($shapeInCompass,true));
            $bitdata = 0;
            if ($shapeInCompass[0] == 1) {
                $bitdata |= 1;
            }
            else {
                $bitdata &= 0;
            }
            if ($shapeInCompass[3] == 1) {
                $bitdata |= 2;
            }
            else {
                $bitdata &= 0xfffd;
            } 
            if ($shapeInCompass[1] == 1) {
                $bitdata |= 4;
            }
            else {
                $bitdata &= 0xfffb;
            }
            if ($shapeInCompass[2] == 1) {
                $bitdata |= 8;
            }
            else {
                $bitdata &= 0xfff7;
            }
            Log::info("bitdata : " . var_export($bitdata, true));
            $zoomFactor = 2 * 10;

            Log::info("file size :" .var_export($_FILES['file']['size'],true));

            if($_FILES['file']['size']<=0){
                ajax_return(-1,'请上传有效的KML文档');
            }

            if($_FILES['file']['size']>0){
                $file = request()->file('file');
                $savePath = './loc_file/';
                $extension = strtolower(pathinfo($file->getInfo('name'), PATHINFO_EXTENSION));
                if($extension === 'kmz'){
                    Log::info("extension :" .var_export($extension,true));
                    $saveName = 'loc_txt_'.date('ymd_His').'.'.'zip';
                    Log::info("saveName :" .var_export($saveName,true));
                    $kmlPath = $savePath . $saveName;
                    $result = $file->validate(['ext'=>'kml,KML,kmz,KMZ'])->move($savePath, $saveName);
                    $kmlPath = $this->parseTowerKML($kmlPath, $saveName);
                    Log::info("kmlPath==== :" .var_export($kmlPath,true));
                }else{
                    $saveName = 'loc_txt_'.date('ymd_His').'.'.pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);
                    $kmlPath = $savePath . $saveName;
                    $result = $file->validate(['ext'=>'kml,KML,kmz,KMZ'])->move($savePath, $saveName);
                }
              
                Log::info("result :" .var_export($result,true));
                $dbAccount = Db::table('towerList')  
                            ->where('lineID', $lineID)
                            ->count();
                $arr = [];
                if($result){
                    Log::info("kmlPath :" .var_export($kmlPath,true));
                    $doc = new \DOMDocument('1.0', 'utf-8');
                    $doc->load($kmlPath);
                    Log::info(" doc create ");
                    $root = $doc->documentElement;
                    $namespace = $root->getAttribute('xmlns:wpml');
                    Log::info(" namespace: " . $namespace);
                    $wpmlNamespace = $namespace;
                    $placemarks = $doc->getElementsByTagName('Placemark');

                    foreach ($placemarks as $placemark) {
                        $towerName =  $towerOriginName . $towerSquence;
                        Log::info("towerName === :" .var_export($towerName,true));
                        $point = $placemark->getElementsByTagName('Point')->item(0);
                        $ellipsoidHeight = $placemark->getElementsByTagName('ellipsoidHeight')->item(0);
                        $height = $ellipsoidHeight->nodeValue - 10;
                        Log::info("ellipsoidHeight === :" . var_export($height,true));
                        $coordinatesNode = $point->getElementsByTagName('coordinates')->item(0);
                        Log::info("coordinatesNode=== :" .var_export($coordinatesNode,true));
                        if ($coordinatesNode) {
                            $coordinatesString = $coordinatesNode->nodeValue;
                            $coordinatesArray = explode(',', $coordinatesString);
                            if (count($coordinatesArray) == 2) {
                                $longitude = (double)trim($coordinatesArray[0]);
                                $latitude = (double)trim($coordinatesArray[1]);
                                Log::info("longitude :" . $longitude);
                                Log::info("latitude :" . $latitude);
                            }
                        }
                        
                        $actionGroupNode = $placemark->getElementsByTagName('actionGroup')->item(0);
                        Log::info("actionGroupNode=== :" .var_export($actionGroupNode,true));
                        if ($actionGroupNode) {
                            $actionGroupNode = $actionGroupNode->getElementsByTagName('action')->item(0);
                            $focalLengthNode = $placemark->getElementsByTagNameNS($wpmlNamespace, 'focalLength')->item(0);
                            if($focalLengthNode){
                                $focalLength = $focalLengthNode->nodeValue;
                                $zoomFactor = $focalLength / 24 * 10;
                                Log::info(" zoomFactor :" . $zoomFactor);
                            }
                        }else {
                            Log::info(" without actionGroupNode ====" );
                            // $zoomFactor = 20;
                            continue;
                        }
                        Log::info("dbAccount :" .var_export($dbAccount,true));
                        if($dbAccount === 0){
                            $sequence += 1;
                        }else {
                            $sequence = $dbAccount + 1;
                        }
            
                        $arr[]=[
                            'towerName'=>$towerName,
                            'lineID' =>  $lineID,
                            'longitude' => $longitude,
                            'latitude' => $latitude,
                            'altitude' => $height,
                            'towerNumber' => $sequence,
                            'insulatorNum' => $zoomFactor,
                            'towerShapeID' => $towerType,
                            'photoPosition' => $bitdata,
                        ];
                    
                        $dbAccount = $sequence;
                        Log::info("====== towerSquence :" .var_export($towerSquence,true));
                        $towerSquence += 1;
                    }

                    if(empty($arr)){
                        Log::info("arr is null :" .var_export($arr,true));
                        ajax_return(-1, '拍照点个数为0');
                    }

                   
                    $batchSize = 100;
                    $batches = array_chunk($arr, $batchSize);

                    foreach ($batches as $batch) {
                        $res = Db::table('towerList')->insertAll($batch);
                        if (!$res) {
                            Db::rollback();
                            ajax_return(-1, '添加失败');
                        }
                    }
                    Db::commit();
                    ajax_return(1, '添加成功');
                }else{
                    ajax_return(-1,'文件保存出错');
                }
            }
            ajax_return(-1,'操作成功');
        }
    }

    public function parseTowerKML($kmlPath,$saveName){
        Log::info(" ==== parseTowerKML ====" );

        $zip = new ZipArchive;
        Log::info("saveName " . $kmlPath);
        $unzipPath = dirname($kmlPath);
        $unzipName = pathinfo($saveName, PATHINFO_FILENAME);
        $unzipPath1 = $unzipPath . '/' .$unzipName;
        Log::info("unzipPath1 " . $unzipPath1);
        Log::info("kmlPath " . $kmlPath);
        if($zip->open($kmlPath)){
            $zip->extractTo($unzipPath1);
            $zip->close();
            $a = $this->recursiveFindKML($unzipPath1);
            Log::info("a==== " . $a);
            return $a;
        }
        return null;
    }

    public function recursiveFindKML($unzipPath1){
        $dir = new \DirectoryIterator($unzipPath1);
        Log::info("unzipPath1 " . $unzipPath1);
        $saveName = pathinfo($unzipPath1, PATHINFO_FILENAME);
        Log::info("saveName " . $saveName);
        $aimFile = '';
        foreach($dir as $fileInfo){
            Log::info("fileInfo " . $fileInfo);
            if(!$fileInfo->isDot()){
                $filePath = $fileInfo->getPathname();
                if($fileInfo->isDir()){
                    $aimFile = $this->recursiveFindKML($filePath);
                    if($aimFile){
                        return $aimFile;
                    }
                }else{
                    $extension = strtolower(pathinfo($filePath,PATHINFO_EXTENSION));
                    if($extension === 'kml'){
                        Log::info("KML NAME  " . $filePath);
                        return $filePath;
                    }
                }
            }
        }
        return null;
    }

    public function importLine(){
        Log::info(" ==== importLine ====" );

        if(IS_AJAX && IS_POST){
            //P($_FILES);
            $lineID = $_POST['lineID'];
            $towerType = isset($_POST['towerType']) ? $_POST['towerType'] : '';
            $zoomFactor = isset($_POST['zoomFactor']) ? $_POST['zoomFactor'] : '';

            $shapeInCompass = Db::table('towerShapeList')
                ->where('towerShapeNameID', $towerType)
                ->value('shapeInCompass');
            Log::info("photoPosition :" . var_export($shapeInCompass,true));
            $bitdata = 0;
            if ($shapeInCompass[0] == 1) {
                $bitdata |= 1;
            }
            else {
                $bitdata &= 0;
            }
            if ($shapeInCompass[3] == 1) {
                $bitdata |= 2;
            }
            else {
                $bitdata &= 0xfffd;
            } 
            if ($shapeInCompass[1] == 1) {
                $bitdata |= 4;
            }
            else {
                $bitdata &= 0xfffb;
            }
            if ($shapeInCompass[2] == 1) {
                $bitdata |= 8;
            }
            else {
                $bitdata &= 0xfff7;
            }
            Log::info("bitdata : " . var_export($bitdata, true));
           // $photoHeight = isset($_POST['photoHeight']) ? $_POST['photoHeight'] : '';
            $zoomFactor = $zoomFactor * 10;
            Log::info("lineID :" .var_export($lineID,true));
            Log::info("TowerType :" .var_export($towerType,true));
            Log::info("zoomFactor :" .var_export($zoomFactor,true));
            //Log::info("photoHeight :" .var_export($photoHeight,true));
            Log::info("file size :" .var_export($_FILES['file']['size'],true));

            if($_FILES['file']['size']<=0){
                ajax_return(-1,'请上传csv文档');
            }

            if($_FILES['file']['size']>0){
                $file = request()->file('file');
                $savePath = './loc_file/';
                $saveName = 'loc_txt_'.date('ymd_His').'.'.pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);
                $result = $file->validate(['ext'=>'csv,CSV'])->move($savePath, $saveName);
                Log::info("result :" .var_export($result,true));

                if($result){
                    $excel = $savePath . $saveName;
                    Log::info("excel :" .var_export($excel,true));
                    ini_set('default_charset', 'UTF-8');
                    
                    $fileContent = file_get_contents($excel);
                    
                    $originalEncoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'GB2312', 'GBK']);
                    
                    // if ($originalEncoding !== 'UTF-8' || $originalEncoding !== 'GB2312') {
                    //     ajax_return(-1, '文件编码格式错误，仅支持UTF-8和GB2312编码格式');
                    // }



                    Log::info("=== originalEncoding :" .var_export($originalEncoding,true));
                    if ($originalEncoding !== 'UTF-8') {
                        $fileContentUTF8 = mb_convert_encoding($fileContent, 'UTF-8', 'GB2312');
                    } else {
                        $fileContentUTF8 = $fileContent;
                    }
    
                    $tempFile = tempnam(sys_get_temp_dir(), 'csv');
                    Log::info(" tempFile :" .var_export($tempFile,true));
                    file_put_contents($tempFile, $fileContentUTF8);
                    $file = fopen($tempFile, "r");


                    if ($file) {
                        // 读取CSV文件的第一行，通常是表头
                        $header = fgetcsv($file,1000,',');
                        $csvData[] = $header;
                        Log::info("header :" .var_export($header,true));

                        $dbAccount = Db::table('towerList')  
                            ->where('lineID', $lineID)
                            ->count();
                  
                  
                        while (($row = fgetcsv($file,1000,',')) !== false){
                            Log::info("dbAccount :" .var_export($dbAccount,true));
                            if($dbAccount === 0){
                                $sequence += 1;
                            }else {
                                $sequence = $dbAccount + 1;
                            }
    
                            $csvData[] = $row;
                            Log::info("row :" .var_export($row,true));
                            $towerName = $row[0];
                            if(strpos($row[0],',') !== false){
                                $wayPoints = explode(',', $row[0]);
                                $towerName = $wayPoints[0];
                                $longitude = $wayPoints[1];
                                $latitude = $row[1];
                                $altitude = $row[2];
                            }else{
                                $towerName = $row[0];
                                $longitude = $row[1];
                                $latitude = $row[2];
                                $altitude = $row[3];
                            }

                            $wayPoints = explode(',', $row[0]);
                           // Log::info("wayPoints :" . var_export($wayPoints,true));
                            $towerName = $wayPoints[0];
                            Log::info("wayPoints  towerName:" .$towerName);
                           // $longitude = $row[1];
                        
                            Log::info("row :" .var_export($row,true));
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
                                'photoPosition' => $bitdata,
                               // 'photoHeight'=>$photoHeight,

                            ];
                            $dbAccount = $sequence;
                        }
                        // if(!empty($arr)){
                        //     $res=Db::table('towerList')
                        //         ->insertAll($arr);
                        //     if(!$res){
                        //         Db::rollback();
                        //         ajax_return(-1,'添加失败');
                        //     }
                        // }
                        // Db::commit();
                        // ajax_return(1,'添加成功');
                        // Log::info("row :" .var_export($row,true));
                        // fclose($file);

                        $batchSize = 100; // 每批插入100条数据
                        $batches = array_chunk($arr, $batchSize);

                        foreach ($batches as $batch) {
                            $res = Db::table('towerList')->insertAll($batch);
                            if (!$res) {
                                Db::rollback();
                                ajax_return(-1, '添加失败');
                            }
                        }

                        Db::commit();
                        ajax_return(1, '添加成功');
                        Log::info("row :" . var_export($row, true));
                   

                        fclose($file);
                        unlink($tempFile);
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

        $permission=Db::view('user_info')
        ->where('userID', $this->member_id)
        ->find();
        Log::info("permission : " . var_export($permission,true));
        Log::info("permissionGroupID : " . var_export($permission['permissionGroupID'],true));
        $companyID = $permission['companyID'];
        Log::info("companyID : " . var_export( $companyID,true));

        if($permission['permissionGroupID'] == 0){
            Log::info("=== superadmin ==="); 
            $data=Db::view('user_info')
                ->where($where)
                ->order('userID desc')
                ->select();
        } else if ($permission['permissionGroupID'] == 3){
            $countyID = Db::view('companyList')
            ->where("companyID",$companyID)
            ->value('countyCompanyID');

            Log::info("countyID : " . var_export( $countyID,true));   

            $companyArray=Db::table('companyList')
                ->where("countyCompanyID", $countyID)
                ->column('companyID');
            Log::info("companyArray : " . var_export( $companyArray,true));

            $data=Db::view('user_info')
                ->where($where)
                ->whereIn('companyID',  $companyArray)
                ->order('userID desc')
                ->select();

        }
        else if($permission['permissionGroupID'] == 1){
            Log::info("=== not superadmin ==="); 

            $data=Db::view('user_info')
            ->where($where)
            ->where('companyID',  $companyID)
            ->order('userID desc')
            ->select();
          
        } else {
            // $data=Db::table('user_info')
            // ->where($where)
            // ->where('group_list_id', $permission['groupID'])
            // ->order('userID desc')
            // ->select();


            $data=Db::view('user_info')
            ->where($where)
            ->where('companyID',  $companyID)
            ->order('userID desc')
            ->select();
        }

        $groups=Db::table('groupList')
            ->column('groupName','groupID');
        Log::info("groups in user list : " . var_export($groups,true));

        Log::info("data in user list : " . var_export($data,true));

        $pgs=Db::table('permissionGroup')
            ->column('permission_group_name','groupID');

        $this->assign(compact('data','keywords','type','groups','pgs','permission'));
        return $this->fetch();
    }

    public function add_user(){
        Log::info("=== add_user ==="); 
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


        $permission=Db::view('user_info')
        ->where('userID', $this->member_id)
        ->find();
        Log::info("permission : " . var_export($permission,true));
        Log::info("permissionGroupID : " . var_export($permission['permissionGroupID'],true));
        $companyID = $permission['companyID'];
        Log::info("companyID : " . var_export( $companyID,true));
   
        if($permission['permissionGroupID'] == 0){
            Log::info("=== superadmin ==="); 
            $groups=Db::table('groupList')
            ->order('groupID asc')
            ->select();

            $pgs=Db::table('permissionGroup')
            ->order('groupID asc')
            ->select();
        }else if ($permission['permissionGroupID'] == 3){
            $countyID = Db::view('companyList')
            ->where("companyID",$companyID)
            ->value('countyCompanyID');

            Log::info("countyID : " . var_export( $countyID,true));   

            $companyArray=Db::table('companyList')
                ->where("countyCompanyID", $countyID)
                ->column('companyID');
            Log::info("companyArray : " . var_export( $companyArray,true));

            $groups=Db::table('groupList')
            ->whereIn('companyID', $companyArray)
            ->order('groupID desc')
            ->select();

            $pgs=Db::table('permissionGroup')
                ->order('groupID asc')
                ->whereNotIn('groupID',[0,1])
                ->select();

        }else{
            Log::info("=== not superadmin ==="); 

            $groups=Db::table('groupList')
                ->where('companyID', $permission['companyID'])
                ->order('groupID desc')
                ->select();


            // $groups=Db::table('groupList')
            //     ->where('groupID', $permission['groupID'])
            //     ->order('groupID desc')
            //     ->select();


            $pgs=Db::table('permissionGroup')
                ->order('groupID asc')
                ->whereNotIn('groupID',[0,1])
                ->select();
        } 


        // $groups=Db::table('groupList')
        //     ->order('groupID asc')
        //     ->select();

        // $pgs=Db::table('permissionGroup')
        //     ->order('groupID asc')
        //     ->select();
        $this->assign(compact('data','groups','pgs','permission'));
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
        $permission=Db::view('user_info')
        ->where('userID', $this->member_id)
        ->find();
        $groups=Db::table('groupList')
            ->order('groupID asc')
            ->select();

        $pgs=Db::table('permissionGroup')
            ->order('groupID asc')
            ->select();
        $this->assign(compact('data','groups','pgs','permission'));
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

        
        $permission=Db::view('user_info')
            ->where('userID', $this->member_id)
            ->find();
        $companyID = $permission['companyID'];
        
        Log::info("permission : " . var_export($permission,true));
        Log::info("companyID : " . var_export( $companyID,true));
        Log::info("permissionGroupID : " . var_export($permission['permissionGroupID'],true));

        if($permission['permissionGroupID'] == 0 ){
            Log::info("=== superadmin ==="); 
            $data=Db::table('companyList')
                ->where($where)
                ->order('companyID desc')
                ->select();
        } else if ($permission['permissionGroupID'] == 3){
            $countyID = Db::view('companyList')
                ->where("companyID",$companyID)
                ->value('countyCompanyID');

            Log::info("countyID : " . var_export( $countyID,true));   

            $companyArray=Db::table('companyList')
                ->where("countyCompanyID", $countyID)
                ->column('companyID');
            Log::info("companyArray : " . var_export( $companyArray,true));

            $data=Db::table('companyList')
            ->where($where)
            ->whereIn('companyID',$companyArray)
            ->select();

        }
        // else if($permission['permissionGroupID'] == 1){
        //     $data=Db::table('companyList')
        //     ->where($where)
        //     ->where('createManID',$this->member_id)
        //     ->whereOr('createManID',0)
        //     ->order('companyID desc')
        //     ->select();
        // }
        else{
            Log::info("=== not super admin ==="); 
            $data=Db::table('companyList')
                ->where($where)
                ->where('companyID',$companyID)
                ->select();
        }  

       
        
        $this->assign(compact('data','keywords','type','permission'));
        return $this->fetch();
    }

    public function county_company_list(){
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
        $permission=Db::view('user_info')
            ->where('userID', $this->member_id)
            ->find();
        $companyID = $permission['companyID'];
        
        Log::info("permission : " . var_export($permission,true));
        Log::info("companyID : " . var_export( $companyID,true));
        Log::info("permissionGroupID : " . var_export($permission['permissionGroupID'],true));

        if($permission['permissionGroupID'] == 0){
            Log::info("=== superadmin ==="); 
            $data=Db::table('countyCompanyList')
                ->where($where)
                ->order('county_company_id desc')
                ->select();
        }
        else{
            Log::info("=== not super admin ===");

            $countyID = Db::view('companyList')
                ->where("companyID",$companyID)
                ->value('countyCompanyID'); 
                
            $data=Db::table('countyCompanyList')
                ->where($where)
                ->where('county_company_id',$countyID)
                ->select();
        }  

        // $data=Db::table('countyCompanyList')
        //     ->where($where)
        //     ->order('county_company_id desc')
        //     ->select();
        
        $this->assign(compact('data','keywords','type','permission'));
        return $this->fetch();
    }




    public function add_company(){
        Log::info("=== add_company ==="); 
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            Log::info("data in add company: " . var_export( $data,true));
            if(empty($data['companyName'])){
                ajax_return(-1,'请填写单位名称');
            }
            // if(empty($data['address'])){
            //     ajax_return(-1,'请填写地址');
            // }
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
                $data['createManID']=$this->member_id;
                Log::info("data : " . var_export( $data,true));
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
        $permission=Db::view('user_info')
            ->where('userID', $this->member_id)
            ->find();


        $county_companys=Db::table('countyCompanylist')
            ->select();

        Log::info("county_companys : " .var_export($county_companys,true));
       
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('companyList')
                ->where('companyID',$id)
                ->find();
        }
        Log::info("====result : " .var_export($data,true));
        $this->assign(compact('data','permission','county_companys'));
        return $this->fetch();
    }

    public function check_company(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            // $data=Db::table('companyList')
            //     ->where('companyID',$id)
            //     ->find();

            // $county_companys=Db::table('countyCompanylist')
            //     ->select();

            $data = Db::view('company_county')
                ->where('companyID',$id)
                ->find();

            $groups = Db::view('group_company')
                ->where('companyID',$id)
                ->select();

            Log::info("====result in check company : " .var_export($data,true));
        }
        $this->assign(compact('data','groups'));
        return $this->fetch();
    }

    public function add_county_company(){
        Log::info("=== add_county_company ==="); 
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            Log::info("data in add county company : " . var_export( $data,true));
            if(empty($data['county_company_name'])){
                ajax_return(-1,'请填写上级单位单位名称');
            }

            Db::startTrans();
            $id=$data['countyCompanyID'];
            unset($data['countyCompanyID']);
            if($id>=0){
                if($this->my_permission['modifyUserMgr']<=0){
                    ajax_return(-1,'无权操作');
                }
                $has_data=Db::table('countyCompanyList')
                    ->where('county_company_id',$id)
                    ->find();
                if(empty($has_data)){
                    ajax_return(-1,'未知单位');
                }

                $res=Db::table('countyCompanyList')
                    ->where('county_company_id',$id)
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
             
                Log::info("data : " . var_export( $data,true));
                $res=Db::table('countyCompanyList')
                    ->insertGetId($data);
                if($res===false){
                    Db::rollback();
                    ajax_return(-1,'添加失败');
                }
                Db::commit();
                ajax_return(1,'添加成功');
            }
        }
        $permission=Db::view('user_info')
            ->where('userID', $this->member_id)
            ->find();
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('countyCompanyList')
                ->where('county_company_id',$id)
                ->find();
        }
        $this->assign(compact('data','permission'));
        return $this->fetch();
    }

    public function check_county_company(){
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        $id=input('id',-1);
        $data=[];
        if($id>=0){
            $data=Db::table('countyCompanyList')
                ->where('county_company_id',$id)
                ->find();


            $companys=Db::view('company_county')
                ->where('county_company_id',$id)
                ->select();

            Log::info("companys ==== : " . var_export($companys,true));
        }
    
        $this->assign(compact('data','companys'));
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

        $permission=Db::view('user_info')
            ->where('userID', $this->member_id)
            ->find();
        $companyID = $permission['companyID'];
        
        Log::info("permission : " . var_export($permission,true));
        Log::info("companyID : " . var_export( $companyID,true));
        Log::info("permissionGroupID : " . var_export($permission['permissionGroupID'],true));

        if($permission['permissionGroupID'] == 0){
            Log::info("=== superadmin ==="); 
            $data=Db::table('groupList')
                ->where($where)
                ->order('groupID desc')
                ->select();
        }else if($permission['permissionGroupID'] == 1){
            Log::info("=== not super admin ==="); 
            $data=Db::table('groupList')
                ->where($where)
                ->where('companyID', $companyID )
                ->select();
        } else if ($permission['permissionGroupID'] == 3){
            $countyID = Db::view('companyList')
            ->where("companyID",$companyID)
            ->value('countyCompanyID');

            Log::info("countyID : " . var_export( $countyID,true));   

            $companyArray=Db::table('companyList')
                ->where("countyCompanyID", $countyID)
                ->column('companyID');
            Log::info("companyArray : " . var_export( $companyArray,true));

            $data=Db::table('groupList')
                ->where($where)
                ->whereIn('companyID', $companyArray)
                ->select();


        }else {
            $data=Db::table('groupList')
                ->where($where)
                ->where('groupID',$permission['groupID'])
                ->select();
        }
        $this->assign(compact('data','keywords','type','permission'));
        return $this->fetch();
    }


    public function add_group(){
        Log::info("=== add_group ==="); 
        if($this->my_permission['findUserMgr']<=0){
            return $this->fetch();
        }
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            Log::info("data : " . var_export( $data,true));
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
                Log::info("data : " . var_export( $data,true));
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

        // $permission=Db::view('user_info')
        // ->where('userID', $this->member_id)
        // ->find();

        
        $permission=Db::view('user_info')
            ->where('userID', $this->member_id)
            ->find();
        $companyID = $permission['companyID'];
        
        Log::info("permission : " . var_export($permission,true));
        Log::info("companyID : " . var_export( $companyID,true));
        Log::info("permissionGroupID : " . var_export($permission['permissionGroupID'],true));

        if($permission['permissionGroupID'] == 3){
           
            $countyID = Db::view('companyList')
            ->where("companyID", $permission['companyID'])
            ->value('countyCompanyID');

            Log::info("countyID : " . var_export( $countyID,true));   

            $companyArray=Db::table('companyList')
                ->where("countyCompanyID", $countyID)
                ->column('companyID');

            $companys=Db::table('companyList')
                ->whereIn('companyID',$companyArray)
                ->order('companyID asc')
                ->select();

            Log::info("data : " . var_export( $companys,true));

        }else {
            $companys=Db::table('companyList')
            ->order('companyID asc')
            ->select();
        
        }
        Log::info("data : " . var_export( $companys,true));

        $this->assign(compact('data','title','companys','permission'));

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
        $permission=Db::view('user_info')
        ->where('userID', $this->member_id)
        ->find();
        $companys=Db::table('companyList')
            ->order('companyID asc')
            ->select();
        $this->assign(compact('data','companys','permission'));
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

        $permission=Db::view('user_info')
            ->where('userID', $this->member_id)
            ->find();
        $this->assign(compact('data','keywords','permissions','permission'));
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
        $permission=Db::view('user_info')
            ->where('userID', $this->member_id)
            ->find();
        $data=Db::table('permissionList')
            ->where($where)
            ->order('permissionID desc')
            ->select();
        $this->assign(compact('data','keywords','permission'));
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

    public function history_list(){
        if($this->my_permission['findData']<=0){
            return $this->fetch();
        }
        $where='1=1';
        $keywords=input('keywords','');
        if($keywords){
            $where.=" and submissionName like '%{$keywords}%'";
        }
        $permission=Db::view('user_info')
        ->where('userID', $this->member_id)
        ->find();
        $data=Db::table('FlightRecords')
            ->where($where)
            ->order('FlightRecordsID desc')
            ->select();
        $this->assign(compact('data','keywords','permission'));
        return $this->fetch();
    }

    public function history_detail(){
        Log::info("==== history_detail ====" );
        if($this->my_permission['findData']<=0){
            return $this->fetch();
        }
        $id = input('id',-1);
        $data = Db::table('FlightRecords')
            ->where('FlightRecordsID',$id)
            ->find();
        Log::info("data : " . var_export($data,true));
        
        $missions = Db::table('missionList')
            ->column('missionType','missionID');
    
        $towerNum = Db::view('submission_mission')
            ->where('submissionID',$data['submissionID'])
            ->value('tower_num');
        
        Log::info("towerNum : " . var_export($towerNum,true));
        $data['TowerNum'] = $towerNum;
        

        $towersInfor=Db::view('submission_tower_missiontype')
            ->where('submissionID',$data['submissionID'])
            ->column('towerID');
        Log::info("towersInfor : " . var_export($towersInfor,true));

        $towers = Db::view('tower_line_towershape')
            ->whereIn('towerID',$towersInfor)
            ->select();
        Log::info("towers : " . var_export($towers,true));

        // $towers=Db::table('FlightRecordsTowerList')
        //     ->where(['FlightRecordsID'=>$id])
        //     ->select();


        
        $this->assign(compact('data','missions','towers'));
        return $this->fetch();
    }

    public function flight_list(){
        Log::info("==== permission ==== " );
        if($this->my_permission['findData']<=0){
            return $this->fetch();
        }
        $where='1=1';
        $keywords=input('keywords','');
        if($keywords){
            $where.=" and towerShapeName like '%{$keywords}%'";
        }
        // $data=Db::table('towerShapeList')
        //     ->where($where)
        //     ->order('towerShapeNameID desc')
        //     ->select();

        $permission=Db::view('user_info')
            ->where('userID', $this->member_id)
            ->find();
        
        Log::info("permission : " . var_export($permission,true));
        Log::info("permissionGroupID : " . var_export($permission['permissionGroupID'],true));
        $companyID = $permission['companyID'];
        Log::info("companyID : " . var_export( $companyID,true));
        if($permission['permissionGroupID'] == 0){
            Log::info("=== superadmin ==="); 
            $data=Db::table('towerShapeList')
                ->where($where)
                ->order('towerShapeNameID desc')
                ->select();
        } else if ($permission['permissionGroupID'] == 3){
            $countyID = Db::view('companyList')
            ->where("companyID",$companyID)
            ->value('countyCompanyID');

            Log::info("countyID : " . var_export( $countyID,true));   

            $companyArray=Db::table('companyList')
                ->where("countyCompanyID", $countyID)
                ->column('companyID');
            Log::info("companyArray : " . var_export( $companyArray,true));


            $query = Db::table('towerShapeList')
            ->join('UserList', 'towerShapeList.createManID = UserList.userID')
            ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
            ->join('companyList', 'GroupList.companyID = companyList.companyID')
            ->whereIn('companyList.companyID', $companyArray)
            ->column('towerShapeNameID');
            Log::info("query : " .var_export($query,true)); 
            $shapes1 =  Db::table('towerShapeList')
                ->where('createManID', 0)
                ->select();
            Log::info("shapes1 : " .var_export($shapes1,true)); 
            $shapes2 =  Db::table('towerShapeList')
                ->whereIN('towerShapeNameID', $query)
                ->select();
            Log::info("shapes2 : " .var_export($shapes2,true));
            $data = array_merge($shapes1,$shapes2);
            Log::info("shapes : " .var_export($data,true));
        }else{
            // Log::info("company admin and staff"); 
            // $data = Db::table('towerShapeList')
            //     ->join('UserList', 'towerShapeList.createManID = UserList.userID')
            //     ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
            //     ->join('companyList', 'GroupList.companyID = companyList.companyID')
            //     ->where('companyList.companyID', $companyID)
            //     ->whereor('towerShapeList.createManID', 0)
            //     ->order('towerShapeNameID desc')
            //     ->select();

            Log::info("company admin and staff"); 
            $query = Db::table('towerShapeList')
                ->join('UserList', 'towerShapeList.createManID = UserList.userID')
                ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
                ->join('companyList', 'GroupList.companyID = companyList.companyID')
                ->where('companyList.companyID', $companyID)
                ->column('towerShapeNameID');
            Log::info("query : " .var_export($query,true)); 
            $shapes1 =  Db::table('towerShapeList')
                ->where('createManID', 0)
                ->select();
            Log::info("shapes1 : " .var_export($shapes1,true)); 
            $shapes2 =  Db::table('towerShapeList')
                ->whereIN('towerShapeNameID', $query)
                ->select();
            Log::info("shapes2 : " .var_export($shapes2,true));
            $data = array_merge($shapes1,$shapes2);
            Log::info("shapes : " .var_export($data,true));
        }  
        Log::info("data: " . var_export($data, true)); 
        $this->assign(compact('data','keywords','permission'));
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

                Log::info("data ====:" .var_export($data,true));
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
        $permission=Db::view('user_info')
        ->where('userID', $this->member_id)
        ->find();
    
        $lts=Db::table('lineTypeList')
            ->select();
        Log::info("data in add flight:" .var_export($data,true));
        Log::info("lts in add flight:" .var_export($lts,true));
        $this->assign(compact('data', 'lts', 'permission'));
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
        $permission=Db::view('user_info')
        ->where('userID', $this->member_id)
        ->find();
    
        Log::info(" data : " . var_export($data,true));
        $lts=Db::table('lineTypeList')
            ->select();
        Log::info(" lts : " . var_export($lts,true));
        $this->assign(compact('data','lts','permission'));
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
        $permission=Db::view('user_info')
        ->where('userID', $this->member_id)
        ->find();
        Log::info(" data in get drone: " . var_export($data,true));

        if(IS_AJAX && IS_POST){
            $data=input('post.');

            $controller =  "get_drone";
            $len = strLen(json_encode($data));

            if(!isset($data['wetherLive'])){
                $wetherLiveVal = Db::table('drone')
                    ->where('snCode',$data['droneSncode'])
                    ->value('wetherLive');

                $data['wetherLive'] = $wetherLiveVal;
            }


            $combinedData = array(
                "controller" => $controller,
                "len" => $len,
                "data" =>  $data,
            );
            $combinedData = json_encode($combinedData);
            Log::info(" combinedData in get_drone : " . var_export($combinedData,true));

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



        $this->assign(compact('data','title','permission'));

        return $this->fetch();
    }


    public function data_analysis(){
        Log::info("==== data_analysis ==="); 


        $currentTaskID = isset($_GET['submissionID'])?$_GET['submissionID'] : null;
        Log::info("currentTaskID :". var_export($currentTaskID, true));


        if($this->my_permission['findTask']<=0){
            return $this->fetch();
        }
        Log::info("member id: " . $this->member_id); 
        $task_name=input('task_name','');
        $task_where='1=1';
        if($task_name){
            $task_where.=" and submissionName like '%{$task_name}%'";
        }

        $permission=Db::view('user_info')
                    ->where('userID', $this->member_id)
                    ->find();
        Log::info("permission : " . var_export($permission,true));
        Log::info("permissionGroupID : " . var_export($permission['permissionGroupID'],true));
        $companyID = $permission['companyID'];
        Log::info("companyID : " . var_export( $companyID,true));
        if($permission['permissionGroupID'] == 0){
            Log::info("=== superadmin ==="); 
            $tasks=Db::view('submission_mission')
            ->where($task_where)
            ->order('submissionID desc')
            ->select();
        }else if($permission['permissionGroupID'] == 3){
            Log::info("=== county admin  ==="); 

            $countyID = Db::table('companyList')
                ->where("companyID",$companyID)
                ->value('countyCompanyID');

            Log::info("countyID : " . var_export( $countyID,true));   

            $companyArray=Db::table('companyList')
                ->where("countyCompanyID", $countyID)
                ->column('companyID');
            Log::info("companyArray : " . var_export( $companyArray,true));

            $tasks = Db::view('submission_mission')
                ->join('UserList', 'submission_mission.createManID = UserList.userID')
                ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
                ->join('companyList', 'GroupList.companyID = companyList.companyID')
                ->whereIn('companyList.companyID',$companyArray)
                ->where($task_where)
                ->order('submissionID desc')
                ->select();

            Log::info("tasks : " . var_export( $tasks,true));
        
        }else if($permission['permissionGroupID'] == 1){
            Log::info("=== company admin  ==="); 
            $tasks = Db::view('submission_mission')
                ->join('UserList', 'submission_mission.createManID = UserList.userID')
                ->join('GroupList', 'UserList.group_list_id = GroupList.groupID')
                ->join('companyList', 'GroupList.companyID = companyList.companyID')
                ->where('companyList.companyID', $companyID)
                ->where($task_where)
                ->order('submissionID desc')
                ->select();
            Log::info("submissions: " . var_export($tasks, true));
        }
        else {
            Log::info("=== staff ===");
            $tasks=Db::view('submission_mission')
            ->where($task_where)
            ->where('createmanID', $this->member_id)
            ->order('submissionID desc')
            ->select();
        }
        foreach($tasks as &$task){
            Log::info("task : " . var_export($task,true));    
            if($task['missionID'] == 5){
                Log::info("missionID == 5");    
                $towerNumber=Db::table('uploadRouteSubmissionList')
                    ->where('submissionID', $task['submissionID'])
                    ->value('towerNumber');
                Log::info("towerNumber : " . var_export($towerNumber,true));    
                $task['tower_num'] = $towerNumber;
            }
        }
       
    
        $submissionID=input('submissionID',-1);
        Log::info("submissionID change : " . var_export($submissionID,true));

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
        }

        Log::info("task in data analysis : " . var_export($tasks,true));    
        $this->assign(compact('tasks','task_name','towers','submissionID','permission', 'currentTaskID'));

        return $this->fetch();
    }
    public function update_version(){
        Log::info("=== update_version === " );
        
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            $controller =  "update_version";
            unset($data['droneType']);
            unset($data['lensType']);
            unset($data['maxSpeed']);
            unset($data['systemVersion']);
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
        }
    }



    public function kill_parent(){
        Log::info("=== kill_parent === " );
        
        if(IS_AJAX && IS_POST){
            $data=input('post.');
            $controller =  "kill_parent";
            unset($data['droneType']);
            unset($data['lensType']);
            unset($data['maxSpeed']);
            unset($data['systemVersion']);
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
        }
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

            $taskWithDrone = Db::table('submissionList')
                ->where('droneID', $data['droneID'])
                ->update(['droneID' => null]);
      
            Log::info(" taskWithDrone " . var_export($taskWithDrone,true));


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
        $permission=Db::view('user_info')
        ->where('userID', $this->member_id)
        ->find();

        if($permission['permissionGroupID'] == 0){
            Log::info("=== superadmin ==="); 
            $all_drones = Db::table('drone')
                ->where('whetherOnline', 1)
                ->order('droneID desc')
                ->select();
        } else if($permission['permissionGroupID'] == 3){
            $countyID = Db::view('companyList')
            ->where("companyID",$permission['companyID'])
            ->value('countyCompanyID');

            Log::info("countyID : " . var_export( $countyID,true));   

            $companyArray=Db::table('companyList')
                ->where("countyCompanyID", $countyID)
                ->column('companyID');
            Log::info("companyArray : " . var_export( $companyArray,true));

            $all_drones = Db::table('drone')
                ->whereIn('companyID',$companyArray)
                ->where('whetherOnline', 1)
                ->order('droneID desc')
                ->select();

        }else {
            Log::info("=== not superadmin === "); 
            $all_drones = Db::table('drone')
                ->where('companyID',$permission['companyID'])
                ->where('whetherOnline', 1)
                ->order('droneID desc')
                ->select();
        }


        // $all_drones = Db::table('drone')
        //     ->order('droneID desc')
        //     ->select();

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
        Log::info("==== play_video ====" );
        $snCode = $this->request->param('snCode');

        Log::info("snCode :" . var_export($snCode, true));
        

        $currentDrone = Db::view('submission_mission')
            ->where("snCode", $snCode)
            ->find();

        
        Log::info("currentDrone: " .var_export($currentDrone, true));
        if($currentDrone == null){
            $currentDrone['snCode'] =  $snCode;
            $currentDrone['submissionID'] =  '-';
            $currentDrone['submissionName'] =  '-';
        }

        $liveAddr = Db::table('drone')
            ->where("snCode", $snCode)
            ->value('videoAddr');

        $currentDrone['liveAddr'] = $liveAddr;

        Log::info("currentDrone: " .var_export($currentDrone, true));
        $title='直播中心';
        

        $this->assign(compact('currentDrone','title'));
        return $this->fetch();
    }
    public function task_analysis(){
        return $this->fetch();
    }



    // public function send_play_video(){
    //     Log::info(" === send_play_video ===");
    //     if(IS_AJAX && IS_POST){
    //         $data=input('post.');
    //         $controller =  "send_play_video";
            
    //         $len = strLen(json_encode($data));
    //         $combinedData = array(
    //             "controller" => $controller,
    //             "len" => $len,
    //             "data" =>  $data,
    //         );
    //         $combinedData = json_encode($combinedData);
    //         Log::info(" combinedData : " . var_export($combinedData,true));

    //         $socket = new tcp();
    //         $socket->socketSend($combinedData);
    //         // $response = array( 
    //         //     'status' =>1,
    //         //     'message' =>'上传成功',
    //         //     'data' => $data,
    //         // );
    //         // Log::info(" response : " . var_export($response,true));
    //         //ajax_return($response);
    //     }
        
    // }

    public function check_router(){
        if(IS_AJAX && IS_POST){
            $datas = input('post.');
            Log::info(" === check_router === ");
            $uploadSubmissionID = Db::table('submissionList')
                    ->whereNotNull('droneID')
                    ->column('submissionID');     
            Log::info("uploadSubmissionID : " .var_export($uploadSubmissionID,true));
            $flightTypeArray = [];
            foreach($uploadSubmissionID as $data) {
                //$folderPath = 'C:/Users/Administrator/Desktop/console_socket/console_socket/x64/Debug/route'; 
                $folderPath = 'C:\Users\123\Desktop\console_socket\console_socket\route'; 

                //$folderPath = 'C:\Users\Administrator\Desktop\txzf_server\route';

               
                $files = scandir($folderPath);
                $flight = 1; // 默认值为1
                $submissionID = (string) $data;
                Log::info("submissionID : " . $submissionID);
                if(is_array($files) && !empty($files))
                {
                    foreach ($files as $file) {
                        Log::info("file : " . $file);
                        if($file === '.' || $file === '..'){
                            continue;
                        }
                        $fileInfo = $folderPath . '/' . $file;
                        Log::info("fileInfo : " . $fileInfo);
                        if(is_file($fileInfo)){
                            Log::info("=== is file === " );
                            if(strpos($file, '_') == false){
                                Log::info("not include '_' ");
                                continue;
                            } else {
                                $parts = explode('_', $file);
                                $leftPart = $parts[0];
                                Log::info("leftPart : " . $leftPart);
                                if($leftPart === $submissionID){
                                    $lastModifiedTime = (int)$parts[1];
                                    Log::info("lastModifiedTime : " . $lastModifiedTime);
                                    $current_timestamp = time();
                                    Log::info("current_timestamp : " . $current_timestamp);
                                    if($current_timestamp - $lastModifiedTime <= 604800){
                                        Log::info("current_timestamp111 : " . $current_timestamp);
                                        $flight = 2;
                                    }
                                }

                            }
                        }
                    }
                }
                Log::info("flight : " . $flight);
                //$flightTypeArray[] = $flight;

                $flightTypeArray[] = array(
                    "flight" => $flight,
                    "submissionID" => $submissionID,
                );
                Log::info("++ flightTypeArray : " .var_export($flightTypeArray,true));
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




    public function person_info(){
        Log::info(" ==== person_info ==== ");
        $permission=Db::view('user_info')
        ->where('userID', $this->member_id)
        ->find();
        $data = Db::view("user_info")
        ->where('userID',$this->member_id)
        ->find();
        Log::info("data: " . var_export($data,true));

        $this->assign(compact('data','permission'));
        return $this->fetch();
    }
}