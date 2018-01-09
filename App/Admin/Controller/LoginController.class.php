<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-01-17
 * 版    本：1.0.0
 * 功能说明：后台登录控制器。
 *
 **/

namespace Admin\Controller;

use Admin\Controller\ComController;

class LoginController extends ComController
{
    public function index()
    {
        $flag = $this->check_login();
        if ($flag) {
            $this->error('您已经登录,正在跳转到主页', U("index/index"));
        }

        $this->display();
    }

    public function login()
    {
        $verify = isset($_POST['verify']) ? trim($_POST['verify']) : '';
        if (!$this->check_verify($verify, 'login')) {
            $this->error('验证码错误！', U("login/index"));
        }

        $username = isset($_POST['user']) ? trim($_POST['user']) : '';
        $password = isset($_POST['password']) ? password(trim($_POST['password'])) : '';
        $remember = isset($_POST['remember']) ? $_POST['remember'] : 0;
        if ($username == '') {
            $this->error('用户名不能为空！', U("login/index"));
        } elseif ($password == '') {
            $this->error('密码必须！', U("login/index"));
        }

        if(checkMobile($username)){
            $where = array('phone' => $username, 'password' => $password);
        }else{
            $where = array('user' => $username, 'password' => $password);
        }
        
        $model = M("admin");
        $user = $model->field('admin_id,user')->where($where)->find();

        if ($user) {
            
            
            
            
            $salt = C("COOKIE_SALT");
            $ip = get_client_ip();
            $ua = $_SERVER['HTTP_USER_AGENT'];
            session_start();
            session('admin_id',$user['admin_id']);
            //加密cookie信息
            $auth = password($user['admin_id'].$user['user'].$ip.$ua.$salt);
            if ($remember) {
                cookie('auth', $auth, 3600 * 24 * 365);//记住我
            } else {
                cookie('auth', $auth);
            }
            addlog('登录成功。');
            
            $shop_id = 0;
            $shop_status=-1;
            
            $url = U("index/index");
            $shopInfo = M('admin_shop')->where("admin_id=".$user['admin_id'])->find();
            if($shopInfo){
                $shop_id=$shopInfo['shop_id'];
                $shop = M('shop')->where("shop_id=".$shopInfo['shop_id'])->find();
                $shop_status = $shop['shop_status'];
            }
            session('shop_id',$shop_id);
            session('shop_status',$shop_status);
            
            header("Location: $url");
            exit(0);
        } else {
            addlog('登录失败。', $username);
            $this->error('账号或密码输入错误。！', U("login/index"));
        }
    }

    function check_verify($code, $id = '')
    {
        $verify = new \Think\Verify();
        return $verify->check($code, $id);
    }

    public function verify()
    {
        $config = array(
            'fontSize' => 14, // 验证码字体大小
            'length' => 4, // 验证码位数
            'useNoise' => false, // 关闭验证码杂点
            'imageW' => 100,
            'imageH' => 30,
        );
        $verify = new \Think\Verify($config);
        $verify->entry('login');
    }
}
