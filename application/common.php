<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

//传递数据以易于阅读的样式格式化后输出
function p($data){
    // 定义样式
    $str='<pre style="display: block;padding: 9.5px;margin: 44px 0 0 0;font-size: 13px;line-height: 1.42857;color: #333;word-break: break-all;word-wrap: break-word;background-color: #F5F5F5;border: 1px solid #CCC;border-radius: 4px;">';
    // 如果是boolean或者null直接显示文字；否则print
    if (is_bool($data)) {
        $show_data=$data ? 'true' : 'false';
    }elseif (is_null($data)) {
        $show_data='null';
    }else{
        $show_data=print_r($data,true);
    }
    $str.=$show_data;
    $str.='</pre>';
    echo $str;die();
}

function is_weixin() {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return true;
    } return false;
}


/**
 * 二维数组按照键值降序排序
 * @param array $arr   待排序数组
 * @param string $key  键值
 * @return mixed
 */
function sortByKey($arr, $key,$sort='SOTR_ASC') {
    array_multisort(array_column($arr, $key), $sort, $arr);
    return $arr;
}


/**
 * 检测用户当前浏览器
 * @return boolean 是否ie浏览器
 */
function is_IE() {
    $userbrowser = $_SERVER['HTTP_USER_AGENT'];
    //P($userbrowser);
    if ( preg_match( '/MSIE/i', $userbrowser ) ) {
        $usingie = true;
    } else {
        $usingie = false;
    }

    if(!$usingie){
        if(strpos($_SERVER['HTTP_USER_AGENT'],"Triden")!==false){
            $usingie=true;
        }else{
            $usingie=false;
        }
    }
    return $usingie;
}

/**
 * 求两个日期之间相差的天数
 * (针对1970年1月1日之后，求之前可以采用泰勒公式)
 * @param string $day1
 * @param string $day2
 * @return number
 */
function diffBetweenTwoDays ($day1, $day2)
{
    $second1 = strtotime($day1);
    $second2 = strtotime($day2);

    if ($second1 < $second2) {
        $tmp = $second2;
        $second2 = $second1;
        $second1 = $tmp;
    }
    return round(($second1 - $second2) / 86400);
}

function checkMobile($mobile){
    if(preg_match("/^1[23456789]{1}\d{9}$/",$mobile)){
        return true;
    }else{
        return false;
    }
}


//ajax返回
function ajaxReturn($data,$type = 'json'){
    exit(json_encode($data));
}


/**
 * 邮件发送
 * @param $to    接收人
 * @param string $subject   邮件标题
 * @param string $content   邮件内容(html模板渲染后的内容)
 * @throws Exception
 * @throws phpmailerException
 */
function sendEmail($to,$subject='',$content='',$config=array(),$attachment=''){
    //判断openssl是否开启
    $openssl_funcs = get_extension_funcs('openssl');
    if(!$openssl_funcs){
        return array('status'=>-1 , 'msg'=>'请先开启openssl扩展');
    }


    if(empty($config)){
        $email_config=\think\Db::name('site')
            ->column('value','name');
        $config=array(
            'email_server'=>$email_config['email_server'],
            'email_port'=>$email_config['email_port'],
            'email_pwd'=>$email_config['email_pwd'],
            'email'=>$email_config['email'],
            'site_name'=>$email_config['site_name']
        );
    }

    //P($config);
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        //Server settings
        $mail->CharSet  = 'UTF-8';
        $mail->SMTPDebug = 0;                                       // Enable verbose debug output
        $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host       = $config['email_server'];                // Specify main and backup SMTP servers
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = $config['email'];                       // SMTP username
        $mail->Password   = $config['email_pwd'];                   // SMTP password
        $mail->SMTPSecure = 'tls';                                  // Enable TLS encryption, `ssl` also accepted
        $mail->Port       = $config['email_port'];;                 // TCP port to connect to
        if($mail->Port == 465) $mail->SMTPSecure = 'ssl';           // 使用安全协议
        //Recipients
        $mail->setFrom($config['email'],$config['site_name']);
        if(is_array($to)){
            foreach ($to as $v){
                $mail->addAddress($v);
            }
        }else{
            $mail->addAddress($to);
        }

        //$mail->addReplyTo('info@example.com', 'Information');
        //$mail->addCC('cc@example.com');
        //$mail->addBCC('bcc@example.com');

        // Attachments
        if($attachment){
            $mail->addAttachment($attachment);         // Add attachments
        }

        //P($mail);
        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $content;
        $mail->AltBody = $content;

        $mail->send();
        //echo 'Message has been sent';
        return array('status'=>1 , 'msg'=>'发送成功');
    } catch (Exception $e) {
        return array('status'=>-1 , 'msg'=>'发送失败: '.$mail->ErrorInfo);
        //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

/*发送短信*/
/*短信接口*/
function curl_post($url,$post_data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //如果需要将结果直接返回到变量里，那加上这句。
    return $result = curl_exec($ch);
}

function sendSms($data,$config=array())
{
    if(empty($config)){
        $mobile_config=\think\Db::name('site')
            ->column('value','name');
        $config=array(
            'msg_user_id'=>$mobile_config['msg_user_id'],
            'msg_user'=>$mobile_config['msg_user'],
            'msg_pwd'=>$mobile_config['msg_pwd'],
            'msg_server'=>'http://106.14.0.125:8088/sms.aspx',
            'sign'=>'【'.$mobile_config['sign'].'】'
        );
    }
    //数据

    $post_data = array();
    $post_data['userid']   = $config['msg_user_id'] ;
    $post_data['account']  = $config['msg_user'];
    $post_data['password'] = $config['msg_pwd'];
    $post_data['content']  = $data['content'].$config['sign'];
    $post_data['mobile']   = $data['mobile'];
    $post_data['sendtime'] = ''; //不定时发送，值为0，定时发送，输入格式YYYYMMDDHHmmss的日期值
    $post_data['action'] = 'send';
    $post_data = http_build_query($post_data);
    $result = curl_post($config['msg_server'],$post_data);
    $result=json_decode(json_encode(simplexml_load_string($result)));
    $result=array(
        'status'=>$result->returnstatus,
        'msg'=>$result->message
    );
    if (strpos($result['status'],'Success')!==false) {
        return array('status'=>1,'msg'=>'发送成功');
    }
    return array('status'=>-1,'msg'=>'发送失败,'.$result['msg']);
}


function checkSms($config=array())
{
    //数据
    $post_data = array();
    $post_data['userid']   = $config['msg_user_id'] ;
    $post_data['account']  = $config['msg_user'];
    $post_data['password'] = $config['msg_pwd'];
    $post_data['action'] = 'overage';
    $post_data = http_build_query($post_data);
    $result = curl_post($config['msg_server'],$post_data);
    $result=json_decode(json_encode(simplexml_load_string($result)));
    $result=array(
        'status'=>$result->returnstatus,
        'payinfo'=>$result->payinfo,
        'overage'=>$result->overage,
        'sendTotal'=>$result->sendTotal
    );
    return $result;
}

function send_sms_new($data,$mobile_config=array()){
    if(empty($mobile_config)){
        $mobile_config=\think\Db::name('site')
            ->column('value','name');
    }

    $config=array(
        'smsUser'=>$mobile_config['msg_user'],
        'smsKey'=>$mobile_config['msg_pwd'],
        'msg_server'=>$mobile_config['msg_server'],
        'templateId'=>$mobile_config['msg_tpl']
    );
    //P($config);

    $param = array(
        'smsUser' => $config['smsUser'],
        'templateId' => $config['templateId'],
        'msgType' => '0',
        'phone' => $data['mobile'],
        'vars' => '{"%code%":'.$data['code'].'}'
    );

    $sParamStr = "";
    ksort($param);
    foreach ($param as $sKey => $sValue) {
        $sParamStr .= $sKey . '=' . $sValue . '&';
    }

    $sParamStr = trim($sParamStr, '&');
    $smskey = $config['smsKey'];
    $sSignature = md5($smskey."&".$sParamStr."&".$smskey);
    $param['signature']=$sSignature;
    //P($param);
    $data = http_build_query($param);
    $options = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-Type:application/x-www-form-urlencoded',
            'content' => $data

        ));
    $url=$config['msg_server'];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, FILE_TEXT, $context);
    $result=json_decode($result,true);
    //P($result);
    $result_data=[];
    if($result['result']){
        $result_data['status']=1;
        $result_data['msg']='发送成功';
    }else{
        $result_data['status']=-1;
        $result_data['msg']='发送失败，请重试';
    }
    //P($result);
    return $result_data;

}

