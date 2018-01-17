<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：手机短信控制器。
 *
 **/

namespace Admin\Controller;



class MobileController extends ComController
{

    public function getCode(){
        
        $mobile = I("post.mobile");
        
        $admin_id = session("admin_id");
        $info = M('admin')->where(array("admin_id"=>$admin_id))->find();
        if($info && $info['status']){
            $time = time()-600;
            $where = "admin_id=".$admin_id." and add_time>=".$time;
            $count = M('admin_mobile')->where($where)->count();
            if($count>5){
                $this->error("10分钟内请求过多");
            }
            if($mobile != $info['phone']){
                $this->error("请输入正确的手机号码");
            }
            
            $code = rand(1000,9999);
            
            
            $Qcloudsms = new \Org\Util\Qcloudsms(C("QcloudsmsApi"), C("QcloudsmsAppkey"));
            
            $msg_config = C('SENDmsg_tpl_id');
            
            $params = array();
            $params[] = $code;
            $res = $Qcloudsms->sendWithParam("86", $mobile, $msg_config['login_id'],$params);
            $res = json_decode($res,true);
//             print_R($res);
            if($res['result']!=0){
                $this->error("短信发送失败");
            }
            
            $data = array();
            $data['admin_id'] = $admin_id;
            $data['mobile'] = $mobile;
            $data['add_time'] = time();
            $data['mobile_code'] = $code;
            
            $res = M('admin_mobile')->add($data);
            if($res){
                $this->success("请求成功");
            }
        }
        $this->error("请求出错了");
    }
}
