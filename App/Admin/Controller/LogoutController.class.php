<?php
/**
 *
 * ��Ȩ���У�ǡά����<www.bobolucy.com>
 * ��    �ߣ�����<273719650@qq.com>
 * ��    �ڣ�2016-01-17
 * ��    ����1.0.0
 * ����˵������̨�ǳ���������
 *
 **/

namespace Admin\Controller;

class LogoutController extends ComController
{
    public function index()
    {
        cookie('auth', null);
        session('admin_id',null);
        $url = U("login/index");
        header("Location: {$url}");
        exit(0);
    }
}