/**
 * 取得IP
 *
 * @return string 字符串类型的返回结果
 */
function getIp(){
    if (@$_SERVER['HTTP_CLIENT_IP'] && $_SERVER['HTTP_CLIENT_IP']!='unknown') {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (@$_SERVER['HTTP_X_FORWARDED_FOR'] && $_SERVER['HTTP_X_FORWARDED_FOR']!='unknown' && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return preg_match('/^\d[\d.]+\d$/', $ip) ? $ip : '';
}

/**
 * CURL请求
 * @param $url string 请求url地址
 * @param $method string 请求方法 get post
 * @param mixed $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug  调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method="POST", $postfields = null, $headers = array(), $debug = false)
{
    $method = strtoupper($method);
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if ($ssl) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    }
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);
        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    //return array($http_code, $response,$requestinfo);
    //P($response);
    return $response;
}

//获取自定长度的随机字符串
function getString($len){
    $strs="1234567890qwertyuiopasdfghjklzxcvbnm";
    $name=substr(str_shuffle($strs),mt_rand(0,strlen($strs)-($len+1)),$len);
    return $name;
}

// 递归删除文件夹
function delFile($path,$delDir = FALSE) {
    if(!is_dir($path))
        return FALSE;
    $handle = @opendir($path);
    if ($handle) {
        while (false !== ( $item = readdir($handle) )) {
            if ($item != "." && $item != "..")
                is_dir("$path/$item") ? delFile("$path/$item", $delDir) : unlink("$path/$item");
        }
        closedir($handle);
        if ($delDir) return rmdir($path);
    }else {
        if (file_exists($path)) {
            return unlink($path);
        } else {
            return FALSE;
        }
    }
}


/**
 * 判断当前访问的用户是  PC端  还是 手机端  返回true 为手机端  false 为PC 端
 * @return boolean
 */
/**
　　* 是否移动端访问访问
　　*
　　* @return bool
　　*/
function isMobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;

    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA']))
    {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT']))
    {
        $clientkeywords = array ('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile');
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            return true;
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT']))
    {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
        {
            return true;
        }
    }
    return false;
}


function encrypt($data, $key)
{
    $key	=	md5($key);
    $x		=	0;
    $len	=	strlen($data);
    $l		=	strlen($key);
    for ($i = 0; $i < $len; $i++)
    {
        if ($x == $l)
        {
            $x = 0;
        }
        $char .= $key{$x};
        $x++;
    }
    for ($i = 0; $i < $len; $i++)
    {
        $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);
    }
    return base64_encode($str);
}

function decrypt($data, $key)
{
    $key = md5($key);
    $x = 0;
    $data = base64_decode($data);
    $len = strlen($data);
    $l = strlen($key);
    for ($i = 0; $i < $len; $i++)
    {
        if ($x == $l)
        {
            $x = 0;
        }
        $char .= substr($key, $x, 1);
        $x++;
    }
    for ($i = 0; $i < $len; $i++)
    {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1)))
        {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        }
        else
        {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return $str;
}

function approvalGetSign($data) {
    foreach($data as $k=>$v){
        if(is_null($v)){
            $data[$k]='';
        }
    }
    $tmp = $data;
    ksort($tmp);
    $sign = '';
    $secret = $data['YW_TOKEN'];
    foreach ($tmp as $k => $v) {
        $sign .= "{$k}={$v}&";
    }
    $sign .= $secret;
    $ret = md5($sign);
    return $ret;
}

function actLog($msg,$data=[],$login=1){

    $url= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    if($login){
        $admin_info=session('admin_user');
        $admin_info['group_name']=admin_group($admin_info['id'],'name');
    }else{
        $admin_info=[
            'admin_id'=>0,
            'admin_name'=>'',
            'admin_mobile'=>'',
            'group_name'=>''
        ];
    }

    $log=[
        'url'=>$url,
        'admin_id'=>$admin_info['id'],
        'admin_name'=>$admin_info['username'],
        'mobile'=>$admin_info['mobile'],
        'group_name'=>$admin_info['group_name'],
        'create_time'=>date('Y-m-d H:i:s'),
        'content'=>$msg,
        'ip'=>getIp(),
        'data'=>serialize($data)
    ];

    \think\Db::name('act_log')->insertGetId($log);
}


/**
 * @param $arr
 * @param $key_name
 * @return array
 * 将数据库中查出的列表以指定的 id 作为数组的键名
 */
function convert_arr_key($arr, $key_name)
{
    $arr2 = array();
    foreach($arr as $key => $val){
        $arr2[$val[$key_name]] = $val;
    }
    return $arr2;
}


function getSize($file)
{
    if(!is_file('.'.trim($file,'.'))){
        return 0;
    }
    $filesize=@ filesize('.'.trim($file,'.'));
    if ($filesize >= 1073741824) {
        $filesize = round($filesize / 1073741824 * 100) / 100 . ' GB';
    } elseif ($filesize >= 1048576) {
        $filesize = round($filesize / 1048576 * 100) / 100 . ' MB';
    } elseif ($filesize >= 1024) {
        $filesize = round($filesize / 1024 * 100) / 100 . ' KB';
    } else {
        $filesize = $filesize . ' 字节';
    }
    return $filesize;
}


function isEmail($email){
    if (!preg_match('/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/',$email)) {
        return false;
    }
    return true;
}

/**
 * 导入excel文件
 * @param  string $file excel文件路径
 * @return array        excel文件内容数组
 */
function import_excel($file){
    // 判断文件是什么格式
    $type = pathinfo($file);
    $type = strtolower($type["extension"]);
    if ($type=='xlsx') {
        $type='Excel2007';
    }elseif($type=='xls') {
        $type = 'Excel5';
    }
    ini_set('max_execution_time', '0');

    // 判断使用哪种格式
    $objReader = \PHPExcel_IOFactory::createReader($type);
    $objPHPExcel = $objReader->load($file);
    $sheet = $objPHPExcel->getSheet(0);
    // 取得总行数
    $highestRow = $sheet->getHighestRow();
    //P($highestRow);

    // 取得总列数
    //$highestColumn = $sheet->getHighestColumn();
    //P($highestColumn);
    //总列数转换成数字
    //$numHighestColum = \PHPExcel_Cell::columnIndexFromString("$highestColumn");
    //P($numHighestColum);
    //最多读取30列
    $numHighestColum=30;
    //循环读取excel文件,读取一条,插入一条
    $data=array();
    //P($highestRow);
    //从第一行开始读取数据
    //P($numHighestColum);
    $column_count=0;//根据第一行表头个数判断实际有多少列
    for($j=1;$j<=$highestRow;$j++){
        //从A列读取数据
        $line_data=[];
        for($k=0;$k<=$numHighestColum;$k++){
            //数字列转换成字母
            $columnIndex = \PHPExcel_Cell::stringFromColumnIndex($k);
            // 读取单元格
            $column_data=$objPHPExcel->getActiveSheet()->getCell("$columnIndex$j")->getValue();
            if($j==1 && empty($column_data)){
                $column_count=count($line_data);continue;
            }
            if($j>1 && $k+1>$column_count)break;
            $line_data[$k]=$column_data;
        }
        //每行的前两列放必填数据，如果没有就认定为空行结束循环，发现空行立即结束循环，后面的数据不再读取
        if($j<=1)continue;
        if(empty($line_data[0]) && empty($line_data[1]))break;
        if(!is_string($line_data[0]) && !is_numeric($line_data[0]))break;
        $data[$j]=$line_data;
    }
    return $data;
}

function get_content_video($str){
    preg_match_all("/<video[^<>]*src=[\"]([^\"]+)[\"][^<>]*>/im",$str,$matches);
    return $matches[1];
}

function get_to_users($to_user_ids){
    $usernames=Db::name('member')->where('id','in',explode(',',$to_user_ids))->column('username');
    return implode(',',$usernames);
}

function hideMobile($mobile){
    return  preg_replace('/(1[23456789]{1}[0-9])[0-9]{4}([0-9]{4})/i','$1****$2',$mobile);
}


/**
 * 去除Html所有标签、空格以及空白，并截取字符串（包括中文）
 * @param  string $string 字符串
 * @param  number $sublength 字符串长度
 * @param  string $encoding 编码方式
 * @param  string $ellipsis 省略号  
 */
function cutstr_html($string, $sublength = 230, $encoding = 'utf-8', $ellipsis = '…')
{
    $string=html_entity_decode(htmlspecialchars_decode($string));
    $string = strip_tags($string);
    $string = trim($string);
    $string = mb_ereg_replace("\t", " ", $string);
    $string = mb_ereg_replace("\r\n", " ", $string);
    $string = mb_ereg_replace("\r", " ", $string);
    $string = mb_ereg_replace("\n", " ", $string);
    //$string = mb_ereg_replace(" ", "", $string);
    if (mb_strlen(trim($string),'utf-8')<$sublength){
        return trim($string);
    } else {
        return mb_strcut(trim($string), 0, $sublength, $encoding) . $ellipsis;
    }
}


function getTreeData($data,$type='tree',$name='name',$child='id',$parent='pid',$child_name='_data',$p_key=true,$spread=false){
    $obj=new \app\yiwang\extend\Data();
    if($type=='tree'){
        $data=$obj->tree($data,$name,$child,$parent);
    }elseif($type="level"){
        //P($data);
        //$data, $pid = 0, $html = "&nbsp;", $fieldPri = 'id', $fieldPid = 'pid', $level = 1,$child_name='_data'
        $data=$obj->channelLevel($data,0,'&nbsp;',$child,$parent,1,$child_name,$p_key,$spread);
    }
    return $data;
}

function removeEmoji($nickname) {

    $clean_text = "";

    // Match Emoticons
    $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
    $clean_text = preg_replace($regexEmoticons, '', $nickname);

    // Match Miscellaneous Symbols and Pictographs
    $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
    $clean_text = preg_replace($regexSymbols, '', $clean_text);

    // Match Transport And Map Symbols
    $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
    $clean_text = preg_replace($regexTransport, '', $clean_text);

    // Match Miscellaneous Symbols
    $regexMisc = '/[\x{2600}-\x{26FF}]/u';
    $clean_text = preg_replace($regexMisc, '', $clean_text);

    // Match Dingbats
    $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
    $clean_text = preg_replace($regexDingbats, '', $clean_text);

    return $clean_text;
}


function create_xls($data,$filename='simple.xls'){
    ini_set('memory_limit','800M');
    ini_set('max_execution_time','0');
    $filename=str_replace('.xls', '', $filename).'.xls';
    $phpexcel = new PHPExcel();
    $phpexcel->getProperties()
        ->setCreator("一网科技")
        ->setLastModifiedBy("一网科技")
        ->setTitle("Office 2007 XLSX Test Document")
        ->setSubject("Office 2007 XLSX Test Document")
        ->setDescription("一网科技")
        ->setKeywords("office 2007 openxml php")
        ->setCategory("Test result file");
    PHPExcel_CachedObjectStorageFactory::cache_to_discISAM;
    $phpexcel->getActiveSheet()->fromArray($data);
    $phpexcel->getActiveSheet()->setTitle('Sheet1');
    $phpexcel->setActiveSheetIndex(0);
    header('Content-Type: application/vnd.ms-excel');
    header("Content-Disposition: attachment;filename=$filename");
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0
    $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
    $objwriter->save('php://output');
    exit;
}

/**
 * 解析url中参数信息，返回参数数组
 */
function convertUrlQuery($query)
{
    $queryParts = explode('&', $query);

    $params = array();
    foreach ($queryParts as $param) {
        $item = explode('=', $param);
        $params[urldecode($item[0])] = urldecode($item[1]);
    }

    return $params;
}
function getFirstCharter($str){
    if(empty($str)){return '';}
    if(is_numeric($str{0})) return $str{0};// 如果是数字开头 则返回数字
    $fchar=ord($str{0});
    if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0}); //如果是字母则返回字母的大写
    $s1=iconv('UTF-8','gb2312',$str);
    $s2=iconv('gb2312','UTF-8',$s1);
    $s=$s2==$str?$s1:$str;
    $asc=ord($s{0})*256+ord($s{1})-65536;
    if($asc>=-20319&&$asc<=-20284) return 'A';//这些都是汉字
    if($asc>=-20283&&$asc<=-19776) return 'B';
    if($asc>=-19775&&$asc<=-19219) return 'C';
    if($asc>=-19218&&$asc<=-18711) return 'D';
    if($asc>=-18710&&$asc<=-18527) return 'E';
    if($asc>=-18526&&$asc<=-18240) return 'F';
    if($asc>=-18239&&$asc<=-17923) return 'G';
    if($asc>=-17922&&$asc<=-17418) return 'H';
    if($asc>=-17417&&$asc<=-16475) return 'J';
    if($asc>=-16474&&$asc<=-16213) return 'K';
    if($asc>=-16212&&$asc<=-15641) return 'L';
    if($asc>=-15640&&$asc<=-15166) return 'M';
    if($asc>=-15165&&$asc<=-14923) return 'N';
    if($asc>=-14922&&$asc<=-14915) return 'O';
    if($asc>=-14914&&$asc<=-14631) return 'P';
    if($asc>=-14630&&$asc<=-14150) return 'Q';
    if($asc>=-14149&&$asc<=-14091) return 'R';
    if($asc>=-14090&&$asc<=-13319) return 'S';
    if($asc>=-13318&&$asc<=-12839) return 'T';
    if($asc>=-12838&&$asc<=-12557) return 'W';
    if($asc>=-12556&&$asc<=-11848) return 'X';
    if($asc>=-11847&&$asc<=-11056) return 'Y';
    if($asc>=-11055&&$asc<=-10247) return 'Z';
    return null;
}

