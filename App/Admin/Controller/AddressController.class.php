<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-01-20
 * 版    本：1.0.0
 * 功能说明：后台用户控制器。
 *
 **/

namespace Admin\Controller;

class AddressController extends ComController
{
    public function index()
    {
        
        
        $this->display();
    }

    public function del()
    {

        $uids = isset($_REQUEST['uids']) ? $_REQUEST['uids'] : false;
        //uid为1的禁止删除
        if ($uids == 1 or !$uids) {
            $this->error('参数错误！');
        }
        if (is_array($uids)) {
            foreach ($uids as $k => $v) {
                if ($v == 1) {//uid为1的禁止删除
                    unset($uids[$k]);
                }
                $uids[$k] = intval($v);
            }
            if (!$uids) {
                $this->error('参数错误！');
                $uids = implode(',', $uids);
            }
        }

        $map['admin_id'] = array('in', $uids);
        if (M('admin')->where($map)->delete()) {
            M('auth_group_access')->where($map)->delete();
            addlog('删除会员UID：' . $uids);
            $this->success('恭喜，用户删除成功！');
        } else {
            $this->error('参数错误！');
        }
    }

    public function edit()
    {

        $uid = isset($_GET['uid']) ? intval($_GET['uid']) : false;
        if ($uid) {
            //$member = M('member')->where("uid='$uid'")->find();
            $prefix = C('DB_PREFIX');
            $user = M('admin');
            $member = $user->field("{$prefix}admin.*,{$prefix}auth_group_access.group_id")->join("{$prefix}auth_group_access ON {$prefix}admin.admin_id = {$prefix}auth_group_access.admin_id")->where("{$prefix}admin.admin_id=$uid")->find();

        } else {
            $this->error('参数错误！');
        }

        $usergroup = M('auth_group')->field('id,title')->select();
        $this->assign('usergroup', $usergroup);

        $this->assign('member', $member);
        $this->display('form');
    }

    public function update($ajax = '')
    {
        if ($ajax == 'yes') {
            $uid = I('get.uid', 0, 'intval');
            $gid = I('get.gid', 0, 'intval');
            M('auth_group_access')->data(array('group_id' => $gid))->where("admin_id='$uid'")->save();
            die('1');
        }

        $uid = isset($_POST['uid']) ? intval($_POST['uid']) : false;
//         $user = isset($_POST['user']) ? htmlspecialchars($_POST['user'], ENT_QUOTES) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        if(!checkMobile($phone)){
            $this->error("请填入正确的手机号码");
        }
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
        if (!$group_id) {
            $this->error('请选择用户组！');
        }
        $password = isset($_POST['password']) ? trim($_POST['password']) : false;
        if ($password) {
            $data['password'] = password($password);
        }
        $head = I('post.head', '', 'strip_tags');
        $data['sex'] = isset($_POST['sex']) ? intval($_POST['sex']) : 0;
        $data['head'] = $head ? $head : '';
        $data['birthday'] = isset($_POST['birthday']) ? strtotime($_POST['birthday']) : 0;
        $data['qq'] = isset($_POST['qq']) ? trim($_POST['qq']) : '';
        $data['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';


        $data['t'] = time();
        if (!$uid) {
//             if ($user == '') {
//                 $this->error('用户名称不能为空！');
//             }
            if (!$password) {
                $this->error('用户密码不能为空！');
            }
            if (M('admin')->where("phone='{$phone}'")->count()) {
                $this->error('手机号已被占用！');
            }
            $data['user'] = 'mobile_'.$phone;
            $data['phone'] = $phone;
            $uid = M('admin')->data($data)->add();
            M('auth_group_access')->data(array('group_id' => $group_id, 'admin_id' => $uid))->add();
            addlog('新增会员，会员UID：' . $uid);
        } else {
            M('auth_group_access')->data(array('group_id' => $group_id))->where("admin_id=$uid")->save();
            addlog('编辑会员信息，会员UID：' . $uid);
            M('admin')->data($data)->where("admin_id=$uid")->save();

        }
        $this->success('操作成功！');
    }


    public function add()
    {

        $usergroup = M('auth_group')->field('id,title')->select();
        $this->assign('usergroup', $usergroup);
        $this->display('form');
    }
}