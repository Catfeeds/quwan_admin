<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：文章控制器。
 *
 **/

namespace Admin\Controller;


class CategoryController extends ComController
{

    public function index()
    {

        $cid_type = I("get.cid_type",1,'intval');
        if($cid_type<1 || $cid_type>6){
            $cid_type=1;
        }
        $category = M('cid')->where("cid_status>=0 and cid_type=".$cid_type)->order('cid_sort asc')->select();
//         $category = $this->getMenu($category);
//         print_R($cid_type);
        $this->assign('category', $category);
        $this->assign("cid_type_status",$cid_type);
        $this->display();
    }

    public function del()
    {

        $cid_type = I("get.cid_type",1,'intval');
        if($cid_type<1 || $cid_type>6){
            $cid_type=1;
        }
        $id = isset($_GET['cid_id']) ? intval($_GET['cid_id']) : false;
        if ($id) {
            $data['cid_id'] = $id;
            $category = M('cid');
            $category->where('cid_id=' . $id)->save(array('cid_status'=>-1));
                addlog('删除分类，ID：' . $id);
            die('1');
        } else {
            die('0');
        }

    }

    public function edit()
    {

        $id = isset($_GET['cid_id']) ? intval($_GET['cid_id']) : false;
        $currentcategory = M('cid')->where('cid_id=' . $id)->find();
        $this->assign('currentcategory', $currentcategory);
        $cid_type = I("get.cid_type",1,'intval');
        if($cid_type<1 || $cid_type>6){
            $cid_type=1;
        }
//         $category = M('cid')->field('cid_id id,cid_pid pid,cid_name name')->where("cid_type=".$cid_type." and cid_id <> {$id}")->order('cid_sort asc')->select();
//         $tree = new Tree($category);
//         $str = "<option value=\$id \$selected>\$spacer\$name</option>"; //生成的形式
//         $category = $tree->get_tree(0, $str, $currentcategory['cid_pid']);

//         $this->assign('category', $category);
        $this->assign("cid_type",$cid_type);
        $this->display('form');
    }

    public function add()
    {

        $pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
        $cid_type = I("get.cid_type",1,'intval');
        if($cid_type<1 || $cid_type>6){
            $cid_type=1;
        }
//         $category = M('cid')->field('cid_id id,cid_pid pid,cid_name name')->where("cid_type=".$cid_type)->order('cid_sort asc')->select();
//         $tree = new Tree($category);
//         $str = "<option value=\$id \$selected>\$spacer\$name</option>"; //生成的形式
//         $category = $tree->get_tree(0, $str, $pid);

//         $this->assign('category', $category);
        $this->assign("cid_type",$cid_type);
        $this->display('form');
    }

    public function update($act = null)
    {
        if ($act == 'order') {
            $id = I('post.cid_id', 0, 'intval');
            if (!$id) {
                die('0');
            }
            $o = I('post.cid_sort', 0, 'intval');
            M('cid')->data(array('cid_sort' => $o))->where("cid_id='{$id}'")->save();
            addlog('分类修改排序，ID：' . $id);
            die('1');
        }elseif ($act == 'status') {
            $id = I('post.cid_id', 0, 'intval');
            if (!$id) {
                die('0');
            }
            $o = I('post.cid_status', 0, 'intval');
            M('cid')->data(array('cid_status' => $o))->where("cid_id='{$id}'")->save();
            addlog('分类修改状态，ID：' . $id);
            die('1');
        }

        $id = I('post.cid_id', false, 'intval');
        $data['cid_type'] = I('post.cid_type', 1, 'intval');
        $data['cid_pid'] = I('post.cid_pid', 0, 'intval');
        $data['cid_name'] = I('post.cid_name');
        $data['cid_sort'] = I('post.cid_sort', 0, 'intval');
        $data['cid_status'] = I('post.cid_status', 0, 'intval');
        if ($data['cid_name'] == '') {
            $this->error('请输入正确的景点类型！');
        }
        
        if(mb_strlen($data['cid_name'])>6){
            $this->error('最大限制6个字！');
        }
        
        if($id){
            $where = "cid_name='{$data['cid_name']}' and cid_id<>".$id;
        }else{
            $where = "cid_name='{$data['cid_name']}'";
        }
        
        $count = M('cid')->where($where)->count();
        if($count){
            $this->error('分类已经存在了哦！');
        }
        
        
        if ($id) {
            if (M('cid')->data($data)->where('cid_id=' . $id)->save()) {
                addlog('文章分类修改，ID：' . $id . '，名称：' . $data['name']);
                $this->success('恭喜，分类修改成功！');
                die(0);
            }
        } else {
            $id = M('cid')->data($data)->add();
            if ($id) {
                addlog('新增分类，ID：' . $id . '，名称：' . $data['name']);
                $this->success('恭喜，新增分类成功！', 'index');
                die(0);
            }
        }
        $this->success('恭喜，操作成功！');
    }
}