function changeTimeType($seconds){
    if ($seconds>3600){
        $hours = intval($seconds/3600);
        $time = $hours.":".gmstrftime('%M:%S', $seconds);
    }else{
        $time = gmstrftime('%H:%M:%S', $seconds);
    }
    return $time;
}

function periodDate($start_time,$end_time){
    $start_time = strtotime($start_time);
    $end_time = strtotime($end_time);
    $i=0;
    while ($start_time<=$end_time){
        $arr[$i]=date('Y-m-d',$start_time);
        $start_time = strtotime('+1 day',$start_time);
        $i++;
    }
    return $arr;
}

function ajax_return($status=0,$msg='',$data=array(),$type = 'json'){
    if(empty($msg)){
        $msg='';
    }

    foreach($data as $k=>&$v){
        if(is_array($v) && empty($v)){
            $v=[];
        }else{
            if((empty($v) || $v=='null' || $v==null) && $v!=0){
                $v='';
            }
        }
    }
    //P($data);
    $return=array();
    //$code=1;
    if($status==1){
        //$code=0;
    }
    //$return['code']=$code;
    $return['status']=$status;
    $return['msg']=$msg;
    $return['data']=$data;
    if($type=='json'){
        $json=json_encode($return);
        $json=str_replace('null','""',$json);
        //$json=str_replace('0.00','""',$json);
        exit($json);
    }
}


