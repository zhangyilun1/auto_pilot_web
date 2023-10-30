<?php
namespace app\common\extend;
use app\common\controller\Commons;

class Poster extends Commons {
    /**
     * 分享图片生成
     * @param $gData  商品数据，array
     * @param $codeName 二维码图片
     * @param $fileName string 保存文件名,默认空则直接输入图片
     */
    function createSharePng($gData,$codeName,$fileName = ''){
        //创建画布
        $im = imagecreatetruecolor(618, 1000);

        //填充画布背景色
        $color = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $color);

        //字体文件
        $font_file = "./static/poster/msyh.ttf";
        $font_file_bold = "./static/poster/msyh_bold.ttf";

        //设定字体的颜色
        $font_color_1 = ImageColorAllocate ($im, 140, 140, 140);
        $font_color_2 = ImageColorAllocate ($im, 28, 28, 28);
        $font_color_3 = ImageColorAllocate ($im, 129, 129, 129);
        $font_color_red = ImageColorAllocate ($im, 217, 45, 32);

        $fang_bg_color = ImageColorAllocate ($im, 254, 216, 217);

        //Logo
        list($l_w,$l_h) = getimagesize('./static/images/favicon.png');
        $logoImg = @imagecreatefrompng('./static/images/favicon.png');
        imagecopyresized($im, $logoImg, 274, 28, 0, 0, 100, 100, $l_w, $l_h);

        //温馨提示
        imagettftext($im, 14,0, 100, 130, $font_color_1 ,$font_file, '温馨提示：长按图片识别二维码即可前往购买');

        //商品图片
        list($g_w,$g_h) = getimagesize($gData['pic']);
        $goodImg = $this->createImageFromFile($gData['pic']);
        imagecopyresized($im, $goodImg, 0, 185, 0, 0, 618, 618, $g_w, $g_h);

        //二维码
        list($code_w,$code_h) = getimagesize($codeName);
        $codeImg = $this->createImageFromFile($codeName);
        imagecopyresized($im, $codeImg, 440, 820, 0, 0, 170, 170, $code_w, $code_h);

        //商品描述
        $theTitle = $this->cn_row_substr($gData['title'],2,19);
        imagettftext($im, 14,0, 8, 845, $font_color_2 ,$font_file, $theTitle[1]);
        imagettftext($im, 14,0, 8, 875, $font_color_2 ,$font_file, $theTitle[2]);

        if($gData['price']){
            imagettftext($im, 14,0, 8, 935, $font_color_2 ,$font_file, "现价￥");
            imagettftext($im, 28,0, 80, 935, $font_color_red ,$font_file_bold, $gData["price"]);
        }

        if($gData['original_price']){
            imagettftext($im, 14,0, 8,970, $font_color_3 ,$font_file, "原价￥".$gData["original_price"]);
        }


        //优惠券
        if($gData['coupon_price']){
            imagerectangle ($im, 125 , 950 , 160 , 975 , $font_color_3);
            imagefilledrectangle ($im, 126 , 951 , 159 , 974 , $fang_bg_color);
            imagettftext($im, 14,0, 135,970, $font_color_3 ,$font_file, "券");

            $coupon_price = strval($gData['coupon_price']);
            imagerectangle ($im, 160 , 950 , 198 + (strlen($coupon_price)* 10), 975 , $font_color_3);
            imagettftext($im, 14,0, 170,970, $font_color_3 ,$font_file, $coupon_price."元");
        }

        //输出图片
        if($fileName){
            imagepng ($im,$fileName);
        }else{
            Header("Content-Type: image/png");
            imagepng ($im);
        }

