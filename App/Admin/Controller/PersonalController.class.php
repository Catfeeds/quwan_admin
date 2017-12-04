<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2015-09-18
 * 版    本：1.0.0
 * 功能说明：个人中心控制器。
 *
 **/

namespace Admin\Controller;

class PersonalController extends ComController
{

    public function profile()
    {
        $member = M('admin')->where('admin_id=' . $this->USER['admin_id'])->find();
        $this->assign('nav', array('Personal', 'profile', ''));//导航
        $this->assign('member', $member);
        $this->display();
    }

    public function update()
    {

        $uid = $this->USER['admin_id'];
        $password = isset($_POST['password']) ? trim($_POST['password']) : false;
        if ($password) {
            $data['password'] = password($password);
        }

        $head = I('post.head', '', 'strip_tags');
        if ($head <> '') {
            $data['head'] = $head;
        }

        $data['sex'] = isset($_POST['sex']) ? intval($_POST['sex']) : 0;
        $data['birthday'] = isset($_POST['birthday']) ? strtotime($_POST['birthday']) : 0;
//         $data['phone'] = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $data['qq'] = isset($_POST['qq']) ? trim($_POST['qq']) : '';
        $data['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';
        $isadmin = isset($_POST['isadmin']) ? $_POST['isadmin'] : '';
        if ($uid <> 1) {#禁止最高管理员设为普通会员。
            $data['isadmin'] = $isadmin == 'on' ? 1 : 0;
        }
        $Model = M('admin');
        $Model->data($data)->where("admin_id=$uid")->save();
        addlog('修改个人资料');
        $this->success('操作成功！');
    }
    
    public function profile_shop()
    {
        
        $shop_id = session("shop_id");
        if(!$shop_id || $this->Group_id!=2){
            redirect('profile');
        }
        
        $member = M('admin')->where('admin_id=' . $this->USER['admin_id'])->find();
        $this->assign('nav', array('Personal', 'profile', ''));//导航
        $this->assign('member', $member);
        
        $shop = M('shop')->where(array("shop_id"=>$shop_id))->find();
        if(!$shop){
            redirect("Admin/index/index");
        }elseif($shop['shop_status']==-1){
            redirect("Admin/ShopPass/index");
        }elseif($shop['shop_status']<-1){
            redirect("Admin/index/index");
        }
        
        $this->assign("shop",$shop);
        $this->display();
    }
    
    
    public function update_shop(){
        $shop_id = session("shop_id");
        if(!$shop_id || $this->Group_id!=2){
            $this->error("您没有权限",U("Admin/index/index"));
            redirect('profile');
        }
        
        $member = M('admin')->where('admin_id=' . $this->USER['admin_id'])->find();
        $this->assign('nav', array('Personal', 'profile', ''));//导航
        $this->assign('member', $member);
        
        $shop = M('shop')->where(array("shop_id"=>$shop_id))->find();
        if(!$shop){
            $this->error("您没有权限",U("Admin/index/index"));
            redirect("Admin/index/index");
        }elseif($shop['shop_status']==-1){
            $this->error("您没有权限",U("Admin/ShopPass/index"));
            redirect("Admin/ShopPass/index");
        }elseif($shop['shop_status']<-1){
            $this->error("您没有权限",U("Admin/index/index"));
            redirect("Admin/index/index");
        }
        
        
        $data = array();
        $data['shop_title'] = $_POST['shop_title'];
        $data['shop_mobile'] = $_POST['shop_mobile'];
        $data['shop_address'] = $_POST['shop_address'];
        $data['shop_lon'] = $_POST['shop_lon'];
        $data['shop_lat'] = $_POST['shop_lat'];
        
        if(!$data['shop_address'] || !$data['shop_lon'] || !$data['shop_lat']){
            $this->error("请使用定位功能，获取位置");
        }
        
        if(!$data['shop_title'] || mb_strlen($data['shop_title']>20)){
            $this->error("请填写店铺名称/店铺名称长度不能超过20个字符串");
        }
        if(!$data['shop_mobile']){
            $this->error("请填写店铺联系手机号码");
        }
        
        $res = M("shop")->where(array("shop_id"=>$shop_id))->save($data);
        if(!$res){
            $this->error("商家信息更细失败");
        }
        addlog('修改商家资料成功');
        $this->success("商家信息更新成功");
    }
}