/**
 * 获取图片的Base64编码(不支持url)
 * @date 2017-02-20 19:41:22
 *
 * @param $img_file 传入本地图片地址
 *
 * @return string
 */
function imgToBase64($img_file) {
    $img_file='.'.$img_file;
    $img_base64 = '';
    if (file_exists($img_file)) {
        $app_img_file = $img_file; // 图片路径
        $img_info = getimagesize($app_img_file); // 取得图片的大小，类型等

        //echo '<pre>' . print_r($img_info, true) . '</pre><br>';
        $fp = fopen($app_img_file, "r"); // 图片是否可读权限

        if ($fp) {
            $filesize = filesize($app_img_file);
            $content = fread($fp, $filesize);
            $file_content = chunk_split(base64_encode($content)); // base64编码
            switch ($img_info[2]) {           //判读图片类型
                case 1: $img_type = "gif";
                    break;
                case 2: $img_type = "jpg";
                    break;
                case 3: $img_type = "png";
                    break;
            }

            $img_base64 = 'data:image/' . $img_type . ';base64,' . $file_content;//合成图片的base64编码

        }
        fclose($fp);
    }

    return str_replace("\r\n",'',$img_base64); //返回图片的base64
}

/**
 * 生日转年龄
 * @author he
 * @parameter birthday:yyyy-mm-dd
 * @return str
 */
function birthdaytoage($birthday)
{
    $age = 0;
    $year = date('Y',strtotime($birthday));
    $month = date('m',strtotime($birthday));
    $day = date('d',strtotime($birthday));

    $now_year = date('Y');
    $now_month = date('m');
    $now_day = date('d');

    if ($now_year >= $year) {
        $age = $now_year - $year - 1;
        if ($now_month > $month) {
            $age++;
        } else if ($now_month == $month) {
            if ($now_day >= $day) {
                $age++;
            }
        }
    }
    if(empty($birthday)){
        $age = '未知';
    }
    return $age;
}

function get_file_name_from_folder($folder){
    $handler = opendir($folder);

//2、循环的读取目录下的所有文件
//其中$filename = readdir($handler)是每次循环的时候将读取的文件名赋值给$filename，为了不陷于死循环，所以还要让$filename !== false。一定要用!==，因为如果某个文件名如果叫’0′，或者某些被系统认为是代表false，用!=就会停止循环*/
    $filenames=[];
    while( ($filename = readdir($handler)) !== false ) {
        //3、目录下都会有两个文件，名字为’.'和‘..’，不要对他们进行操作
        if($filename != "." && $filename != ".."){
            //4、进行处理
            //这里简单的用echo来输出文件名
            $filenames[]= $filename;
        }
    }
    return $filenames;
}


//获取目录下的所有文件，包括子目录
function getAllFilesFromDir($path,&$files=[]){

    if(is_dir($path)){

        $dir = scandir($path);
        foreach ($dir as $value){
            $sub_path =$path .'/'.$value.'/';
            if($value == '.' || $value == '..'){
                continue;
            }else if(is_dir($sub_path)){
                getAllFilesFromDir($sub_path,$files);
            }else{
                //.$path 可以省略，直接输出文件名
                $files[]=$path.$value;
            }
        }
    }
    return $files;
}

function format_html($html,$data=[],$is_ping=false){
    $html=html_entity_decode(htmlspecialchars_decode($html));
    return $html;
    if($is_ping){
        $header='<h1 style="font-size:20px;">'.$data['title'].'</h1>';
        $header.='<p style="font-size:13px;text-align:center;">浏览量：'.$data['view'].'　　发布时间：'.$data['add_time'].'</p>';
        $font_size=16;
    }else{
        $header='';
        $font_size=16;
    }
    //P($html);
    $html=<<<EOF
        <html>
        <head>
        <meta charset="utf-8">
        <meta id="viewport" name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=false" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="black" />
        <meta name="black" name="apple-mobile-web-app-status-bar-style" />
        <title>webview</title>
        <style>
            img{width:100%;}
            img{height:auto;}
            img{max-width:100%;width:auto;height:auto;}
            table{width:100%;}
            *{font-size:{$font_size}px;}
        </style>
        </head>
        <body>
            {$header}
            <div>$html</div>
        </body>
        <script type="text/javascript">
             window.onload = function(){
                 var maxwidth=document.body.clientWidth;
                 for(i=0;i <document.images.length;i++){
                     var myimg = document.images[i];
                     if(myimg.width > maxwidth){
                         myimg.style.width = "100%";
                         myimg.style.height = "auto";
                     }
                 }
             }
         </script>
        </html>
EOF;
    return $html;
}



/**
 * @todo敏感词过滤，返回结果
 * @paramarray $list 定义敏感词一维数组
 * @paramstring $string 要过滤的内容
 * @returnstring $log 处理结果
 */

function sensitive($string)
{

    $sensitive_words=cache('sensitive_words');
    if(empty($sensitive_words)){
        $sensitive_words=Db::name('sensitive')
            ->column('word');
        cache('sensitive_words',$sensitive_words);
    }

    $handle=\DfaFilter\SensitiveHelper::init()->setTree($sensitive_words);
    $filterContent = $handle->replace($string, '*', true);

    return $filterContent;
}


function check_sensitive_string($string){
    $easy_wechat=new \app\wchat\controller\Easywechat();
    $res=$easy_wechat->check_sensitive_string($string);
    //P($res);
    if($res){
        ajax_return(-1,'含有违规内容，请重新填写');
    }
}


function check_sensitive_img($img){
    $easy_wechat=new \app\wchat\controller\Easywechat();
    $res=$easy_wechat->check_sensitive_img($img);
    if($res){
        ajax_return(-1,'图片涉嫌违规，请重新上传');
    }
}


/**
 * 获取已经过了多久
 * PHP时间转换
 * 刚刚、几分钟前、几小时前
 * 今天昨天前天几天前
 * @param  string $targetTime 时间戳
 * @return string
 */
function get_last_time($targetTime)
{
    if(!is_numeric($targetTime)){
        $targetTime=strtotime($targetTime);
    }
    // 今天最大时间
    $todayLast   = strtotime(date('Y-m-d 23:59:59'));
    $agoTimeTrue = time() - $targetTime;
    $agoTime     = $todayLast - $targetTime;
    $agoDay      = floor($agoTime / 86400);

    if ($agoTimeTrue < 60) {
        $result = '刚刚';
    } elseif ($agoTimeTrue < 3600) {
        $result = (ceil($agoTimeTrue / 60)) . '分钟前';
    } elseif ($agoTimeTrue < 3600 * 12) {
        $result = (ceil($agoTimeTrue / 3600)) . '小时前';
    } elseif ($agoDay == 0) {
        $result = '今天 ' . date('H:i', $targetTime);
    } elseif ($agoDay == 1) {
        $result = '昨天 ' . date('H:i', $targetTime);
    } elseif ($agoDay == 2) {
        $result = '前天 ' . date('H:i', $targetTime);
    } elseif ($agoDay > 2 && $agoDay < 16) {
        $result = $agoDay . '天前 ' . date('H:i', $targetTime);
    } else {
        $format = date('Y') != date('Y', $targetTime) ? "Y-m-d H:i" : "m-d H:i";
        $result = date($format, $targetTime);
    }
    return $result;
}


