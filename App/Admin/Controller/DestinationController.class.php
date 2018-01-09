<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：目的地控制器。
 *
 **/

namespace Admin\Controller;

class DestinationController extends ComController
{

    public function index()
    {
        $category = M('destination')->where("destination_status>=0")->order("destination_sort asc")->select();
        $this->assign('destination', $category);
        $this->display();
    }

    public function del()
    {
        $id = isset($_GET['destination_id']) ? intval($_GET['destination_id']) : false;
        if ($id) {
            $data['destination_id'] = $id;
            $category = M('destination');
            $category->where('destination_id=' . $id)->save(array("destination_status"=>-1));
                addlog('删除目的地，ID：' . $id);
            die('1');
        } else {
            die('0');
        }

    }

    public function edit()
    {

        $id = isset($_GET['destination_id']) ? intval($_GET['destination_id']) : false;
        $currentcategory = M('destination')->where('destination_id=' . $id)->find();
        $this->assign('destination', $currentcategory);
        
        $this->display('form');
    }

    public function add()
    {
        $this->display('form');
    }

    public function update($act = null)
    {
        if ($act == 'order') {
            $id = I('post.destination_id', 0, 'intval');
            if (!$id) {
                die('0');
            }
            $o = I('post.destination_sort', 0, 'intval');
            $data = array('destination_sort' => $o,'destination_updated_at'=>time());
            M('destination')->data($data)->where("destination_id='{$id}'")->save();
            $data['destination_id'] = $id;
            addlog('目的地排序修改，ID：' . json_encode($data));
            die('1');
        }elseif ($act == 'status') {
            $id = I('post.destination_id', 0, 'intval');
            if (!$id) {
                die('0');
            }
            $o = I('post.destination_status', 0, 'intval');
            $data = array('destination_status' => $o,'destination_updated_at'=>time());
            M('destination')->data($data)->where("destination_id='{$id}'")->save();
            addlog('目的地修改状态，ID：' . $id);
            die('1');
        }

        $id = I('post.destination_id', false, 'intval');
        $data['destination_status'] = I('post.destination_status', 0, 'intval');
        $data['destination_name'] = I('post.destination_name');
        $data['destination_sort'] = I('post.destination_sort', 0, 'intval');
        
        
        
        if ($data['destination_name'] == '') {
            $this->error('请输入正确的目的地类型！');
        }
        
        if(mb_strlen($data['destination_name'])>20){
            $this->error('最大限制20个字！');
        }
        
        if($id){
            $where = "destination_name='{$data['destination_name']}' and destination_id<>".$id;
        }else{
            $where = "destination_name='{$data['destination_name']}'";
        }
        
        $count = M('destination')->where($where)->count();
        if($count){
            $this->error('目的地已经存在了哦！');
        }
        
        
        if ($id) {
            $data['destination_updated_at'] = time();
            if (M('destination')->data($data)->where('destination_id=' . $id)->save()) {
                addlog('目的地修改，ID：' . $id . '，名称：' . $data['destination_name']);
                $this->success('恭喜，目的地修改成功！',U('index'));
                die(0);
            }
        } else {
            $data['destination_created_at'] = time();
            $data['destination_updated_at'] = time();
            $id = M('destination')->data($data)->add();
            if ($id) {
                addlog('新增目的地，ID：' . $id . '，名称：' . $data['destination_name']);
                $this->success('恭喜，新增目的地成功！',U('index'));
                die(0);
            }
        }
        $this->error('操作失败！');
    }
}
