<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：酒店控制器。
 *
 **/

namespace Admin\Controller;

use Admin\Model\CommonModel;
use Admin\Model\GeoHashModel;
use Admin\Model\ReplayModel;

class HotelController extends ComController
{
    private $type = 5;
    public function add()
    {

        
        $destination = M('destination')->where("destination_status=1")->order('destination_sort asc')->select();
        $this->assign("destination",$destination);
        
        $this->display('form');
    }

    public function index($sid = 0, $p = 1)
    {


        $p = intval($p) > 0 ? $p : 1;

        $article = M('hotel');
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $prefix = C('DB_PREFIX');
        $keyword = isset($_GET['keyword']) ? htmlentities($_GET['keyword']) : '';
        $where = '1 = 1 ';
        
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        
        if($status==1){
            $where .= ' and hotel_status=1';
        }elseif($status==2){
            $where .= ' and hotel_status=0';
        }else{
            $where .= ' and hotel_status>=0';
        }
        
        //默认按照时间降序
        $orderby = "hotel_updated_at desc";
        
        
        if($keyword){
            $where .= " and hotel_name like '%{$keyword}%'";
        }
        
        

        $count = $article->where($where)->count();
        $list = $article->where($where)->order($orderby)->limit($offset . ',' . $pagesize)->select();
        if($list){
            $commonModel = new CommonModel();
            foreach($list as &$val){
                $val['img'] = $commonModel->getImgJoinOne($val['hotel_id'], $this->type);
                if(!strstr($val['img'],'http')){
                    $val['img'] = getQiniuImgUrl($val['img']);
                }
            }
        }
//         print_R($list);
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function del()
    {

        $aids = isset($_REQUEST['hotel_id']) ? $_REQUEST['hotel_id'] : false;
        if ($aids) {
            $map = 'hotel_id=' . $aids;
            if (M('hotel')->where($map)->delete()) {
                
                $action=3;
                
                $searchUpdate = D("search");
                $searchUpdate->delType($aids,$this->type,$action);
                
                addlog('删除酒店，AID：' . $aids);
                $CommonModel = new CommonModel();
                $CommonModel->delId($aids,$this->type);
                $this->success('恭喜，酒店删除成功！');
            } else {
                $this->error('参数错误！');
            }
        } else {
            $this->error('参数错误！');
        }

    }
    

    public function edit($hotel_id)
    {

        $aid = intval($hotel_id);
        $article = M('hotel')->where('hotel_id=' . $aid)->find();
        if ($article) {
            $CommonModel = new CommonModel();
            $article['img'] = $CommonModel->getImgJoin($article['hotel_id'], $this->type);
            $article['img'] = implode('|', $article['img']);
//             print_R($article);
            $article['hotel_intro'] = htmlspecialchars_decode($article['hotel_intro']);
            $res = $CommonModel->getDestination_join($article['hotel_id'], $this->type);
            $article['destination_id'] = $res;
            $this->assign('info', $article);
        } else {
            $this->error('参数错误！');
        }
        $destination = M('destination')->where("destination_status=1")->order('destination_sort asc')->select();
        $this->assign("destination",$destination);
        $this->display('form');
    }

    public function update()
    {
        $data = array();
        if(I("get.act")=='status'){
            $hotel_id = intval(I('hotel_id'));
            if(!$hotel_id){
               $this->error("系统错误"); 
            }
            $data['hotel_status'] = intval(I('hotel_status'))?1:0;
            $data['hotel_updated_at'] = time();
            $res = M('hotel')->data($data)->where('hotel_id=' . $hotel_id)->save();
            addlog('酒店，hotel_id：' . $hotel_id.":".intval(I('hotel_status'))?"上架":"下架");
            
            if($res){
                if($data['hall_status']==0){//下架是删除
                    $action = 3;
                }else{//上架是新增
                    $action = 2;
                }
            
                $searchUpdate = D("search");
                $searchUpdate->delType($hotel_id,$this->type,$action);
            }
            
            $this->success("Ok");
        }
        
        $data['hotel_id'] = isset($_POST['hotel_id']) ? intval($_POST['hotel_id']) : 0;
        $data['hotel_name'] = isset($_POST['hotel_name']) ? $_POST['hotel_name'] : false;
        $data['hotel_open_time'] = isset($_POST['hotel_open_time']) ? $_POST['hotel_open_time'] : '';
        $data['hotel_phone'] = I('post.hotel_phone', '', 'strip_tags');
        $data['hotel_address'] = I('post.hotel_address', '', 'strip_tags');
        $data['hotel_status'] = I('post.hotel_status', '0', 'intval');
        $data['hotel_lon'] = I('post.hotel_lon');
        $data['hotel_lat'] = I('post.hotel_lat');
        
        $data['hotel_intro'] = I('post.hotel_intro');
        $destination_id = I('post.destination_id',0,'intval');
        if(!$data['hotel_name'] || mb_strlen($data['hotel_name'])>20){
            $this->error("酒店名称不能为空，且不能超过20个字");
        }
        
        if(!$data['hotel_open_time']){
            $this->error("开放时间不能为空");
        }
        
        if(!$data['hotel_address'] || !$data['hotel_lon'] || !$data['hotel_lat']){
            $this->error("请使用定位功能，获取位置");
        }
        
        $geoHashModel = new GeoHashModel();
        $data['hotel_geohash'] = $geoHashModel->encode($data['hotel_lat'], $data['hotel_lon']);
        
        if(!$data['hotel_phone']){
            $this->error("电话号码不能为空");
        }
        
        if(!$destination_id){
            $this->error("请选择目的地");
        }
        
        $ImgStr = I('img');
        $ImgStr = trim($ImgStr, '|');
        $Img = array();
        if (strlen($ImgStr) > 1) {
            $Img = explode('|', $ImgStr);
        }
        
        if(count($Img)>20 || count($Img)<1){
            $this->error("图集最少一张，最多20张");
        }
        
        
        if(!$data['hotel_intro']){
            $this->error("酒店介绍必须填写");
        }
        // $data['hotel_intro'] = htmlspecialchars($data['hotel_intro']);
        $CommonModel = D('Common');
        $CommonModel->startTrans();
        $data['hotel_updated_at'] = time();
        if ($data['hotel_id']) {
            
            M('hotel')->data($data)->where('hotel_id=' . $data['hotel_id'])->save();
            //更新目的地对应
            $res = $CommonModel->destination_join($destination_id, $data['hotel_id'], $this->type);
            if(!$res['status']){
                $CommonModel->rollback();
                $this->error($res['msg']);
            }
            //更新图片对应
            $CommonModel->ImgJoin($data['hotel_id'], $this->type, $Img);
            
            addlog('编辑酒店，hotel_id：' . $data['hotel_id'].':'.json_encode($_POST));
            $CommonModel->commit();
            
            if($data['hotel_status']==0){//下架是删除
                $action = 3;
            }else{//上架是新增
                $action = 2;
            }
            
            $searchUpdate = D("search");
            $searchUpdate->delType($data['hotel_id'],$this->type,$action);
            $this->success('恭喜！酒店编辑成功！');
        } else {
            $data['hotel_created_at'] = time();
            $hotel_id = M('hotel')->data($data)->add();
            if ($hotel_id) {
                //更新目的地对应
                $res = $CommonModel->destination_join($destination_id, $hotel_id, $this->type);
                if(!$res['status']){
                    $CommonModel->rollback();
                    $this->error($res['msg']);
                }
                //更新图片对应
                $CommonModel->ImgJoin($hotel_id, $this->type, $Img);
                addlog('新增酒店，hotel_id：' . $hotel_id.":".json_encode($_POST));
                $CommonModel->commit();
                
                if($data['hotel_status']==1){//下架是删除
                    $action = 1;
                    $searchUpdate = D("search");
                    $searchUpdate->delType($hotel_id,$this->type,$action);
                }
                
                $this->success('恭喜！酒店添加成功！',U('index'));
            } else {
                $CommonModel->rollback();
                $this->error('抱歉，未知错误！');
            }

        }
    }
    
    /**
     * 评论管理
     * @param unknown $hotel_id
     * @param number $p
     */
    public function replay($hotel_id,$p=1){
        $p = intval($p) > 0 ? $p : 1;
        
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        
        $aid = intval($hotel_id);
        $article = M('hotel')->where('hotel_id=' . $aid)->find();
        if(!$article){
            $this->error("请求错误，请稍后再试",U('index'));
        }
        
        $this->assign("info",$article);
        
        $ReplayModel = new ReplayModel();
        $res = $ReplayModel->getReplayList($aid, $this->type, $offset, $pagesize);
        $page = new \Think\Page($res['total'], $pagesize);
        $page = $page->show();
        $this->assign('list', $res['list']);
        $this->assign('page', $page);
        $this->display();
    }
    
    /**
     * 删除评论
     */
    public function replay_del(){
        $aids = isset($_REQUEST['score_id']) ? $_REQUEST['score_id'] : false;
//         echo $aids;
//         die;
        if ($aids) {
            $map = 'score_type='.$this->type.' and score_id=' . $aids;
            $up = array();
            $up['score_status']=-1;
            $res = M('score')->where($map)->save($up);
            
            
            if($res){
                $hall_id = M('score')->where($map)->getField("join_id");
                $res = M('hotel')->where(array("hotel_id"=>$hall_id))->setDec("hotel_score_num");
                if($res){
                    $action=2;
                    $searchUpdate = D("search");
                    $searchUpdate->delType($hall_id,$this->type,$action);
                }
            }
            
            addlog('删除评论，ID：' . $aids);
            $this->success('恭喜，评论删除成功！');
        } else {
            $this->error('参数错误！');
        }
    }
    
    /**
     * 回复评论
     */
    public function replay_submit(){
        $aids = isset($_REQUEST['score_id']) ? $_REQUEST['score_id'] : false;
        $content = I("post.content");
        if(mb_strlen($content)<10){
            $this->error("回复字数必须大于10个");
        }
        
        $res = D("Replay")->doReplay($aids,$content);
        if($res['status']){
            $this->success("回复成功");
        }else{
            $this->error($res['msg']);
        }
    }
}