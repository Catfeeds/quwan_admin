<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2015-09-17
 * 版    本：1.0.0
 * 功能说明：后台公用控制器。
 *
 **/

namespace Admin\Controller;

use Common\Controller\BaseController;
use Think\Auth;

class ComController extends BaseController
{
    public $USER;
    
    public $Group_id;

    public function _initialize()
    {

        C(setting());
        if (!C("COOKIE_SALT")) {
            $this->error('请配置COOKIE_SALT信息');
        }
        /**
         * 不需要登录控制器
         */
        if (in_array(CONTROLLER_NAME, array("Login"))) {
            return true;
        }
        //检测是否登录
        $flag =  $this->check_login();
        $url = U("login/index");
        if (!$flag) {
            header("Location: {$url}");
            exit(0);
        }
        $m = M();
        $prefix = C('DB_PREFIX');
        $UID = $this->USER['admin_id'];
        $userinfo = $m->query("SELECT * FROM {$prefix}auth_group g left join {$prefix}auth_group_access a on g.id=a.group_id where a.admin_id=$UID");
        $Auth = new Auth();
        $allow_controller_name = array('Upload','Address','Mobile','Index','Cache');//放行控制器名称
        $allow_action_name = array();//放行函数名称
        
        $this->Group_id = $userinfo[0]['group_id'];
        print_R($userinfo);
        die;
        if ($userinfo[0]['group_id'] != 1 && !$Auth->check(CONTROLLER_NAME . '/' . ACTION_NAME,
                $UID) && !in_array(CONTROLLER_NAME, $allow_controller_name) && !in_array(ACTION_NAME,
                $allow_action_name)
        ) {
            $this->error('没有权限访问本页面!');
        }
        $shop_id = session('shop_id');
        $shop_status = session('shop_status');
//         echo $shop_status;
//         die;
        $controller_shop = array("Address","ShopPass","Mobile","Upload","Login");
        if($shop_id>0 && $shop_status==-1 && (CONTROLLER_NAME!='ShopPass' && CONTROLLER_NAME!='Mobile')){
            $this->error('请先重置您的密码!',U('ShopPass/index'));
        }
        $controller_shop[] = "Personal";
        if($shop_id>0 && $shop_status==0 && !in_array(CONTROLLER_NAME, $controller_shop)){
            $this->error('请先完善资料!',U('Personal/profile_shop'));
        }
        
        $user = member(intval($UID));
        $this->assign('user', $user);


        $current_action_name = ACTION_NAME == 'edit' ? "index" : ACTION_NAME;
        $current = $m->query("SELECT s.id,s.title,s.name,s.tips,s.pid,p.pid as ppid,p.title as ptitle FROM {$prefix}auth_rule s left join {$prefix}auth_rule p on p.id=s.pid where s.name='" . CONTROLLER_NAME . '/' . $current_action_name . "'");
        $this->assign('current', $current[0]);


        $menu_access_id = $userinfo[0]['rules'];

        if ($userinfo[0]['group_id'] != 1) {

            $menu_where = "AND id in ($menu_access_id)";

        } else {

            $menu_where = '';
        }
        $menu = M('auth_rule')->field('id,title,pid,name,icon')->where("islink=1 $menu_where ")->order('o ASC')->select();
        $menu = $this->getMenu($menu);
        $this->assign('menu', $menu);

    }


    protected function getMenu($items, $id = 'id', $pid = 'pid', $son = 'children')
    {
        $tree = array();
        $tmpMap = array();

        foreach ($items as $item) {
            $tmpMap[$item[$id]] = $item;
        }

        foreach ($items as $item) {
            if (isset($tmpMap[$item[$pid]])) {
                $tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]];
            } else {
                $tree[] = &$tmpMap[$item[$id]];
            }
        }
        return $tree;
    }

    public function check_login(){
        session_start();
        $flag = false;
        $salt = C("COOKIE_SALT");
        $ip = get_client_ip();
        $ua = $_SERVER['HTTP_USER_AGENT'];
        $auth = cookie('auth');
        $uid = session('admin_id');
        if ($uid) {
            $user = M('admin')->where(array('admin_id' => $uid))->find();

            if ($user) {
                if ($auth ==  password($uid.$user['user'].$ip.$ua.$salt)) {
                    $flag = true;
                    $this->USER = $user;
                }
            }
        }
        return $flag;
    }
}