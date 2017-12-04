<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-02-16
 * 版    本：1.0.0
 * 功能说明：用户反馈。
 *
 **/

namespace Admin\Controller;

class FacebookController extends ComController
{

    //新增
    public function index($p = 1)
    {

        $p = intval($p) > 0 ? $p : 1;
        
        $article = M('suggest s');
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $orderby = "s.suggest_created_at desc";
        $prefix = C('DB_PREFIX');
        $count = $article->count();
        $list = $article->field("s.*,u.user_nickname")->join("left join {$prefix}user u on s.user_id=u.user_id")->order($orderby)->limit($offset . ',' . $pagesize)->select();
        
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }
}