function get_sub_ids($table,$ids,$all_sub_ids=[]){
    $sub_ids=Db::name($table)
        ->where('pid','in',$ids)
        ->column('id');
    if(!is_array($sub_ids)){
        $sub_ids=[$sub_ids];
    }
    //P($sub_ids);
    if(empty($sub_ids)){
        $sub_ids=[];
    }
    //P($sub_ids);

    $sub_sub_ids=Db::name($table)
        ->where('pid','in',$sub_ids)
        ->column('id');
    if(!is_array($sub_sub_ids)){
        $sub_sub_ids=[$sub_sub_ids];
    }
    if(empty($sub_sub_ids)){
        $sub_sub_ids=[];
    }
    $all_sub_ids=array_merge($sub_ids,$sub_sub_ids);
    return $all_sub_ids;
}

function arr2sort($data,$column,$sort=SORT_ASC){
    $sort_column = array_column($data,$column);
    array_multisort($sort_column,$sort,$data);
    return $data;
}


/**
 * func 验证中文姓名
 * @param $name
 * @return bool
 */
function isChineseName($name){
    if (preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,4}$/', $name)) {
        return true;
    } else {
        return false;
    }
}


function get_time_only($time){
    if(empty($time) || $time=='00:00:00'){
        return '';
    }
    return date('H:i',strtotime($time));
}

//将下划线命名转换为驼峰式命名
function convertUnderline ( $str , $ucfirst = true)
{
    $str = ucwords(str_replace('_', ' ', $str));
    $str = str_replace(' ','',lcfirst($str));
    return $ucfirst ? ucfirst($str) : $str;
}


function change_url_to_class($url){
    return str_replace('/','_',$url);
}

//查询用户有多少张票
function ticket_count($member_id,$admin_shop_id=0){
    $where="member_id=$member_id";
    if($admin_shop_id){
        $where.=" and shop_id=$admin_shop_id";
    }
    $count=Db::name('member_ticket')
        ->where($where)
        ->count();
    return $count;
}

//只要时间
function time_only($time){
    if(is_numeric($time)){
        return date('H:i');
    }
    return date('H:i',strtotime($time));
}

function date_only($time){
    if(is_numeric($time)){
        return date('Y-m-d');
    }
    return date('Y-m-d',strtotime($time));
}

//预约人数
function yue_count($schedule_id){
    $count=Db::name('member_ticket')
        ->where('schedule_id',$schedule_id)
        //->sum('people');
        ->count();
    return $count+0;
}

function add0($num,$len=0){
    if(empty($len)){
        return $num;
    }
    if(strlen($num)>=$len){
        return $num;
    }
    return str_repeat('0',$len-strlen($num)).$num;
}

function file_type_bg($url){
    $ext='attachment';
    if(!empty($url)){
        $ext=substr($url, strrpos($url, '.')+1);
    }
    return '/static/plugins/webuploader/images/'.$ext.'.png';
}

function get_url() {
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
    $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
    $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
    return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
}



//仅限前台参数过滤
function param_filter($data){
    if(is_array($data)){
        foreach($data as $k=>$v){
            $data[$k]=htmlspecialchars(addslashes(strip_tags($v)));
        }
    }else{
        $data=htmlspecialchars(addslashes(strip_tags($data)));
    }
    return $data;
}

//下级数量
function fans_count($leader_id){
    if($leader_id<=0){
        return 0;
    }
    $count=\think\Db::name('member')
        ->where('pid',$leader_id)
        ->count();
    return $count;
}

//拥有商品数量
function course_count($member_id,$type=1){
    if(empty($member_id)){
        return 0;
    }
    //根据激活数量算
    $course_ids=\think\Db::name('my_course')
        ->where(['member_id'=>$member_id])
        ->group('course_id')
        ->column('course_id');
    /**根据购买数量算
    $course_ids=\think\Db::name('course_order')
        ->where(['member_id'=>$member_id,'paid'=>1,'is_refund'=>0])
        //->where('validate','>=',date('Y-m-d'))
        ->column('course_ids');

    $course_id=[];
    foreach($course_ids as $v){
        $id_arr=unserialize($v);
        if(!is_array($id_arr) || empty($id_arr)){
            $id_arr=[];
        }
        $course_id=array_merge($course_id,$id_arr);
    }
    $course_ids=array_filter(array_unique($course_id));
    //$course_ids=array_filter(array_unique($course_ids));
    //P($course_ids);
     *  * */
    if($type===1){
        $count=count($course_ids);
        return $count;
    }else{
        return $course_ids;
    }
}

//拥有优惠券数量
function coupon_count($member_id,$type=1){
    if(empty($member_id)){
        return 0;
    }
    $coupon_ids=\think\Db::name('coupon')
        ->where(['member_id'=>$member_id])
        ->column('id');

    if($type===1){
        $count=count($coupon_ids);
        return $count;
    }else{
        return $coupon_ids;
    }
}

//班级数量
function class_count($major_id,$type=1){
    if(empty($major_id)){
        return 0;
    }
    $class_ids=\think\Db::name('clas')
        ->where(['major_id'=>$major_id])
        ->column('id');

    if($type===1){
        $count=count($class_ids);
        return $count;
    }else{
        return $class_ids;
    }
}

function str_trim($str){
    $str=str_replace(' ','',$str);
    $str=str_replace('　','',$str);
    $str=trim($str);
    return $str;
}

//资料数量
function document_count($document_id,$type=1){
    if(empty($document_id)){
        return 0;
    }
    $document_ids=\think\Db::name('document')
        ->where(['pid'=>$document_id])
        ->column('id');
    if($type===1){
        $count=count($document_ids);
        return $count;
    }else{
        return $document_ids;
    }
}

//题库列表去除标签
function tk_title($str){
    $str=html_entity_decode($str);
    $str=preg_replace('/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i', '[图片]', $str);
    $str=strip_tags($str);
    return cutstr_html($str,60);
}

function save_base64_image($base64_image_content,$path){
    //匹配出图片的格式
    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
        $type = $result[2];
        $new_file = $path.date('Ymd',time())."/";
        if(!file_exists('.'.$new_file)){
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir('.'.$new_file,0777,true);

        }
        $new_file = $new_file.md5(uniqid().time()).".{$type}";
        if (file_put_contents('.'.$new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
            return BASE_URL.trim($new_file,'.');
        }else{
            return false;
        }
    }else{
        if(strpos($base64_image_content,'http')!==false){
            return $base64_image_content;
        }
        return false;
    }
}

function show_textarea($text){
    return str_replace("\r\n",'<br/>',$text);
}

/**
 * 检测用户当前浏览器
 * @return boolean 是否ie浏览器
 */
function isIE() {
    $userbrowser = $_SERVER['HTTP_USER_AGENT'];
    //P($userbrowser);
    if ( preg_match( '/MSIE/i', $userbrowser )  || preg_match( '/Triden/i', $userbrowser ) ) {
        $usingie = true;
    } else {
        $usingie = false;
    }
    //P($usingie);
    return $usingie;
}

