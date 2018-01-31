<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：商家景点控制器。
 *
 **/

namespace Admin\Controller;

use Admin\Model\GeoHashModel;
use Admin\Model\CommonModel;

class ShopattractionsController extends ComController
{
    
    private $type=1;

    public function index($p=1)
    {
        
        $shop_id = session("shop_id");
        
        if($this->Group_id==2 && !$shop_id){
            $this->error("请求错误，请刷新后试一试");
        }
        
        
        $p = intval($p) > 0 ? $p : 1;
        
        $article = M('attractions');
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $prefix = C('DB_PREFIX');
        $keyword = isset($_GET['keyword']) ? htmlentities($_GET['keyword']) : '';
        
        
        if($shop_id){
            $where = $prefix.'attractions.shop_id='.$shop_id;
        }else{
            $where = "1=1";
        }
        
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        
        if($status==1){
            $where .= ' and '.$prefix.'attractions.attractions_status=1';
        }elseif($status==2){
            $where .= ' and '.$prefix.'attractions.attractions_status=0';
        }else{
            $where .= ' and '.$prefix.'attractions.attractions_status>=0';
        }
        
        //默认按照时间降序
        $orderby = $prefix.'attractions.attractions_updated_at desc';
        
        
        if($keyword){
            $where .= " and (".$prefix."attractions.attractions_name like '%{$keyword}%' or ".$prefix."shop.shop_name like '%{$keyword}%' or ".$prefix."shop.shop_title like '%{$keyword}%')";
        }
        
        
        $count = $article->where($where)->join("left join ".$prefix."shop on ".$prefix."attractions.shop_id=".$prefix."shop.shop_id")->count();
        $list = $article->where($where)->join("left join ".$prefix."shop on ".$prefix."attractions.shop_id=".$prefix."shop.shop_id")->field($prefix."attractions.*,".$prefix."shop.shop_name,".$prefix."shop.shop_title")->order($orderby)->limit($offset . ',' . $pagesize)->select();
        if($list){
            $commonModel = new CommonModel();
            foreach($list as &$val){
                $val['img'] = $commonModel->getImgJoinOne($val['attractions_id'], $this->type);
                if(!strstr($val['img'],'http')){
                    $val['img'] = getQiniuImgUrl($val['img']);
                }
            }
        }
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('list', $list);
        $this->assign('page', $page);
        if($this->Group_id==2){
            $this->display();
        }else{
            $this->display('index_all');
        }
        
    }

    public function del()
    {
        $cid_type = I("get.cid_type",1,'intval');
        if($cid_type<1 || $cid_type>6){
            $cid_type=1;
        }
        $id = isset($_GET['attractions_id']) ? intval($_GET['attractions_id']) : false;
        if ($id) {
            $category = M('attractions');
            $res = $category->where('attractions_id=' . $id)->save(array("attractions_status"=>-1));
            if($res){
                $action=3;
                
                $searchUpdate = D("search");
                $searchUpdate->delType($id,$this->type,$action);
            }
                addlog('删除景点，ID：' . $id);
            $this->success("删除景点成功");
        } else {
            $this->error("删除景点失败");
        }

    }

