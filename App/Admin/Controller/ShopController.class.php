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

class ShopController extends ComController
{

    public function index($p = 1)
    {
        $where = '';
        $key = I('get.keyword');
        if($key){
            $where = "(shop_title like '%{$key}%' or shop_name like '%{$key}%' or shop_mobile like '%{$key}%')";
        }
        
        $type = I('get.type','0','intval');
        //已经结清
        $where1='shop_status>=-1';
        if($type==1){
            $where1 = "shop_status=1 and shop_lastmonth_money=0";
        }elseif($type==2){
            $where1 = "shop_status=1 and shop_lastmonth_money!=0";
        }
        
        if(!$where){
            $where = $where1;
        }elseif($where && $where1){
            $where = $where.' and '.$where1;
        }
        $p = intval($p) > 0 ? $p : 1;
        
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $count = M('shop')->where($where)->count();
        $list = M('shop')->where($where)->limit($offset . ',' . $pagesize)->select();
        
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function del()
    {
        $id = isset($_GET['shop_id']) ? intval($_GET['shop_id']) : false;
        if ($id) {
            $data['shop_id'] = $id;
            $category = M('shop');
            $shopInfo = $category->where(array("shop_id"=>$id))->find();
            if(!$shopInfo){
                die('1');
            }
            //如果存在的商户，还没登录，就直接删除那个商户
            if($shopInfo['shop_status']==-1){
                $category->where('shop_id=' . $id)->delete();
                $info = M("admin_shop")->where('shop_id=' . $id)->find();
                if($info){
                    M('admin')->where('admin_id=' . $info['admin_id'])->save(array("shop_status"=>0));
                }
                M("admin_shop")->where('shop_id=' . $id)->delete();
            }
            
            $category->where('shop_id=' . $id)->save(array("shop_status"=>-2));
            
            $info = M("admin_shop")->where('shop_id=' . $id)->find();
            if($info){
                M('admin')->where('admin_id=' . $info['admin_id'])->save(array("status"=>0));
            }
            addlog('删除商家，ID：' . $id);
            die('1');
        } else {
            die('0');
        }

    }

    public function edit()
    {

        $id = isset($_GET['shop_id']) ? intval($_GET['shop_id']) : false;
        $currentcategory = M('shop')->where('shop_id=' . $id)->find();
        $this->assign("shopInfo",$currentcategory);
        $this->display('form');
    }

    public function add()
    {
        $this->display('form_add');
    }

    /**
     * 添加商家
     */
    public function update_add(){
        $shop_name = I('post.shop_name');
        $shop_mobile = I('post.shop_mobile');
        
        if(!checkMobile($shop_mobile)){
            $this->error("请填写正确的手机号码");
        }
        
        if(!$shop_name || mb_strlen($shop_name)>10){
            $this->error("请填写正确的商家号码");
        }
        //商户已经存在
        if (M('shop')->where("shop_mobile='{$shop_mobile}'")->count()) {
            $this->error('手机号已经存在商户了！');
        }
        
        $add_admin = array();
        $add_shop = array();
        $add_shop['shop_mobile'] = $shop_mobile;
        $add_shop['shop_name'] = $shop_name;
        
        $model = new Model();
        
        $model->startTrans();
        
        $res_shop = M('shop')->add($add_shop);
        if(!$res_shop){
            $model->rollback();
            $this->error("添加商户失败");
        }
        
        $add_admin['password'] = password('qw123456');
        $add_admin['sex'] = 0;
        $add_admin['head'] = '';
        $add_admin['birthday'] = 0;
        $add_admin['qq'] = '';
        $add_admin['email'] = '';
        $add_admin['t'] = time();
        $add_admin['user'] = 'mobile_'.$shop_mobile;
        $add_admin['phone'] = $shop_mobile;
        $res_admin = M('admin')->add($add_admin);
        if(!$res_admin){
            $model->rollback();
            $this->error("添加商户用户失败");
        }
        
        M('auth_group_access')->data(array('group_id' => 2, 'admin_id' => $res_admin))->add();
        M('admin_shop')->data(array('shop_id' => $res_shop, 'admin_id' => $res_admin))->add();
        addlog('新增商户UID：' . json_decode($res_shop));
        $model->commit();
        $this->success("新增商户成功");
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
        addlog('编辑商户UID：'.$shop_id . json_encode($data));
        $this->success("编辑商户成功",U('index'));
    }
}
