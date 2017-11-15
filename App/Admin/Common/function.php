<?php
/**
 * 增加日志
 * @param $log
 * @param bool $name
 */

function addlog($log, $name = false)
{
    $Model = M('admin_log');
    if (!$name) {
        session_start();
        $uid = session('admin_id');
        if ($uid) {
            $user = M('admin')->field('user')->where(array('admin_id' => $uid))->find();
            $data['name'] = $user['user'];
        } else {
            $data['name'] = '';
        }
        $data['admin_id'] = $uid;
    } else {
        $data['name'] = $name;
    }
    $data['t'] = time();
    $data['ip'] = $_SERVER["REMOTE_ADDR"];
    $data['log'] = $log;
    $Model->data($data)->add();
}


/**
 *
 * 获取用户信息
 *
 **/
function member($uid, $field = false)
{
    $model = M('admin');
    if ($field) {
        return $model->field($field)->where(array('admin_id' => $uid))->find();
    } else {
        return $model->where(array('admin_id' => $uid))->find();
    }
}