    public function edit()
    {
        $attractions_id = isset($_GET['attractions_id']) ? intval($_GET['attractions_id']) : false;
        
        if(!$attractions_id){
            $this->error("请求错误，请稍后再试");
        }
        
        $info = M('attractions')->where(array("attractions_id"=>$attractions_id))->find();
        if(!$info){
            $this->error("景点不存在，刷新后重试");
        }
        $shop_id = session("shop_id");
        if(($this->Group_id==2 && $info['shop_id'] == $shop_id) || $this->Group_id){
            //通过
        }else{
            $this->error("请求错误，请刷新后试一试");
        }

        $CommonModel = new CommonModel();
        $info['img'] = $CommonModel->getImgJoin($attractions_id, $this->type);
        $info['img'] = implode('|', $info['img']);
        //             print_R($article);
        // print_R($info);
        // die;
        $info['attractions_intro'] = htmlspecialchars_decode($info['attractions_intro']);
        $res = $CommonModel->getDestination_join($info['attractions_id'], $this->type);
        $info['destination_id'] = $res;
        
        $this->assign("info",$info);
        
        $cid_type=1;
        $category = M('cid')->where("cid_status=1 and cid_type=".$cid_type)->order('cid_sort asc')->select();
    
        $this->assign('category', $category);
        //查询景点对应的分类信息
        $list = $CommonModel->getCidJoin($attractions_id, $this->type);
        $infoCid = array();
        if($list){
            foreach($list as $val){
                $infoCid[$val] = 1;
            }
        }
        $this->assign('infoCid',$infoCid);
        $destination = M('destination')->where("destination_status=1")->order('destination_sort asc')->select();
        $this->assign("destination",$destination);
        
        if($this->Group_id==2){
            $this->display('form');
        }else{
            $this->display('form_all');
        }
        
    }

    public function add()
    {
        if($this->Group_id!=2){
            $this->error("请用商家账号新增");
        }
        $cid_type=1;
        $category = M('cid')->where("cid_status=1 and cid_type=".$cid_type)->order('cid_sort asc')->select();
    
        $this->assign('category', $category);
        $infoCid = array();
        $this->assign('infoCid',$infoCid);
        $destination = M('destination')->where("destination_status=1")->order('destination_sort asc')->select();
        $this->assign("destination",$destination);
        $this->display('form_add');
    }

    /**
     * 新增景点
     */
    public function update_add(){
        $shop_id = session("shop_id");
        //新增的时候，必须是商家账号
        if(!$shop_id || $this->Group_id!=2){
            $this->error("请用商家账号新增");
        }
        
        $data['attractions_name'] = isset($_POST['attractions_name']) ? $_POST['attractions_name'] : false;//景点名称
        if(!$data['attractions_name'] || mb_strlen($data['attractions_name'])>20){
            $this->error("景点名称不能为空，且不能超过20个字");
        }
        
        $data['attractions_open_time'] = isset($_POST['attractions_open_time']) ? $_POST['attractions_open_time'] : '';//开放时间
        
        if(!$data['attractions_open_time']){
            $this->error("开放时间不能为空");
        }
        
        $data['attractions_phone'] = I('post.attractions_phone', '', 'strip_tags');//联系电话
        if(!$data['attractions_phone']){
            $this->error("电话号码不能为空");
        }
        if(!checkPhone($data['attractions_phone']) && !checkMobile($data['attractions_phone'])){
            $this->error("请填写正电话号码/手机号码");
        }
        $data['attractions_status'] = I('post.attractions_status', '0', 'intval');//状态
        
        $data['attractions_address'] = I('post.attractions_address', '', 'strip_tags');//详细地址
        $data['attractions_lon'] = I('post.attractions_lon');//经度
        $data['attractions_lat'] = I('post.attractions_lat');//维度
        
        if(!$data['attractions_address'] || !$data['attractions_lon'] || !$data['attractions_lat']){
            $this->error("请使用定位功能，获取位置");
        }
        
        $geoHashModel = new GeoHashModel();
        $data['attractions_geohash'] = $geoHashModel->encode($data['attractions_lat'], $data['attractions_lon']);
        
        
        $data['attractions_is_refund'] = I('post.attractions_is_refund', '0', 'intval');//是否可以退款
        
        $data['attractions_suggest'] = I('post.attractions_suggest');//建议游玩时长
        if(!$data['attractions_suggest']){
            $this->error("请填写建议游玩时长");
        }
        $data['attractions_price'] = I('post.attractions_price','0.00','float');
        if($data['attractions_price']<0){
            $this->error("销售价格必须大于等于0");
        }
        
        if($data['attractions_price']>=100000000){
            $this->error("销售价格不能大于100000000");
        }
        
        
        $destination_id = I('post.destination_id',0,'intval');
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
        
        
        $data['attractions_intro'] = I('post.attractions_intro');
        if(!$data['attractions_intro']){
            $this->error("景点介绍必须填写");
        }
        // $data['attractions_intro'] = htmlspecialchars($data['attractions_intro']);
        
        $cid_id = $_POST['cid_id'];
        if(count($cid_id)<1){
            $this->error("最少选择一个分类");
        }
        
        $CommonModel = D('Common');
        $CommonModel->startTrans();
        $data['attractions_updated_at'] = time();
        $data['shop_id'] = $shop_id;
        $data['attractions_created_at'] = time();
        $attractions_id = M('attractions')->data($data)->add();
        if ($attractions_id) {
            //更新目的地对应
            $res = $CommonModel->destination_join($destination_id, $attractions_id, $this->type);
            if(!$res['status']){
                $CommonModel->rollback();
                $this->error($res['msg']);
            }
            //更新图片对应
            $CommonModel->ImgJoin($attractions_id, $this->type, $Img);
            //更新分类对应
            $CommonModel->setCidJoin($attractions_id, $this->type, $cid_id);
            addlog('新增景点，attractions_id：' . $attractions_id.":".json_encode($_POST));
        
            $CommonModel->commit();
            if($data['attractions_status']){
                $action=1;
                
                $searchUpdate = D("search");
                $searchUpdate->delType($attractions_id,$this->type,$action);
            }
            $this->success('恭喜！景点添加成功！',U('index'));
        } else {
            $CommonModel->rollback();
            $this->error('抱歉，未知错误！');
        }
    }
    
