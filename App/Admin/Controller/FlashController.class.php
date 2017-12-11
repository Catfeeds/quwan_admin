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

class FlashController extends ComController
{

    //flash焦点图
    public function index()
    {

        $list = M('adv')->where("adv_status=1")->order('adv_weight asc')->select();
        $this->assign('list', $list);
        $this->display();
    }

    //新增焦点图
    public function add()
    {

        $this->display('form');
    }

    //修改焦点图
    public function edit($id = null)
    {

        $id = intval($id);
        $flash = M('adv')->where('adv_id=' . $id)->find();
        $this->assign('flash', $flash);
        $this->display('form');
    }

    //删除焦点图
    public function del()
    {

        $ids = isset($_REQUEST['ids']) ? $_REQUEST['ids'] : false;
        if ($ids) {
            if (is_array($ids)) {
                $ids = implode(',', $ids);
                $map['adv_id'] = array('in', $ids);
            } else {
                $map = 'adv_id=' . $ids;
            }
            if (M('adv')->where($map)->save(array("adv_status=-1"))) {
                addlog('删除焦点图，ID：' . $ids);
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
        $data['adv_title'] = I('post.adv_title', '', 'strip_tags');
        if (!$data['adv_title']) {
            $this->error('请填写广告说明！');
        }
        
        $adv_type = I('post.adv_type',1,'intval');
        if($adv_type==1){
            $data['adv_url'] = I('post.adv_url', '', 'strip_tags');
            if(!$data['adv_url']){
                $this->error('请填写链接！');
            }
        }else{
            $data['adv_content'] = I('post.url', '', 'adv_content');
            if(!$data['adv_content']){
                $this->error('请填写内页内容！');
            }
        }
        
        $data['adv_weight'] = I('post.adv_weight', '1', 'intval');
        $data['adv_img'] = I('post.adv_img', '', 'strip_tags');
        if ($data['adv_img'] == '') {
            $this->error('请上传图片！');
        }
        $data['adv_status'] = 1;
        if ($id) {
            $data['adv_updated_at'] = time();
            M('adv')->data($data)->where('adv_id=' . $id)->save();
            addlog('修改焦点图，ID：' . $id);
        } else {
            
            $total = M('adv')->where("adv_status>=0")->count();
            if($total>=5){
                $this->error("最多添加5条哦");
            }
            $data['adv_created_at'] = time();
            M('adv')->data($data)->add();
            addlog('新增焦点图');
        }

        $this->success('恭喜，操作成功！', U('index'));
    }
}