<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-01-21
 * 版    本：1.0.0
 * 功能说明：前台公用控制器。
 *
 **/

namespace Home\Controller;

use Think\Controller;

class ComController extends Controller
{

    public function _initialize()
    {
        C(setting());
        /*
        $links = M('links')->limit(10)->order('o ASC')->select();
        $this->assign('links',$links);
        */
    }
    
    public function set_page_view($page_name='',$page_value=''){
    	log_page_view($page_name,$page_value);
    }
    
}