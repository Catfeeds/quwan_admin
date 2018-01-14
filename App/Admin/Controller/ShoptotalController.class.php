<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：商家财务统计控制器。
 *
 **/

namespace Admin\Controller;


class ShoptotalController extends ComController
{
    public function index(){
        $day_status = intval($_GET['day_status']);
        
        $shop_id = session("shop_id");
        $shop_id = intval($shop_id);
        $wherePay = "";
        $whereCheck = "";
        if($day_status==0){//昨日
            $date = date("Y-m-d",strtotime("-1 day"));
            $start_time = strtotime($date);
            $end_time = $start_time+86400;
        }elseif($day_status==1){//本月
            $date = date("Y-m-01");
            $start_time = strtotime($date);
            $date = date("Y-m-d");
            $end_time = strtotime($date)+86400;
        }elseif($day_status==2){//上月
            $date = date("Y-m",strtotime("-1 month"));
            $start_time = strtotime($date);
            $date = date("Y-m-01");
            $end_time = strtotime($date);
        }else{//选取时间段
            $day_status = 4;
            $start_day = $_GET['start_day'];
            $end_day = $_GET['end_day'];
            if(!$start_day || !$end_day){
                redirect(U('index',array("day_status"=>0)));
            }
            
            $start_time = strtotime($start_day);
            $end_time = strtotime($end_day)+86400;
        }
        
        $order_num = M('order')->where("shop_id={$shop_id} and order_pay_at>=".$start_time." and order_pay_at<".$end_time)->count();
        
        $order_amount = M('order')->where("shop_id={$shop_id} and order_check_at>=".$start_time." and order_check_at<".$end_time)->sum("order_amount");
        //echo M('order')->getLastSql();
        $this->assign("order_num",$order_num);
        $this->assign("order_amount",$order_amount);
        $this->display();
    }
}