    //商家更新
    public function update_my(){
        $shop_id = session("shop_id");
        if(!$shop_id){
            $this->error("请用商家账号新增");
        }
        
        $data = array();
        $data['attractions_id'] = isset($_POST['attractions_id']) ? intval($_POST['attractions_id']) : 0;
        
        if(!$data['attractions_id']){
            $this->error("商品不存在");
        }
        //读取商品库，看是否有权限修改
        $attrInfo = M('attractions')->where(array("attractions_id"=>$data['attractions_id']))->find();
        if(!$attrInfo){
            $this->error("商品不存在");
        }
        //是商家，如果商品不等于
        if($attrInfo['shop_id']!=$shop_id){
            $this->error("系统错误，请刷新再试");
        }
        //开启上下架
        if(I("get.act")=='status'){
            if(!$data['attractions_id']){
                $this->error("系统错误");
            }
            $data['attractions_status'] = intval(I('attractions_status'))?1:0;
            $data['attractions_updated_at'] = time();
            $res = M('attractions')->data($data)->where('attractions_id=' . $data['attractions_id'])->save();
            addlog('景点，attractions_id：' . $data['attractions_id'].":".intval(I('attractions_status'))?"上架":"下架");
            if($res){
                if($data['attractions_status']){
                    $action = 1;
                }else{
                    $action = 3;
                }
                
                $searchUpdate = D("search");
                $searchUpdate->delType($data['attractions_id'],$this->type,$action);
            }
            $this->success("修改状态成功");
        }
        
        
        $data['attractions_name'] = isset($_POST['attractions_name']) ? $_POST['attractions_name'] : false;//景点名称
        if(!$data['attractions_name'] || mb_strlen($data['attractions_name'])>20){
            $this->error("景点名称不能为空，且不能超过20个字");
        }
        
        $data['attractions_open_time'] = isset($_POST['attractions_open_time']) ? $_POST['attractions_open_time'] : '';//开放时间
        
        if(!$data['attractions_open_time']){
            $this->error("开放时间不能为空");
        }
        
        $data['attractions_phone'] = I('post.attractions_phone', '', 'strip_tags');//联系电话
        if(!$data['attractions_phone']){
            $this->error("电话号码不能为空");
        }
        if(!checkPhone($data['attractions_phone']) && !checkMobile($data['attractions_phone'])){
            $this->error("请填写正电话号码/手机号码");
        }
        $data['attractions_status'] = I('post.attractions_status', '0', 'intval');//状态
        
        $data['attractions_address'] = I('post.attractions_address', '', 'strip_tags');//详细地址
        $data['attractions_lon'] = I('post.attractions_lon');//经度
        $data['attractions_lat'] = I('post.attractions_lat');//维度
        
        if(!$data['attractions_address'] || !$data['attractions_lon'] || !$data['attractions_lat']){
            $this->error("请使用定位功能，获取位置");
        }
        
        $geoHashModel = new GeoHashModel();
        $data['attractions_geohash'] = $geoHashModel->encode($data['attractions_lat'], $data['attractions_lon']);
        
        
        $data['attractions_is_refund'] = I('post.attractions_is_refund', '0', 'intval');//是否可以退款
        
        $data['attractions_suggest'] = I('post.attractions_suggest');//建议游玩时长
        if(!$data['attractions_suggest']){
            $this->error("请填写建议游玩时长");
        }
        $data['attractions_price'] = I('post.attractions_price','0.00','float');
        if($data['attractions_price']<0){
            $this->error("销售价格必须大于等于0");
        }
        if($data['attractions_price']>=100000000){
            $this->error("销售价格不能大于100000000");
        }
        
        $destination_id = I('post.destination_id',0,'intval');
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
        
        
        $data['attractions_intro'] = I('post.attractions_intro');
        if(!$data['attractions_intro']){
            $this->error("景点介绍必须填写");
        }
        // $data['attractions_intro'] = htmlspecialchars($data['attractions_intro']);
        
        $cid_id = $_POST['cid_id'];
        if(count($cid_id)<1){
            $this->error("最少选择一个分类");
        }
        
        $CommonModel = D('Common');
        $CommonModel->startTrans();
        $data['attractions_updated_at'] = time();
        
        M('attractions')->data($data)->where('attractions_id=' . $data['attractions_id'])->save();
        //更新目的地对应
        $res = $CommonModel->destination_join($destination_id, $data['attractions_id'], $this->type);
        if(!$res['status']){
            $CommonModel->rollback();
            $this->error($res['msg']);
        }
        //更新图片对应
        $CommonModel->ImgJoin($data['attractions_id'], $this->type, $Img);
        //更新分类对应
        $CommonModel->setCidJoin($data['attractions_id'], $this->type, $cid_id);
        
        addlog('编辑景点，attractions_id：' . $data['attractions_id'].':'.json_encode($_POST));
        $CommonModel->commit();
        
        if($data['attractions_status']){
            $action = 2;
        }else{
            $action = 3;
        }
        
        $searchUpdate = D("search");
        $searchUpdate->delType($data['attractions_id'],$this->type,$action);
        $this->success('恭喜！景点编辑成功！');
    }
    
