<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-01-21
 * 版    本：1.0.0
 * 功能说明：前台控制器演示。
 *
 **/
namespace Home\Controller;


class AdController extends ComController
{
    public function index()
    {
        $id = $_GET['adv_id'];
        $flash = M('adv')->where('adv_status=1 and adv_id=' . $id)->find();
        if(!$flash || $flash['adv_type']==1){
            $title = "趣玩";
            $desc = "请求出错了";
            $img = false;
        }else{
            $title = $flash['adv_title'];
            $desc = $flash['adv_content'];
            $img = $flash['adv_img'];
        }
        $this->assign("img",$img);
        $this->assign("title",$title);
        $this->assign("desc",$desc);
        $this->display();
    }
}