//查询管理员所属用户组
function admin_group($admin_id,$type='name'){
    $group_ids=\think\Db::name('auth_group_access')
        ->where('uid',$admin_id)
        ->column('group_id');
    switch ($type){
        case 'name':
            //用户组名称
            $group_names=\think\Db::name('authGroup')
                ->where('id','in',$group_ids)
                ->column('title');
            return implode('、',$group_names);
            break;
        case 'id':
            //用户组ID
            return $group_ids;
            break;
        case 'rules':
            //所在用户组拥有的权限ID
            $rules=\think\Db::name('authGroup')
                ->where('id','in',$group_ids)
                ->column('rules');
            return explode(',',implode(',',$rules));
            break;
        default:
            return '';

    }
}

//直播开始时间
function live_start($start_time){
    return date('Y-m-d H:i',strtotime($start_time));
}

//直播结束时间
function live_end($end_time){
    return date('H:i',strtotime($end_time));
}

function news_count($cate_id=0,$school_id=0,$station_id=0){
    $where='is_delete=0';
    if($cate_id){
        $where.=" and cate_id=$cate_id";
    }
    if($school_id){
        $where.=" and school_id=$school_id";
    }
    if($station_id){
        $where.=" and staticon_id=$station_id";
    }
    $count=\think\Db::name('news')
        ->where($where)
        ->count();
    return $count;
}

function member_count($school_id=0,$station_id=0){
    $where="is_delete=0";
    if($school_id){
        $where.=" and school_id=".$school_id;
    }
    if($station_id){
        $where.=" and station_id=$station_id";
    }
    $count=\think\Db::name('member')
        ->where($where)
        ->count();
    return $count;
}

function major_member_count($major_id=0,$station_id=0,$teacher_id=0){
    if(empty($major_id)){
        return 0;
    }
    $where='1=1';
    if($station_id){
        $where.=" and station_id=".$station_id;
    }
    if($teacher_id){
        $where.=" and teacher_ids like '%:\"{$teacher_id}\";%'";
    }
    $count=\think\Db::name('member')
        ->where('major_ids','like','%:"'.$major_id.'";%')
        ->where($where)
        ->count();
    return $count;
}

function finish_major_count($major_id){
    $count=\think\Db::name('member_major')
        ->where(['major_id'=>$major_id,'is_finish'=>1])
        ->count();
    return $count;
}

function bo_time($course_id){
    $section=\think\Db::name('section')
        ->where(['open'=>1,'course_id'=>$course_id])
        ->where('start_time','>=',date('Y-m-d H:i:s'))
        ->order('start_time asc')
        ->where('start_time is not null and start_time <> ""')
        ->find();
    if(!empty($section)){
        return date('Y-m-d H:i',strtotime($section['start_time']));
    }
    return '暂无';
}

//奖励积分
function give_point($member_id,$point=0,$type=0,$title='',$order_id=0){
    if($point==0){
        return true;
    }
    if(empty($type)){
        return false;
    }

    if(empty($title)){
        if($point>0){
            $title='获取余额';
        }else{
            $title='扣除余额';
        }
    }
    $member=\think\Db::name('member')
        ->where(['open'=>1,'is_delete'=>0,'id'=>$member_id])
        ->find();
    if(empty($member)){
        return false;
    }
    if($point<=0 && $member['point']<abs($point)){
        return false;
    }
    \think\Db::startTrans();
    $res=\think\Db::name('member')
        ->where('id',$member_id)
        ->setInc('point',$point);
    if(!$res){
        \think\Db::rollback();
        return false;
    }
    $res=\think\Db::name('point_log')
        ->insertGetId([
            'member_id'=>$member_id,
            'type'=>$type,
            'title'=>$title,
            'point'=>$point,
            'intro'=>$title,
            'create_time'=>date('Y-m-d H:i:s'),
            'update_time'=>date('Y-m-d H:i:s'),
            'date'=>date('Y-m-d'),
            'order_id'=>$order_id
        ]);
    if(!$res){
        \think\Db::rollback();
        return false;
    }
    \think\Db::commit();
    return true;
}

function push_msg($data){
    return true;
    // 建立socket连接到内部推送端口
    $client = stream_socket_client('tcp://127.0.0.1:2344', $errno, $errmsg, 1);
    //P($errmsg);
    // 推送的数据，包含uid字段，表示是给这个uid推送
    $data['type']='msg';
    $data['uid']=$data['member_id'];

    $res=fwrite($client, json_encode($data)."\n");
    //P($res);
    // 读取推送结果
    $res= fread($client, 8192);
    //P($res);

    //\think\facade\Cookie::set('tip_msg',$data['content']);
}

function get_week($time ='',$format='Y-m-d'){
    $time =$time !='' ?$time : time();
    //获取当前周几
    $week =date('w',$time);
    $date = [];
    for ($i=1;$i<=7;$i++){
        $date[$i] =date($format ,strtotime('+' .$i-$week .' days',$time));
    }
    return $date;
}

function study_time($study_time,$type='string'){
    if($type==='string'){
        return floor($study_time/3600).'时'.round(($study_time%3600)/60).'分';
    }else{
        return ['hours'=>floor($study_time/3600),'minutes'=>round(($study_time%3600)/60)];
    }

}

function order_items($course_name){
    return str_replace(',','<br/>',$course_name);
}

function order_comment_count($order_id){
    $count=\think\Db::name('comment')
        ->where(['order_id'=>$order_id,'is_delete'=>0])
        ->count();
    return $count;
}

function course_comment_count($course_id){
    $count=\think\Db::name('comment')
        ->where(['course_id'=>$course_id,'is_delete'=>0])
        ->count();
    return $count;
}

//获取两个日期中间的所有日期
function get_dates($start_date,$end_date){
    $dates=[];
    while (strtotime($start_date)<=strtotime($end_date)){
        $dates[]=date('Y-m-d',strtotime($start_date));
        $start_date = date('Y-m-d',strtotime('+1 day',strtotime($start_date)));
    }
    return $dates;
}


function word_export($data){
    $str='';

    $phpWord = new PhpOffice\PhpWord\PhpWord();
    //调整页面样式
    $sectionStyle = array('orientation' => null,
        'marginLeft' => 400,
        'marginRight' => 400,
        'marginTop' => 400,
        'marginBottom' => 400);
    $section = $phpWord->addSection($sectionStyle);

    //添加页眉
    $header = $section->addHeader();
    $k = $header->addTextRun();
    if($data['head_img']){
        //页眉图片
        $k->addImage($data['head_img'], array(
            'width' => '100%',
            'height' => 60,
            'marginTop' => -1,
            'marginLeft' => 1,
            'wrappingStyle' => 'behind',
        ));
        $section->addTextBreak(2);
    }

    //添加页脚
    $footer = $section->addFooter();
    $f = $footer->addTextRun();

    if($data['foot_img']){
        $f->addImage($data['foot_img'], array(
            'width' => 580,
            'height' => 140,
            'marginTop' => -1,
            'marginLeft' => 1,
            'wrappingStyle' => 'behind',
        ));
    }

    //添加页脚页码
    $footer->addPreserveText('Page {PAGE} of {NUMPAGES}.', array('align' => 'center'));

    $section->addText(
        $data['child_name'] . " 的错题本，时间：" . date("Y-m-d"),
        array('name' => '黑体', 'size' => 15),
        array('align' => 'center')
    );

    //添加换行符
    $section->addTextBreak(2);

    //添加文本，处理文本
    $strs = explode(").", $str);
    $arr = array_filter($strs);
    foreach ($arr as $k => $v) {
        $section->addText(
            $v . ").",
            array('name' => 'Arial', 'size' => 13),
            array('lineHeight' => 1.5, 'indent' => 1)
        );
        $section->addTextBreak(1); //添加换行
    }

//
//    $tk=parent::makeToken();
//    $name="write_".$tk.".docx";
//    $phpWord->save($name,"Word2007",true);
//    exit();

    //保存到项目目录

    $objwrite = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord);
    $path = $data['path'];
    $file_name=$data['file_name'];
    $dir = iconv("UTF-8", "GBK", $path);
    if (!file_exists($dir)) {
        @mkdir($dir, 0777, true);
    }

    $objwrite->save($path.'/'.$file_name);
    return $path.'/'.$file_name;
}

