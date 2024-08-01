<?php
namespace app\index\controller;

use think\facade\Log;
class tcp {
    function socketSend($message){
       
        $socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);

        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0));

        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 6, "usec" => 0));
       
        if(socket_connect($socket,'127.0.0.1', 2345) == false){
            Log::info(" connect fail ");
            print("false");
        }else{
            Log::info(" connect success ");
        
            if(socket_write($socket,$message,strlen($message)) == false){
                Log::info(" socket_write fail ");
                print("false");
            }else{
                Log::info(" socket_write success ");
            }
        }
        //error_log('close socket'.PHP_EOL, 3, 'D:\server\dronemanage\log.txt');
        // $response = socket_read($socket,1024);
        // if($response === "success !!!")
        // {
        //     socket_close();
        // }
        // sleep(1);
        socket_close($socket);
    }

    public function socketReceive() {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0));

        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => 6, "usec" => 0));

        if (socket_connect($socket, '127.0.0.1', 2345) == false) {
            Log::info("Connect failed");
            return false;
        } else {
            Log::info("Connect successful");
            $message = ''; // Initialize an empty string to store received message
            // Read data from the socket until the connection is closed

            while ($data = socket_read($socket, 1024)) {
                $message .= $data;
            }

            Log::info("data in socket receive:" . $data);

            Log::info("message in socket receive:" . $message);

            // Close the socket
            sleep(1);
            socket_close($socket);

            return $message; // Return the received message
        }
    }
}