    //管理员更新
    public function update($act = null)
    {
        if($this->Group_id==2){
            $this->error("请刷新重试");
        }
        
        $data = array();
        $data['attractions_id'] = isset($_POST['attractions_id']) ? intval($_POST['attractions_id']) : 0;
        
        if(!$data['attractions_id']){
            $this->error("商品不存在");
        }
        //读取商品库，看是否有权限修改
        $attrInfo = M('attractions')->where(array("attractions_id"=>$data['attractions_id']))->find();
        if(!$attrInfo){
            $this->error("商品不存在");
        }
        
        //开启上下架
        if(I("get.act")=='status'){
            if(!$data['attractions_id']){
                $this->error("系统错误");
            }
            $data['attractions_status'] = intval(I('attractions_status'))?1:0;
            $data['attractions_updated_at'] = time();
            $res = M('attractions')->data($data)->where('attractions_id=' . $data['attractions_id'])->save();
            addlog('景点，attractions_id：' . $data['attractions_id'].":".intval(I('attractions_status'))?"上架":"下架");
            if($res){
                if($data['attractions_status']){
                    $action = 1;
                }else{
                    $action = 3;
                }
            
                $searchUpdate = D("search");
                $searchUpdate->delType($data['attractions_id'],$this->type,$action);
            }
            $this->success("修改状态成功");
        }
        
        
        $data['attractions_name'] = isset($_POST['attractions_name']) ? $_POST['attractions_name'] : false;//景点名称
        if(!$data['attractions_name'] || mb_strlen($data['attractions_name'])>20){
            $this->error("景点名称不能为空，且不能超过20个字");
        }
        
        $data['attractions_open_time'] = isset($_POST['attractions_open_time']) ? $_POST['attractions_open_time'] : '';//开放时间
        
        if(!$data['attractions_open_time']){
            $this->error("开放时间不能为空");
        }
        
        $data['attractions_phone'] = I('post.attractions_phone', '', 'strip_tags');//联系电话
        if(!$data['attractions_phone']){
            $this->error("电话号码不能为空");
        }
        if(!checkPhone($data['attractions_phone']) && !checkMobile($data['attractions_phone'])){
            $this->error("请填写正电话号码/手机号码");
        }
        $data['attractions_status'] = I('post.attractions_status', '0', 'intval');//状态
        
        $data['attractions_address'] = I('post.attractions_address', '', 'strip_tags');//详细地址
        $data['attractions_lon'] = I('post.attractions_lon');//经度
        $data['attractions_lat'] = I('post.attractions_lat');//维度
        
        if(!$data['attractions_address'] || !$data['attractions_lon'] || !$data['attractions_lat']){
            $this->error("请使用定位功能，获取位置");
        }
        
        $geoHashModel = new GeoHashModel();
        $data['attractions_geohash'] = $geoHashModel->encode($data['attractions_lat'], $data['attractions_lon']);
        
        
        
        
        $data['attractions_suggest'] = I('post.attractions_suggest');//建议游玩时长
        if(!$data['attractions_suggest']){
            $this->error("请填写建议游玩时长");
        }
        $destination_id = I('post.destination_id',0,'intval');
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
        
        
        $data['attractions_intro'] = I('post.attractions_intro');
        // print_R($data);
        // die;
        if(!$data['attractions_intro']){
            $this->error("景点介绍必须填写");
        }
        // $data['attractions_intro'] = htmlspecialchars($data['attractions_intro']);
        
        $cid_id = $_POST['cid_id'];
        if(count($cid_id)<1){
            $this->error("最少选择一个分类");
        }
        
        $CommonModel = D('Common');
        $CommonModel->startTrans();
        $data['attractions_updated_at'] = time();
        
        M('attractions')->data($data)->where('attractions_id=' . $data['attractions_id'])->save();
        //更新目的地对应
        $res = $CommonModel->destination_join($destination_id, $data['attractions_id'], $this->type);
        if(!$res['status']){
            $CommonModel->rollback();
            $this->error($res['msg']);
        }
        //更新图片对应
        $CommonModel->ImgJoin($data['attractions_id'], $this->type, $Img);
        //更新分类对应
        $CommonModel->setCidJoin($data['attractions_id'], $this->type, $cid_id);
        
        addlog('编辑景点，attractions_id：' . $data['attractions_id'].':'.json_encode($_POST));
        $CommonModel->commit();
        if($data['attractions_status']){
            $action = 2;
        }else{
            $action = 3;
        }
        
        $searchUpdate = D("search");
        $searchUpdate->delType($data['attractions_id'],$this->type,$action);
        $this->success('恭喜！景点编辑成功！');
    }
    
    /**
     * 评论管理
     * @param unknown $hotel_id
     * @param number $p
     */
    public function replay($attractions_id,$p=1){
        $p = intval($p) > 0 ? $p : 1;
    
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
    
        $aid = intval($attractions_id);
        $article = M('attractions')->where('attractions_id=' . $aid)->find();
        if(!$article){
            $this->error("请求错误，请稍后再试",U('index'));
        }
    
        $this->assign("info",$article);
    
        $ReplayModel = D('Replay');
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
                $res = M('attractions')->where(array("attractions_id"=>$hall_id))->setDec("attractions_score_num");
                
                $action = 2;
                
                $searchUpdate = D("search");
                $searchUpdate->delType($hall_id,$this->type,$action);
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
