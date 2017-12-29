<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-01-21
 * 版    本：1.0.0
 * 功能说明：焦点图。
 *
 **/

namespace Admin\Controller;

class MessageController extends ComController
{

    //flash焦点图
    public function index($p = 1)
    {

        $p = intval($p) > 0 ? $p : 1;
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        
        
        $count = M('message')->where("message_status=1")->count();
        
        $list = M('message')->where("message_status=1")->limit($offset . ',' . $pagesize)->order('message_created_at desc')->select();
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    //新增焦点图
    public function add()
    {

        $this->display('form');
    }

    //删除焦点图
    public function del()
    {

        $ids = isset($_REQUEST['ids']) ? $_REQUEST['ids'] : false;
        if ($ids) {
            if (is_array($ids)) {
                $ids = implode(',', $ids);
                $map['message_id'] = array('in', $ids);
            } else {
                $map = 'message_id=' . $ids;
            }
            if (M('message')->where($map)->save(array("message_status=0"))) {
                addlog('删除信息，ID：' . $ids);
                $this->success('恭喜，删除成功！');
            } else {
                $this->error('参数错误！');
            }
        } else {
            $this->error('参数错误！');
        }
    }

    //保存焦点图
    public function update($id = 0)
    {
        $id = intval($id);
        $data['message_title'] = I('post.message_title', '', 'strip_tags');
        if (!$data['message_title']) {
            $this->error('请标题！');
        }
        
        $data['message_comment'] = I('post.message_content', '', 'strip_tags');
        if(!$data['message_comment']){
            $this->error('请填写内页内容！');
        }
        $data['message_comment'] = htmlspecialchars($data['message_comment']);
        $data['created_user_id'] = session("admin_id");
        $data['message_status'] = 1;
        $data['message_type'] = 1;
        $data['message_read'] = 0;
        $data['message_created_at'] = time();
        $data['user_id'] = 0;
        M('message')->data($data)->add();
        addlog('新增系统消息');

        $this->success('恭喜，操作成功！', U('index'));
    }
}