<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：用户管理控制器。
 *
 **/

namespace Admin\Controller;


class MemberController extends ComController
{

    public function index($p = 1)
    {
        $where = '';
        $key = I('get.keyword');
        if($key){
            $id = intval($key);
            $where = "(user_id={$id} or user_nickname like '%{$key}%' or user_mobile like '%{$key}%')";
        }
        
        //注册时间
        $register_time = I('get.register_time');
        if($register_time){
            $rtime = strtotime($register_time);
            if($where){
                $where .= ' and user_created_at>='.$rtime;
            }else{
                $where = 'user_created_at>='.$rtime;
            }
        }
        
        //登录时间
        $register_time = I('get.login_time');
        if($register_time){
            $rtime = strtotime($register_time);
            if($where){
                $where .= ' and user_updated_at>='.$rtime;
            }else{
                $where = 'user_updated_at>='.$rtime;
            }
        }
        
        
        $p = intval($p) > 0 ? $p : 1;
        
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $count = M('user')->where($where)->count();
        $list = M('user')->where($where)->limit($offset . ',' . $pagesize)->select();
        
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    

    public function edit()
    {

        $id = isset($_GET['user_id']) ? intval($_GET['user_id']) : false;
        $currentcategory = M('user')->where('user_id=' . $id)->find();
        $this->assign("userInfo",$currentcategory);
        $this->display('form');
    }

    
    
    public function update($act = null)
    {
        $user_id = I('post.user_id',0,'intval');
        $user_nickname = I('post.user_nickname');
        $user_mobile = I('post.user_mobile');
        $user_avatar = I('post.user_avatar');
        if(!$user_id){
            $this->error("请求出错了");
        }
        
        if(!checkMobile($user_mobile)){
            $this->error("请填写正确的手机号码");
        }
        
        if(!$user_nickname){
            $this->error("请填写用户昵称");
        }
        //商户已经存在
        if (M('user')->where("user_mobile='{$user_mobile}' and user_id<>".$user_id)->count()) {
            $this->error('手机号已经存在用户了！');
        }
        
        $data = array();
        $data['user_nickname'] = $user_nickname;
        $data['user_mobile'] = $user_mobile;
        $data['user_avatar'] = $user_avatar;
        
        $res = M('user')->where(array('user_id'=>$user_id))->save($data);
        addlog('编辑商户UID：'.$user_id . $data);
        $this->success("编辑用户成功",U('index'));
    }
}
