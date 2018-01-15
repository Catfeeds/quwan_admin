<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-01-21
 * 版    本：1.0.0
 * 功能说明：红包控制。
 *
 **/

namespace Admin\Controller;

class RedSettingController extends ComController
{

    //flash焦点图
    public function index()
    {

        $list = M('red_status')->where("red_id=1")->find();
        $this->assign('info', $list);
        $this->display();
    }

    //保存焦点图
    public function update($id = 0)
    {
        $post = $_POST;
        $data = array();
        $data['red_status'] = I('post.red_status',0,"intval");
        $data['red_start_num'] = I('post.red_start_num',0,"intval");
        $data['red_end_num'] = I('post.red_end_num',0,"intval");
        
        if($data['red_status']==1 && $data['red_start_num']>=$data['red_end_num']){
            $this->error("开启红包后，开始金额不能大于等于结束金额");   
        }
        
        
        if($data['red_status']==1 && $data['red_start_num']<1){
            $this->error("开启红包后，开始金额必须大于等于1");
        }
        
        $data['red_id'] = 1;
        $info = M("red_status")->where(array("red_id"=>1))->find();

        if($info['red_status'] == $data['red_status'] && $info['red_start_num']==$data['red_start_num'] && $info['red_end_num']==$data['red_end_num']){
            $this->error('您未修改哦！');
        }

        if($info){
            $res = M("red_status")->where(array("red_id"=>1))->save($data);
        }else{
            $res = M("red_status")->add($data);
        }
        if($res){
            addlog('红包状态调整：' . json_encode($data));
            $this->success('恭喜，操作成功！', U('index'));
        }else{
            $this->error('更新错误！');
        }
        
    }
}