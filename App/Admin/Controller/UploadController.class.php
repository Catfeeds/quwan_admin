<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：小马哥<273719650@qq.com>
 * 日    期：2015-09-17
 * 版    本：1.0.3
 * 功能说明：文件上传控制器。
 *
 **/

namespace Admin\Controller;

class UploadController extends ComController
{
    public function index($type = null)
    {

    }

    public function uploadpic()
    {
        $Img = I('Img');
        $Path = null;
        $imgUrl = '';
        $setting = C("UPLOAD_SITEIMG_QINIU");
        if($Img){
            
            $imgUrl = getQiniuImgUrl($Img);//"http://".$setting['driverConfig']['domain'].'/'.$Img;
        }
        if ($_FILES['img']) {
            $ImgInfo = $this->saveimg($_FILES);
            if($ImgInfo){
                $Img = $ImgInfo['img']['name'];
                $imgUrl = $ImgInfo['img']['url'];
            }
            
        }
        $BackCall = I('BackCall');
        $Width = I('Width');
        $Height = I('Height');
        if (!$BackCall) {
            $Width = $_POST['BackCall'];
        }
        if (!$Width) {
            $Width = $_POST['Width'];
        }
        if (!$Height) {
            $Width = $_POST['Height'];
        }
        $this->assign('Width', $Width);
        $this->assign('BackCall', $BackCall);
        $this->assign('Img', $Img);
        $this->assign("imgUrl",$imgUrl);
        $this->assign('Height', $Height);
        $this->display('Uploadpic');
    }

    private function saveimg($file)
    {
//         echo "adfasd";
        
        $setting=C('UPLOAD_SITEIMG_QINIU');
//         print_R($setting);
        $Upload = new \Think\Upload($setting);
        $info = $Upload->upload($file);
        return $info;
//         print_R($info);
//         print_R($Upload->error);
// //         die;
        
//         $uptypes = array(
//             'image/jpeg',
//             'image/jpg',
//             'image/jpeg',
//             'image/png',
//             'image/pjpeg',
//             'image/gif',
//             'image/bmp',
//             'image/x-png'
//         );
//         $max_file_size = 2000000;     //上传文件大小限制, 单位BYTE
//         $destination_folder = 'Public/attached/' . date('Ym') . '/'; //上传文件路径
//         if ($max_file_size < $file["size"]) {
//             echo "文件太大!";
//             return null;
//         }
//         if (!in_array($file["type"], $uptypes)) {
//             $name = $file["name"];
//             $type = $file["type"];
//             echo "<script>alert('{$name}文件类型不符!{$type}')</script>";
//             return null;
//         }
//         if (!file_exists($destination_folder)) {
//             mkdir($destination_folder);
//         }
//         $filename = $file["tmp_name"];
//         $image_size = getimagesize($filename);
//         $pinfo = pathinfo($file["name"]);
//         $ftype = $pinfo['extension'];
//         //@todo 有可能生成同一个文件名，需要重新优化
//         $imgname = date("YmdHis") . rand(0000, 9999) . "." . $ftype;
//         $destination = $destination_folder . $imgname;
//         if (file_exists($destination)) {
//             echo "同名文件已经存在了";
//             return null;
//         }
//         if (!move_uploaded_file($filename, $destination)) {
//             return null;
//         }
//         return "/" . $destination;
    }

    public function batchpic()
    {
        $ImgStr = I('Img');
        $ImgStr = trim($ImgStr, '|');
        $Img = array();
        if (strlen($ImgStr) > 1) {
            $Img = explode('|', $ImgStr);
        }
//         PRINT_R($Img);
        $Path = null;
        
        if ($_FILES['uploadimg']) {
            $info = $this->saveimg($_FILES);
//             print_R($Img);
//             print_R($info);
            foreach($info as $val){
//                 array_push($Img, $val['name']);
                $Img[] = $val['name'];
            }
        }
        
        $ImgStr = implode("|", $Img);
        $BackCall = I('BackCall');
        $Width = I('u');
        $Height = I('Height');
        if (!$BackCall) {
            $Width = $_POST['BackCall'];
        }
        if (!$Width) {
            $Width = $_POST['Width'];
        }
        if (!$Height) {
            $Width = $_POST['Height'];
        }
        $this->assign('Width', $Width);
        $this->assign('BackCall', $BackCall);
        $this->assign('ImgStr', $ImgStr);
//         $setting = C("UPLOAD_SITEIMG_QINIU");
//         foreach($Img as &$val){
//             $val = "http://".$setting['driverConfig']['domain'].'/'.$val;
//         }
        $this->assign('Img', $Img);
        $this->assign('Height', $Height);
        $this->display('Batchpic');
    }
}
