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
        $shop_id = I('post.shop_id',0,'intval');
        $shop_name = I('post.shop_name');
        $shop_mobile = I('post.shop_mobile');
        $shop_desc = I('post.shop_desc');
        $shop_title = I('post.shop_title');
        if(!$shop_id){
            $this->error("请求出错了");
        }
        
        if(!checkMobile($shop_mobile)){
            $this->error("请填写正确的手机号码");
        }
        
        if(!$shop_name || mb_strlen($shop_name)>10){
            $this->error("请填写正确的商家号码");
        }
        //商户已经存在
        if (M('shop')->where("shop_mobile='{$shop_mobile}' and shop_id<>".$shop_id)->count()) {
            $this->error('手机号已经存在商户了！');
        }
        
        $data = array();
        $data['shop_name'] = $shop_name;
        $data['shop_mobile'] = $shop_mobile;
        $data['shop_desc'] = $shop_desc;
        $data['shop_title'] = $shop_title;
        
        $res = M('shop')->where(array('shop_id'=>$shop_id))->save($data);
        addlog('编辑商户UID：' . $data);
        $this->success("编辑商户成功",U('index'));
    }
}
