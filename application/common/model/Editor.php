<?php
namespace app\common\model;

/**
 * 编辑器类逻辑
 * Class EditorLogic
 * @package app\common\logic
 */
class Editor
{

    /**
     * 保存上传的图片
     * @param $file
     * @param $save_path
     * @return array
     */
    public function saveUploadImage($file, $save_path)
    {
        $return_url = '';
        $state = "SUCCESS";
        $new_path = $save_path.date('Ymd').'/';
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->rule(function ($file) {
            return  md5(mt_rand()); // 使用自定义的文件保存规则
        })->move(UPLOAD_PATH.$new_path);
        if (!$info) {
            $state = "ERROR" . $file->getError();
        } else {
            $return_url = (UPLOAD_TYPE==2?BASE_URL:'').ltrim(UPLOAD_PATH,'.').$new_path.$info->getSaveName();
        }
        return [
            'state' => $state,
            'url'   => $return_url
        ];
    }

}