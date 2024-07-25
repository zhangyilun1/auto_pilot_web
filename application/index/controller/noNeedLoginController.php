<?php

namespace app\index\controller;
use think\Db;
use think\facade\Log;
use think\Controller;

class noNeedLoginController extends Controller
{
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
                $combinedData = json_encode($combinedData,JSON_UNESCAPED_SLASHES);
                Log::info("combinedData: " . var_export($combinedData,true));
                Log::info("combinedData['data']['snCode'] : " . $combinedData['data']['snCode'] );
                if( $snCode!== null){
                    Log::info("send to socket_connect " );
                    $socket = new tcp();
                    $socket->socketSend($combinedData);

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

}