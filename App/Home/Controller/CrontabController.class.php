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

class CrontabController extends ComController
{
    public function index()
    {
        $list = M('shop')->where("shop_status>=1 and shop_crontab_time='".date("Y-m-d")."'")->limit('50')->select();
        if($list){
            foreach($list as $v){
                $data = array();
                $data['shop_lastmonth_money'] = $v['shop_lastmonth_money']+$v['shop_money'];
                $data['shop_money'] = 0;
                $data['shop_ver'] = $data['shop_ver']+1;
                $data['shop_crontab_time'] = date("Y-m-d");
                $info = M('shop')->where(array("shop_id"=>$v['shop_id'],'shop_ver'=>$v['shop_ver']))->save($data);
            }
        }
        echo "1";
    }
}