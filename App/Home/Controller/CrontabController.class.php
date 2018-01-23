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

use Think\Model;

class CrontabController extends ComController
{
    
    //统计商家的结算金额信息的信息
    public function index()
    {
        $list = M('shop')->where("shop_status>=1 and (shop_crontab_time!='".date("Y-m-d")."' or shop_crontab_time is null)")->limit('50')->select();
        if($list){
            foreach($list as $v){
                $data = array();
                $data['shop_lastmonth_money'] = $v['shop_lastmonth_money']+$v['shop_money'];
                $data['shop_money'] = 0;
                $data['shop_ver'] = $data['shop_ver']+1;
                $data['shop_crontab_time'] = date("Y-m-d");
                $info = M('shop')->where(array("shop_id"=>$v['shop_id'],'shop_ver'=>$v['shop_ver']))->save($data);
                print_R($data);
                print_R($info);
            }
        }
        echo "1";
    }
    
    /**
     * 节日报名短信推送信息
     */
    public function holiday_sms(){
        $day = date("Y-m-d",strtotime("-2 day"));
        $sql = "select * from qw_order o left join qw_holiday h on o.join_id=h.holiday_id
        left join qw_user u on o.user_id=u.user_id
        where h.holiday_status=1 and FROM_UNIXTIME(h.holiday_start_at, '%Y-%m-%d')='{$day}'
        and o.order_type=4 and o.order_status>=20";
        
        $model = new Model();
        $list = $model->query($sql);
        if($list){
            $Qcloudsms = new \Org\Util\Qcloudsms(C("QcloudsmsApi"), C("QcloudsmsAppkey"));
            $msg_config = C('SENDmsg_tpl_id');
            foreach($list as $info){
                $key = $info['user_id'] . '_' . $info['holiday_id'] . '_' . date("Y_m_d");
                $cache = S($key);
                if(!$cache){
                    $params = array();
                    $params[] = $info['holiday_name'];
                    $res = $Qcloudsms->sendWithParam("86", $info['user_mobile'], $msg_config['holiday_id'],$params);
                    wirteFileLog($info['order_id'].'|'.$info['user_id'].'|'.$res,'holiday_send');
                    $res = json_decode($res,true);
                    //print_R($res);
                    if($res['result']!=0){
                        continue;
                    }
                    S($key,1,86400);
                }
            }
        }
    }
}