        //释放空间
        imagedestroy($im);
        imagedestroy($goodImg);
        imagedestroy($codeImg);
    }

    /**
     * 从图片文件创建Image资源
     * @param $file 图片文件，支持url
     * @return bool|resource    成功返回图片image资源，失败返回false
     */
    function createImageFromFile($file){
        if(preg_match('/http(s)?:\/\//',$file)){
            $fileSuffix = $this->getNetworkImgType($file);
        }else{
            $fileSuffix = pathinfo($file, PATHINFO_EXTENSION);
        }

        if(!$fileSuffix) return false;

        switch ($fileSuffix){
            case 'jpeg':
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'jpg':
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'png':
                $theImage = @imagecreatefrompng($file);
                break;
            case 'gif':
                $theImage = @imagecreatefromgif($file);
                break;
            default:
                $theImage = @imagecreatefromstring(file_get_contents($file));
                break;
        }

        return $theImage;
    }

    /**
     * 获取网络图片类型
     * @param $url  网络图片url,支持不带后缀名url
     * @return bool
     */
    function getNetworkImgType($url){
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $url); //设置需要获取的URL
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //支持https
        curl_exec($ch);//执行curl会话
        $http_code = curl_getinfo($ch);//获取curl连接资源句柄信息
        curl_close($ch);//关闭资源连接

        if ($http_code['http_code'] == 200) {
            $theImgType = explode('/',$http_code['content_type']);

            if($theImgType[0] == 'image'){
                return $theImgType[1];
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 分行连续截取字符串
     * @param $str  需要截取的字符串,UTF-8
     * @param int $row  截取的行数
     * @param int $number   每行截取的字数，中文长度
     * @param bool $suffix  最后行是否添加‘...’后缀
     * @return array    返回数组共$row个元素，下标1到$row
     */
    function cn_row_substr($str,$row = 1,$number = 10,$suffix = true){
        $result = array();
        for ($r=1;$r<=$row;$r++){
            $result[$r] = '';
        }

        $str = trim($str);
        if(!$str) return $result;

        $theStrlen = strlen($str);

        //每行实际字节长度
        $oneRowNum = $number * 3;
        for($r=1;$r<=$row;$r++){
            if($r == $row and $theStrlen > $r * $oneRowNum and $suffix){
                $result[$r] = $this->mg_cn_substr($str,$oneRowNum-6,($r-1)* $oneRowNum).'...';
            }else{
                $result[$r] = $this->mg_cn_substr($str,$oneRowNum,($r-1)* $oneRowNum);
            }
            if($theStrlen < $r * $oneRowNum) break;
        }

        return $result;
    }

    /**
     * 按字节截取utf-8字符串
     * 识别汉字全角符号，全角中文3个字节，半角英文1个字节
     * @param $str  需要切取的字符串
     * @param $len  截取长度[字节]
     * @param int $start    截取开始位置，默认0
     * @return string
     */
    function mg_cn_substr($str,$len,$start = 0){
        $q_str = '';
        $q_strlen = ($start + $len)>strlen($str) ? strlen($str) : ($start + $len);

        //如果start不为起始位置，若起始位置为乱码就按照UTF-8编码获取新start
        if($start and json_encode(substr($str,$start,1)) === false){
            for($a=0;$a<3;$a++){
                $new_start = $start + $a;
                $m_str = substr($str,$new_start,3);
                if(json_encode($m_str) !== false) {
                    $start = $new_start;
                    break;
                }
            }
        }

        //切取内容
        for($i=$start;$i<$q_strlen;$i++){
            //ord()函数取得substr()的第一个字符的ASCII码，如果大于0xa0的话则是中文字符
            if(ord(substr($str,$i,1))>0xa0){
                $q_str .= substr($str,$i,3);
                $i+=2;
            }else{
                $q_str .= substr($str,$i,1);
            }
        }
        return $q_str;
    }


    /**
     * 生成宣传海报
     * @param array  参数,包括图片和文字
     * @param string $filename 生成海报文件名,不传此参数则不生成文件,直接输出图片
     * @return bool|string [type] [description]
     */
    function createPoster($config = array(), $filename = "")
    {
        $imageDefault = array(
            'left' => 0,
            'top' => 0,
            'right' => 0,
            'bottom' => 0,
            'width' => 100,
            'height' => 100,
            'opacity' => 100
        );
        $textDefault = array(
            'text' => '',
            'left' => 0,
            'top' => 0,
            'fontSize' => 32,       //字号
            'fontColor' => '255,255,255', //字体颜色
            'angle' => 0,
        );
        $background = $config['background'];//海报最底层得背景
        //背景方法
        $backgroundInfo = getimagesize($background);
        $backgroundFun = 'imagecreatefrom' . image_type_to_extension($backgroundInfo[2], false);
        $background = $backgroundFun($background);
        $backgroundWidth = imagesx($background);  //背景宽度
        $backgroundHeight = imagesy($background);  //背景高度
        $imageRes = imageCreatetruecolor($backgroundWidth, $backgroundHeight);
        $color = imagecolorallocate($imageRes, 0, 0, 0);
        imagefill($imageRes, 0, 0, $color);
        // imageColorTransparent($imageRes, $color);  //颜色透明
        imagecopyresampled($imageRes, $background, 0, 0, 0, 0, imagesx($background), imagesy($background), imagesx($background), imagesy($background));
        //处理了图片
        if (!empty($config['image'])) {
            foreach ($config['image'] as $key => $val) {
                $val = array_merge($imageDefault, $val);
                $info = getimagesize($val['url']);
                $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
                if ($val['stream']) {   //如果传的是字符串图像流
                    $info = getimagesizefromstring($val['url']);
                    $function = 'imagecreatefromstring';
                }
                $res = $function($val['url']);
                $resWidth = $info[0];
                $resHeight = $info[1];

                //建立画板 ，缩放图片至指定尺寸
                $canvas = imagecreatetruecolor($val['width'], $val['height']);
                imagefill($canvas, 0, 0, $color);

                /*echo '<pre>';
                print_r(1);
                exit;*/
                /* --- 用以处理缩放png图透明背景变黑色问题 开始 --- */
                if($key == 0){
                    //这里背景是黄色，所以写成了‘253,166,18’，用在其它地方改成写成‘255，255，255’
                    $color = imagecolorallocatealpha($canvas,252,248,239,0);
                    imagecolortransparent($canvas,$color);
                    imagefill($canvas,0,0,$color);
                }
                /* --- 用以处理缩放png图透明背景变黑色问题 结束 --- */


                //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
                imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'], $resWidth, $resHeight);
                $val['left'] = $val['left'] < 0 ? $backgroundWidth - abs($val['left']) - $val['width'] : $val['left'];
                $val['top'] = $val['top'] < 0 ? $backgroundHeight - abs($val['top']) - $val['height'] : $val['top'];

                //放置图像
                imagecopymerge($imageRes, $canvas, $val['left'], $val['top'], $val['right'], $val['bottom'], $val['width'], $val['height'], $val['opacity']);//左，上，右，下，宽度，高度，透明度
            }
        }
        //处理文字
        if (!empty($config['text'])) {
            foreach ($config['text'] as $key => $val) {
                $val = array_merge($textDefault, $val);
                list($R, $G, $B) = explode(',', $val['fontColor']);
                $fontColor = imagecolorallocate($imageRes, $R, $G, $B);

                if($val['align']==='center'){
                    //文字居中
                    // 计算出文字在图片中的宽度
                    $p = imagettfbbox($val['fontSize'],0,$val['fontPath'],$val['text']);
                    $txt_width=$p[2]-$p[0];
                    // 获取文字在图片中居中的x轴
                    $x = ($backgroundWidth - $txt_width) / 2;
                    $val['left']=$x;
                }else{
                    //文字居左
                    $val['left'] = $val['left'] < 0 ? $backgroundWidth - abs($val['left']) : $val['left'];
                }

                $val['top'] = $val['top'] < 0 ? $backgroundHeight - abs($val['top']) : $val['top'];

                if($val['width']){
                    $this->textalign($imageRes,$val['text'],$val['width'],$val['left'],$val['top'],$val['fontSize'],$val['fontPath'],$fontColor,24,$val['row_max']);
                }else{
                    imagettftext($imageRes, $val['fontSize']/1.33, $val['angle'], $val['left'], $val['top']+$val['fontSize']-5, $fontColor, $val['fontPath'], $val['text']);
                }
            }
        }

        //这一句一定要有
        imagealphablending( $imageRes,false );

        imagesavealpha($imageRes, true);
        //生成图片
        if (!empty($filename)) {
            unlink($filename);
            $res = imagepng($imageRes, $filename, 5); //保存到本地
            imagedestroy($imageRes);
            if (!$res) return false;
            return $filename;
        } else {
            imagejpeg($imageRes);     //在浏览器上显示
            imagedestroy($imageRes);
        }
    }

    /* 文字自动换行
      * @param $card 画板
      * @param $str 要换行的文字
      * @param $width 文字显示的宽度，到达这个宽度自动换行
      * @param $x 基础 x坐标
      * @param $y 基础Y坐标
      * @param $fontsize 文字大小
      * @param $fontfile 字体
      * @param $color array 字体颜色
      * @param $rowheight 行间距
      * @param $maxrow 最多多少行
      */
    function textalign($card, $str, $width, $x,$y,$fontsize,$fontfile,$color,$rowheight,$maxrow)
    {
        $strs=explode("\r\n",$str);
        //P($strs);
        $row = 0;
        foreach($strs as $str){
            $tempstr = "";
            for ($i = 0; $i < mb_strlen($str, 'utf8'); $i++) {
                if($row >= $maxrow) {
                    break;
                }
                //第一 获取下一个拼接好的宽度 如果下一个拼接好的已经大于width了，就在当前的换行 如果不大于width 就继续拼接
                $tempstr = $tempstr.mb_substr($str, $i, 1, 'utf-8');//当前的文字
                $nextstr = $tempstr.mb_substr($str, $i+1, 1, 'utf-8');//下一个字符串
                $nexttemp = imagettfbbox($fontsize, 0, $fontfile, $nextstr);//用来测量每个字的大小
                $nextsize = ($nexttemp[2]-$nexttemp[0]);
                if($nextsize > $width-10) {
                    //大于整体宽度限制 直接换行 这一行写入画布
                    $row++;
//                $card->imageText($tempstr,$fontsize,$color,$x,$y,$width,1);
                    imagettftext($card, $fontsize/1.33, 0, $x, $y, $color, $fontfile, $tempstr);
                    $y = $y+$fontsize+$rowheight;
                    $tempstr = "";
                } else if($i+1 == mb_strlen($str, 'utf8') && $nextsize<$width-10){
//                $card->imageText($nextstr,$fontsize,$color,$x,$y,$width,1);
                    imagettftext($card, $fontsize/1.33, 0, $x, $y, $color, $fontfile, $tempstr);
                }
            }
            $y = $y+$fontsize+$rowheight;
            $row++;
        }

        return true;
    }


    /**
     *图片修圆角
     */
    function border_radius($imgpath)
    {
        $ext = pathinfo($imgpath);
        /* echo '<pre>';
         print_r($ext);
         exit;*/
        $src_img = null;
        if(isset($ext['extension'])){
            switch ($ext['extension']) {
                case 'jpg':
                    $src_img = imagecreatefromjpeg($imgpath);
                    break;
                case 'png':
                    $src_img = imagecreatefrompng($imgpath);
                    break;
            }
        }else{
            $src_img = imagecreatefromjpeg($imgpath);
        }

        $wh = getimagesize($imgpath);
        $w = $wh[0];
        $h = $wh[1];
        $w = min($w, $h);
        $h = $w;
        $img = imagecreatetruecolor($w, $h);
        //这一句一定要有
        imagealphablending( $img,false );

        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r = $w / 2; //圆半径
        $y_x = $r; //圆心X坐标
        $y_y = $r; //圆心Y坐标
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
            }
        }

        $avatar_path = "../public/uploads/" . date('Ymd') . "/avatar/";

        if (!is_dir($avatar_path)) {
            mkdir($avatar_path, 0777, true);
        }
        //    echo '<pre>';
        //    print_r($img);
        //    exit;
        //  file_put_contents($avatar_path.'avatar'.date('Ymd').".jpg",$img);
        $filename = $avatar_path.'avatar'.date('Ymd').".png";
        header("content-type:image/png");
        imagepng($img, $filename, 5); //保存到本地
        /*  imagepng($img);
          imagedestroy($img);
          exit;*/
        return $filename;
    }

}
?>