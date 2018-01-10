<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：财务统计控制器。
 *
 **/

namespace Admin\Controller;

use Think\Model;

class OrderstatController extends ComController
{
    
    public function index()
    {
        
        $day_status = intval($_GET['day_status']);
        
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
        
        $order_num = M('order')->where("order_pay_at>=".$start_time." and order_pay_at<".$end_time)->count();
        $order_create_amount = M('order')->where("order_pay_at>=".$start_time." and order_pay_at<".$end_time)->sum("order_amount");
        if(!$order_create_amount){
            $order_create_amount = 0;
        }
        $order_amount = M('order')->where("order_check_at>=".$start_time." and order_check_at<".$end_time)->sum("order_amount");
//         echo M('order')->getLastSql();
//         die;
        if(!$order_amount){
            $order_amount = 0;
        }
        
        $this->assign("order_num",$order_num);
        $this->assign("order_amount",$order_amount);
        $this->assign("order_create_amount",$order_create_amount);
        $this->display();
    }
    
    
    public function tongji(){
        $day_status = intval($_GET['day_status']);
        
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
            $end_time = strtotime($date)+84400;
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
        //订单数
        $order_num = M('order')->where("order_pay_at>=".$start_time." and order_pay_at<".$end_time)->count();
        //支付金额
        $order_create_amount = M('order')->where("order_pay_at>=".$start_time." and order_pay_at<".$end_time)->sum("order_amount");
        if(!$order_create_amount){
            $order_create_amount = 0;
        }
        //核算金额
        $order_amount = M('order')->where("order_check_at>=".$start_time." and order_check_at<".$end_time)->sum("order_amount");
        
        if(!$order_amount){
            $order_amount = 0;
        }
        
        //新增用户
        $user_total = M("user")->where("user_created_at>=".$start_time." and user_created_at<".$end_time)->count();
        
        //活跃用户
        $user_login = M("log")->where("log_type = 1 and log_time>=".$start_time." and log_time<".$end_time)->count("distinct user_id");
        
        //首页pv
        $index_pv = M("log")->where("log_type = 3 and log_time>=".$start_time." and log_time<".$end_time)->count();
        
        //分享次数
        $share_num = M("log")->where("log_type = 2 and log_time>=".$start_time." and log_time<".$end_time)->count();
        
        //收藏次数
        $fav_num = M("fav")->where("fav_created_at>=".$start_time." and fav_created_at<".$end_time)->count();
        //select count(DISTINCT(user_id)) from qw_order where order_status>=30;
        
        //计算复购率
        $per_m = M("order")->where("order_status>=30")->count("distinct user_id");
        
        $sql = "select count(1) fz from (select user_id from qw_order where order_status>=30 GROUP BY user_id HAVING count(1)>1) a";
        
        $model = new Model();
        $res = $model->query($sql);
        $tongji_per = 0;
        if($per_m>0){
            $tongji_per = round($res[0]['fz']*100/$per_m,2);
        }
        
        
        $this->assign("order_num",$order_num);
        $this->assign("order_amount",$order_amount);
        $this->assign("order_create_amount",$order_create_amount);
        
        $this->assign("user_new_total",$user_total);//新增用户
        $this->assign("user_login",$user_login);//活跃用户数
        $this->assign("index_pv",$index_pv);//首页pv数
        $this->assign("share_total",$share_num);//分享次数
        $this->assign("fav_total",$fav_num);//收藏次数
        $this->assign("tongji_per",$tongji_per);//复购率
        $this->display();
    }
}
