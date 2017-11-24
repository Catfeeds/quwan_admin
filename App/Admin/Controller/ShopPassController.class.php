<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：商家控制器。
 *
 **/

namespace Admin\Controller;



use Think\Model;

class ShopPassController extends ComController
{

    public function index($p = 1)
    {
        $this->display();
    }
    
    public function update($act = null)
    {
        
        
        
        
        $mobile = I('post.mobile');
        $captche = I('post.captche');
        $new_pwd = I('post.new_pwd');
        $new_pwd_2 = I('post.new_pwd_2');
        
        if($new_pwd!=$new_pwd_2){
            $this->error("两次密码不相同");
        }
        
        if(strlen($new_pwd)<6 || strlen($new_pwd)>16){
            $this->error("密码需要是6到15位的英文/数字字符串");
        }
        $dataAdmin = array();
        $dataAdmin['password'] = password($new_pwd);
        
        $admin_id = session("admin_id");
        $info = M('admin')->where(array("admin_id"=>$admin_id))->find();
        if($info && $info['status']){
            
            
            if($info['password']==password($new_pwd)){
                $this->error("您未修改您的密码");
            }
            
            if($mobile != $info['phone']){
                $this->error("请输入正确的手机号码");
            }
            
            $where = "admin_id=".$admin_id." and mobile_status=1 and mobile_code=".$captche;
            $count = M('admin_mobile')->where($where)->find();
            
            
            if(!$count){
                $this->error("请输入正确的验证码");
            }
            $model = new Model();
            $model->startTrans();
            $res = M('admin_mobile')->where(array("mobile_id"=>$count['mobile_id']))->save(array("mobile_status"=>0));
            if($res){
                
                $dataAdmin = array();
                $dataAdmin['password'] = password($new_pwd);
                $resAdmin = M('admin')->where(array("admin_id"=>$admin_id))->save($dataAdmin);
                if($resAdmin){
                    
                    $shopInfo = M('admin_shop')->where("admin_id=".$admin_id)->find();
                    if($shopInfo){
                        $shop_id=$shopInfo['shop_id'];
                        $shop = M('shop')->where("shop_id=".$shopInfo['shop_id'])->find();
                        if($shop && $shop['shop_status']==-1){
                            $upShop = array();
                            $upShop['shop_status'] = 0;
                            M('shop')->where("shop_id=".$shopInfo['shop_id'])->save($upShop);
                            session('shop_status',0);
                        }
                    }
                    addlog('商家修改密码，ID：' . $shopInfo['shop_id']);
                    $model->commit();
                    $this->success("请求成功");
                }
            }
            $model->rollback();
        }
        $this->error("请求出错了");
    }
}