//兑换码
function code_count($course_id,$batch_id=0){
    //if(empty((int)$course_id))return 0;
    //$where='course_id='.$course_id;
    $where='1=1';
    if($batch_id){
        $where.=' and batch_id='.$batch_id;
    }
    $count=\think\Db::name('code')
        ->where($where)
        ->count();
    return $count+0;
}

function dh_count($course_id,$batch_id=0){
    //if(empty((int)$course_id))return 0;
    //$where='is_dh=1 and course_id='.$course_id;
    $where='is_dh=1';
    if($batch_id){
        $where.=' and batch_id='.$batch_id;
    }
    $count=\think\Db::name('code')
        ->where($where)
        ->count();
    return $count+0;
}

function birthday2age($birthday){
    $age = strtotime($birthday);
    if($age === false){
        return false;
    }
    list($y1,$m1,$d1) = explode("-",date("Y-m-d",$age));
    $now = strtotime("now");
    list($y2,$m2,$d2) = explode("-",date("Y-m-d",$now));
    $age = $y2 - $y1;
    if((int)($m2.$d2) < (int)($m1.$d1))
        $age -= 1;
    return $age;
}

function get_device_type()
{
    //全部变成小写字母
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $type ='other';
    //分别进行判断
    if(strpos($agent,'iphone') || strpos($agent,'ipad'))
    {
        $type ='ios';
    }

    if(strpos($agent,'android'))
    {
        $type ='android';
    }
    return$type;
}

function filter($str)
{
    if (empty($str)) return false;
    $str = htmlspecialchars($str);
    $str = str_replace( '/', "", $str);
    $str = str_replace( '"', "", $str);
    $str = str_replace( '(', "", $str);
    $str = str_replace( ')', "", $str);
    $str = str_replace( 'CR', "", $str);
    $str = str_replace( 'ASCII', "", $str);
    $str = str_replace( 'ASCII 0x0d', "", $str);
    $str = str_replace( 'LF', "", $str);
    $str = str_replace( 'ASCII 0x0a', "", $str);
    $str = str_replace( ',', "", $str);
    $str = str_replace( '%', "", $str);
    $str = str_replace( ';', "", $str);
    $str = str_replace( 'eval', "", $str);
    $str = str_replace( 'open', "", $str);
    $str = str_replace( 'sysopen', "", $str);
    $str = str_replace( 'system', "", $str);
    $str = str_replace( '$', "", $str);
    $str = str_replace( "'", "", $str);
    $str = str_replace( "'", "", $str);
    $str = str_replace( 'ASCII 0x08', "", $str);
    $str = str_replace( '"', "", $str);
    $str = str_replace( '"', "", $str);
    $str = str_replace("", "", $str);
    $str = str_replace("&gt", "", $str);
    $str = str_replace("&lt", "", $str);
    $str = str_replace("<SCRIPT>", "", $str);
    $str = str_replace("</SCRIPT>", "", $str);
    $str = str_replace("<script>", "", $str);
    $str = str_replace("</script>", "", $str);
    $str = str_replace("select","",$str);
    $str = str_replace("join","",$str);
    $str = str_replace("union","",$str);
    $str = str_replace("where","",$str);
    $str = str_replace("insert","",$str);
    $str = str_replace("delete","",$str);
    $str = str_replace("update","",$str);
    $str = str_replace("like","",$str);
    $str = str_replace("drop","",$str);
    $str = str_replace("DROP","",$str);
    $str = str_replace("create","",$str);
    $str = str_replace("modify","",$str);
    $str = str_replace("rename","",$str);
    $str = str_replace("alter","",$str);
    $str = str_replace("cas","",$str);
    $str = str_replace("&","",$str);
    $str = str_replace(">","",$str);
    $str = str_replace("<","",$str);
    $str = str_replace(" ",chr(32),$str);
    $str = str_replace(" ",chr(9),$str);
    $str = str_replace("    ",chr(9),$str);
    $str = str_replace("&",chr(34),$str);
    $str = str_replace("'",chr(39),$str);
    $str = str_replace("<br />",chr(13),$str);
    $str = str_replace("''","'",$str);
    $str = str_replace("css","'",$str);
    $str = str_replace("CSS","'",$str);
    $str = str_replace("<!--","",$str);
    $str = str_replace("convert","",$str);
    $str = str_replace("md5","",$str);
    $str = str_replace("passwd","",$str);
    $str = str_replace("password","",$str);
    $str = str_replace("../","",$str);
    $str = str_replace("./","",$str);
    $str = str_replace("Array","",$str);
    $str = str_replace("or 1='1'","",$str);
    $str = str_replace(";set|set&set;","",$str);
    $str = str_replace("`set|set&set`","",$str);
    $str = str_replace("--","",$str);
    $str = str_replace("OR","",$str);
    $str = str_replace('"',"",$str);
    $str = str_replace("*","",$str);
    $str = str_replace("-","",$str);
    $str = str_replace("+","",$str);
    $str = str_replace("/","",$str);
    $str = str_replace("=","",$str);
    $str = str_replace("'/","",$str);
    $str = str_replace("-- ","",$str);
    $str = str_replace(" -- ","",$str);
    $str = str_replace(" --","",$str);
    $str = str_replace("(","",$str);
    $str = str_replace(")","",$str);
    $str = str_replace("{","",$str);
    $str = str_replace("}","",$str);
    $str = str_replace("-1","",$str);
    $str = str_replace("1","",$str);
    $str = str_replace(".","",$str);
    $str = str_replace("response","",$str);
    $str = str_replace("write","",$str);
    $str = str_replace("|","",$str);
    $str = str_replace("`","",$str);
    $str = str_replace(";","",$str);
    $str = str_replace("etc","",$str);
    $str = str_replace("root","",$str);
    $str = str_replace("//","",$str);
    $str = str_replace("!=","",$str);
    $str = str_replace("$","",$str);
    $str = str_replace("&","",$str);
    $str = str_replace("&&","",$str);
    $str = str_replace("==","",$str);
    $str = str_replace("#","",$str);
    $str = str_replace("@","",$str);
    $str = str_replace("mailto:","",$str);
    $str = str_replace("CHAR","",$str);
    $str = str_replace("char","",$str);
    return $str;
}

/**
 * 过滤参数
 * @param string $str 接受的参数
 * @return string
 */
function filterWords($str)
{
    $farr = array(
        "/<(\\/?)(script|i?frame|style|html|body|title|link|meta|object|\\?|\\%)([^>]*?)>/isU",
        "/(<[^>]*)on[a-zA-Z]+\s*=([^>]*>)/isU",
        "/select|insert|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile|dump/is"
    );
    $str = preg_replace($farr,'',$str);
    return $str;
}

/**
 * 过滤接受的参数或者数组,如$_GET,$_POST
 * @param array|string $arr 接受的参数或者数组
 * @return array|string
 */
function filterArr($arr)
{
    if(is_array($arr)){
        foreach($arr as $k => $v){
            $arr[$k] = filterWords($v);
        }
    }else{
        $arr = filterWords($arr);
    }
    return $arr;
}

