<?php

$filePath = "./droneLog/BD00F_20231121-111300_20231121-114500.zip";
if(file_exists($filePath)){
    $fileName = basename($filePath);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='  .$fileName );
    header('Content-Encoding: binary');
    header('Content-Length:' .filesize($filePath));
    readfile($filePath);
    exit;
}else {
    die('找不到相关文件');
}
