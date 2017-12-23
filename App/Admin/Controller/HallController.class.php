<?php
/**
* 餐饮控制器
* @date: 2017年11月23日 下午2:53:10
* @author: bobo
* @qq:273719650
* @email:273719650@qq.com
* @version:
* ==============================================
* 版权所有 2017-2017 http://www.bobolucy.com
* ==============================================
*/
namespace Admin\Controller;

use Admin\Model\CommonModel;
use Admin\Model\GeoHashModel;

class HallController extends ComController
{
    
    private $type = 6;

    public function add()
    {

        
        $destination = M('destination')->where("destination_status=1")->order('destination_sort asc')->select();
        $this->assign("destination",$destination);
        
        $this->display('form');
    }

    public function index($sid = 0, $p = 1)
    {


        $p = intval($p) > 0 ? $p : 1;

        $article = M('hall');
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $prefix = C('DB_PREFIX');
        $keyword = isset($_GET['keyword']) ? htmlentities($_GET['keyword']) : '';
        $where = '1 = 1 ';
        
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        
        if($status==1){
            $where .= ' and hall_status=1';
        }elseif($status==2){
            $where .= ' and hall_status=0';
        }else{
            $where .= ' and hall_status>=0';
        }
        
        //默认按照时间降序
        $orderby = "hall_updated_at desc";
        
        
        if($keyword){
            $where .= " and hall_name like '%{$keyword}%'";
        }
        
        

        $count = $article->where($where)->count();
        $list = $article->where($where)->order($orderby)->limit($offset . ',' . $pagesize)->select();
        if($list){
            $commonModel = new CommonModel();
            foreach($list as &$val){
                $val['img'] = $commonModel->getImgJoinOne($val['hall_id'], $this->type);
                if(!strstr($val['img'],'http')){
                    $val['img'] = getQiniuImgUrl($val['img']);
                }
            }
        }
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function del()
    {

        $aids = isset($_REQUEST['hall_id']) ? $_REQUEST['hall_id'] : false;
        if ($aids) {
            $map = 'hall_id=' . $aids;
            if (M('hall')->where($map)->delete()) {
                
                $action=3;
                
                $searchUpdate = D("search");
                $searchUpdate->delType($aids,$this->type,$action);
                
                addlog('删除餐饮，AID：' . $aids);
                $CommonModel = new CommonModel();
                $CommonModel->delId($aids,$this->type);
                $this->success('恭喜，餐饮删除成功！');
            } else {
                $this->error('参数错误！');
            }
        } else {
            $this->error('参数错误！');
        }

    }

    public function edit($hall_id)
    {

        $aid = intval($hall_id);
        $article = M('hall')->where('hall_id=' . $aid)->find();
        if ($article) {
            $CommonModel = new CommonModel();
            $article['img'] = $CommonModel->getImgJoin($article['hall_id'], $this->type);
            $article['img'] = implode('|', $article['img']);
//             print_R($article);
            $article['hall_intro'] = htmlspecialchars_decode($article['hall_intro']);
            $res = $CommonModel->getDestination_join($article['hall_id'], $this->type);
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
            $hall_id = intval(I('hall_id'));
            if(!$hall_id){
               $this->error("系统错误"); 
            }
            $data['hall_status'] = intval(I('hall_status'))?1:0;
            $data['hall_updated_at'] = time();
            $res = M('hall')->data($data)->where('hall_id=' . $hall_id)->save();
            addlog('餐厅，hall_id：' . $hall_id.":".intval(I('hall_status'))?"上架":"下架");
            
            if($res){
                if($data['hall_status']==0){//下架是删除
                    $action = 3;
                }else{//上架是新增
                    $action = 1;
                }
                
                $searchUpdate = D("search");
                $searchUpdate->delType($hall_id,$this->type,$action);
            }
            $this->success("Ok");
        }
        
        $data['hall_id'] = isset($_POST['hall_id']) ? intval($_POST['hall_id']) : 0;
        $data['hall_name'] = isset($_POST['hall_name']) ? $_POST['hall_name'] : false;
        $data['hall_open_time'] = isset($_POST['hall_open_time']) ? $_POST['hall_open_time'] : '';
        $data['hall_phone'] = I('post.hall_phone', '', 'strip_tags');
        $data['hall_address'] = I('post.hall_address', '', 'strip_tags');
        $data['hall_status'] = I('post.hall_status', '0', 'intval');
        $data['hall_lon'] = I('post.hall_lon');
        $data['hall_lat'] = I('post.hall_lat');
        
        $data['hall_intro'] = I('post.hall_intro');
        $destination_id = I('post.destination_id',0,'intval');
        if(!$data['hall_name'] || mb_strlen($data['hall_name'])>20){
            $this->error("餐饮名称不能为空，且不能超过20个字");
        }
        
        if(!$data['hall_open_time']){
            $this->error("开放时间不能为空");
        }
        
        if(!$data['hall_address'] || !$data['hall_lon'] || !$data['hall_lat']){
            $this->error("请使用定位功能，获取位置");
        }
        
        $geoHashModel = new GeoHashModel();
        $data['hall_geohash'] = $geoHashModel->encode($data['hall_lat'], $data['hall_lon']);
        
        if(!$data['hall_phone']){
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
        
        
        if(!$data['hall_intro']){
            $this->error("餐饮介绍必须填写");
        }
        $data['hall_intro'] = htmlspecialchars($data['hall_intro']);
        $CommonModel = D('Common');
        $CommonModel->startTrans();
        $data['hall_updated_at'] = time();
        if ($data['hall_id']) {
            
            M('hall')->data($data)->where('hall_id=' . $data['hall_id'])->save();
            //更新目的地对应
            $res = $CommonModel->destination_join($destination_id, $data['hall_id'], $this->type);
            if(!$res['status']){
                $CommonModel->rollback();
                $this->error($res['msg']);
            }
            //更新图片对应
            $CommonModel->ImgJoin($data['hall_id'], $this->type, $Img);
            
            addlog('编辑餐饮，hall_id：' . $data['hall_id'].':'.json_encode($_POST));
            $CommonModel->commit();
            
            if($data['hall_status']==0){//下架是删除
                $action = 3;
            }else{//上架是新增
                $action = 2;
            }
            $searchUpdate = D("search");
            $searchUpdate->delType($hall_id,$this->type,$action);
            
            $this->success('恭喜！餐饮编辑成功！');
        } else {
            $data['hall_created_at'] = time();
            $hall_id = M('hall')->data($data)->add();
            if ($hall_id) {
                //更新目的地对应
                $res = $CommonModel->destination_join($destination_id, $hall_id, $this->type);
                if(!$res['status']){
                    $CommonModel->rollback();
                    $this->error($res['msg']);
                }
                //更新图片对应
                $CommonModel->ImgJoin($hall_id, $this->type, $Img);
                addlog('新增餐饮，hall_id：' . $hall_id.":".json_encode($_POST));
                $CommonModel->commit();
                
                if($data['hall_status']==1){//下架是删除
                    $action = 1;
                    $searchUpdate = D("search");
                    $searchUpdate->delType($hall_id,$this->type,$action);
                }
                $this->success('恭喜！餐饮添加成功！',U('index'));
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
    public function replay($hall_id,$p=1){
        $p = intval($p) > 0 ? $p : 1;
    
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
    
        $aid = intval($hall_id);
        $article = M('hall')->where('hall_id=' . $aid)->find();
        if(!$article){
            $this->error("请求错误，请稍后再试",U('index'));
        }
    
        $this->assign("info",$article);
    
        $ReplayModel = D('replay');
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
            $up['score_status']=0;
            $res = M('score')->where($map)->save($up);
            if($res){
                $hall_id = M('score')->where($map)->getField("join_id");
                $res = M('hall')->where(array("hall_id"=>$hall_id))->setDec("hall_score_num");
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