//记录商品销量
function course_sales($order_id){
    $order=\think\Db::name('course_order')
        ->where(['paid'=>1,'id'=>$order_id,'is_refund'=>0])
        ->where(" pay_type <> '' and pay_type is not null and pay_type <> 'offline'")
        ->find();

    //减库存
    $info=unserialize($order['info']);
    foreach($info as $k=>$v){
        \think\Db::name('item_sku')
            ->where('id',$v['attrs']['id'])
            ->setDec('stock',$v['num']);
    }

    //P($order);
    if(empty($order))return false;
    $has_log=\think\Db::name('course_sales')
        ->where('order_id',$order_id)
        ->count();
    if(!empty($has_log))return false;

    $arr=[
        'order_id'=>$order['id'],
        'date'=>$order['date'],
        'year'=>$order['year'],
        'month'=>$order['month'],
        'member_id'=>$order['member_id']
    ];
    $all_data=[];
    foreach($info as $k=>$v){
        $arr['course_id']=$v['course_id'];
        $arr['num']=$v['num'];
        $arr['money']=$v['num']*$v['course']['price'];
        $all_data[]=$arr;
    }
    if(!empty($all_data)){
        \think\Db::name('course_sales')
            ->insertAll($all_data);
    }
}


//上传到阿里云oss
function ali_oss_upload($site_config,$column,$upload_path=''){
    $oss_config=array(
        'KEY_ID'=>$site_config['ali_key_id'],
        'KEY_SECRET'=>$site_config['ali_key_secret'],
        'END_POINT'=>$site_config['ali_endpoint'],
        'BUCKET'=>$site_config['ali_bucket'],
        'BUCKET_URL'=>$site_config['ali_bucket_url']
    );

    $accessKeyId = $oss_config['KEY_ID'];
    $accessKeySecret = $oss_config['KEY_SECRET'];
    $endpoint = $oss_config['END_POINT'];
    $bucket= $oss_config['BUCKET'];
    $bucket_url=$oss_config['BUCKET_URL'];//斜杠结尾
    try{
        if(strpos($column,'./')===false){
            $file = $_FILES[$column]['tmp_name'];
            $pathinfo = pathinfo($_FILES[$column]['name']);
        }else{
            $file=$column;
            $pathinfo=pathinfo($file);
        }

        // 通过pathinfo函数获取图片后缀名
        $ext = $pathinfo['extension'];
        //$upload_path以‘/’结尾，或者为空
        $file_name=substr(md5($file),8,5).date('YmdHis').rand(0,9999);
        $filename = 'upload/'.$upload_path.date('Y-m-d').'/'.$file_name.'.'.$ext;
        $ossClient = new OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
        // 上传时可以设置相关的headers，例如设置访问权限为private、自定义元信息等。
        $options = array(
            \OSS\OssClient::OSS_CONTENT_TYPE => getcontentType($ext)
        );
        $ossClient->uploadFile($bucket, $filename, $file,$options);
        $file_path=$bucket_url.$filename;
        return ['status'=>1,'path'=>$file_path,'filename'=>$file_name,'ext'=>$ext];
    } catch(OSS\Core\OssException $e) {
        return ['status'=>-1,'msg'=>$e->getMessage()];
    }
}

function getcontentType($ext) {
    $ext=strtolower($ext);
    if ($ext==='bmp') {
        return "image/bmp";
    }
    if ($ext==='gif') {
        return "image/gif";
    }
    if (in_array($ext,['jpeg','jpg','png'])) {
        return "image/jpg";
    }
    if($ext==='mp4'){
        return "video/mp4";
    }
    if($ext==='mp3'){
        return "audio/mp3";
    }
    if ($ext==='html') {
        return "text/html";
    }
    if ($ext==='txt') {
        return "text/plain";
    }
    if ($ext==='vsd') {
        return "application/vnd.visio";
    }
    if (in_array($ext,['pptx','ppt'])) {
        return "application/vnd.ms-powerpoint";
    }
    if (in_array($ext,['docx','doc'])) {
        return "application/msword";
    }
    if ($ext==='xml') {
        return "text/xml";
    }
    return "";
}

//列举阿里云oss里面的文件
function ali_oss_file_list($site_config,$upload_path){
    $oss_config=array(
        'KEY_ID'=>$site_config['ali_key_id'],
        'KEY_SECRET'=>$site_config['ali_key_secret'],
        'END_POINT'=>$site_config['ali_endpoint'],
        'BUCKET'=>$site_config['ali_bucket'],
        'BUCKET_URL'=>$site_config['ali_bucket_url']
    );

    $accessKeyId = $oss_config['KEY_ID'];
    $accessKeySecret = $oss_config['KEY_SECRET'];
    $endpoint = $oss_config['END_POINT'];
    $bucket= $oss_config['BUCKET'];
    $bucket_url=$oss_config['BUCKET_URL'];//斜杠结尾
    $ossClient = new OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
    //P($ossClient);
    // 填写前缀，例如dir/。
    $prefix = $upload_path;
    // 填写对文件分组的字符，例如/。
    $delimiter = '/';
    // 本次列举文件的起点。
    $nextMarker = '';
    // 填写列举文件的最大个数。
    $maxkeys = 1000;
    $options = array(
        'delimiter' => $delimiter,
        'prefix' => $prefix,
        'max-keys' => $maxkeys,
        //'marker' => $nextMarker,
    );
    //P($options);
    try {
        $listObjectInfo = $ossClient->listObjects($bucket, $options);
        $objectList = $listObjectInfo->getObjectList(); // 文件列表。
        $prefixList = $listObjectInfo->getPrefixList();
        foreach ($prefixList as $prefixInfo) {
            $path=$prefixInfo->getPrefix();
            $options['prefix']=$path;
            $listObjectInfo1 = $ossClient->listObjects($bucket, $options);
            $objectList1 = $listObjectInfo1->getObjectList(); // 文件列表。
            $objectList=array_merge($objectList,$objectList1);
        }
        $list=[];
        foreach($objectList as $k=>$v){
            $list[]=objectInfoParse($v,$bucket_url);
        }
        //P($list);
        return ['status'=>1,'list'=>$list];
    } catch (OSS\Core\OssException $e) {
        return ['status'=>-1,'msg'=>$e->getMessage()];
    }


}

function objectInfoParse(\OSS\Model\ObjectInfo $objectInfo,$bucket_url) {
    return [
        'url'=>$bucket_url.$objectInfo->getKey(),
        'name'      => $objectInfo->getKey(),
        'size'      => $objectInfo->getSize(),
        'mtime' => $objectInfo->getLastModified(),
    ];
}

function hex2rgb($color) {
    $hexColor = str_replace('#', '', $color);
    $lens = strlen($hexColor);
    if ($lens != 3 && $lens != 6) {
        return false;
    }
    $newcolor = '';
    if ($lens == 3) {
        for ($i = 0; $i < $lens; $i++) {
            $newcolor .= $hexColor[$i] . $hexColor[$i];
        }
    } else {
        $newcolor = $hexColor;
    }
    $hex = str_split($newcolor, 2);
    $rgb = [];
    foreach ($hex as $key => $vls) {
        $rgb[] = hexdec($vls);
    }
    return implode(',',$rgb);
}

function pg_user_count($group_id){
    return \think\Db::table('UserList')
        ->where('permissionGroupID',$group_id)
        ->count();
}

function group_user_count($group_id){
    return \think\Db::table('UserList')
        ->where('group_list_id',$group_id)
        ->count();
}

function departure_time($id){
    return \think\Db::table('FlightRecords')
        ->where('submissionID',$id)
        ->order('FlightRecordsID desc')
        ->find()['DepartureTime'];
}

function line_tower_count($lineID){
    return \think\Db::table('towerList')
        ->where('lineID',$lineID)
        ->count();
}