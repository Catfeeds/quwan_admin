<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：商家节日控制器。
 *
 **/

namespace Admin\Controller;

use Admin\Model\GeoHashModel;
use Admin\Model\CommonModel;

class ShopholidayController extends ComController
{
    
    private $type=4;

    public function index($p=1)
    {
        
        $shop_id = session("shop_id");
        
        if($this->Group_id==2 && !$shop_id){
            $this->error("请求错误，请刷新后试一试");
        }
        
        
        $p = intval($p) > 0 ? $p : 1;
        
        $article = M('holiday');
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $prefix = C('DB_PREFIX');
        $keyword = isset($_GET['keyword']) ? htmlentities($_GET['keyword']) : '';
        
        
        if($shop_id){
            $where = $prefix.'holiday.shop_id='.$shop_id;
        }else{
            $where = "1=1";
        }
        
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        
        if($status==1){
            $where .= ' and '.$prefix.'holiday.holiday_status=1';
        }elseif($status==2){
            $where .= ' and '.$prefix.'holiday.holiday_status=0';
        }else{
            $where .= ' and '.$prefix.'holiday.holiday_status>=0';
        }
        
        //默认按照时间降序
        $orderby = $prefix.'holiday.holiday_updated_at desc';
        
        
        if($keyword){
            $where .= " and (".$prefix."holiday.holiday_name like '%{$keyword}%' or ".$prefix."shop.shop_name like '%{$keyword}%')";
        }
        
        
        $count = $article->where($where)->count();
        $list = $article->where($where)->join("left join ".$prefix."shop on ".$prefix."holiday.shop_id=".$prefix."shop.shop_id")->field($prefix."holiday.*,".$prefix."shop.shop_name")->order($orderby)->limit($offset . ',' . $pagesize)->select();
        if($list){
            $commonModel = new CommonModel();
            foreach($list as &$val){
                $val['img'] = $commonModel->getImgJoinOne($val['holiday_id'], $this->type);
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
        $id = isset($_GET['holiday_id']) ? intval($_GET['holiday_id']) : false;
        if ($id) {
            $category = M('holiday');
            $res = $category->where('holiday_id=' . $id)->save(array("holiday_status"=>-1));
            if($res){
                $action=3;
            
                $searchUpdate = D("search");
                $searchUpdate->delType($id,$this->type,$action);
            }
                addlog('删除节日，ID：' . $id);
            $this->success("删除节日成功");
        } else {
            $this->error("删除节日失败");
        }

    }

    public function edit()
    {
        $holiday_id = isset($_GET['holiday_id']) ? intval($_GET['holiday_id']) : false;
        
        if(!$holiday_id){
            $this->error("请求错误，请稍后再试");
        }
        
        $info = M('holiday')->where(array("holiday_id"=>$holiday_id))->find();
        if(!$info){
            $this->error("节日不存在，刷新后重试");
        }
        $shop_id = session("shop_id");
        if(($this->Group_id==2 && $info['shop_id'] == $shop_id) || $this->Group_id){
            //通过
        }else{
            $this->error("请求错误，请刷新后试一试");
        }

        $CommonModel = new CommonModel();
        $info['img'] = $CommonModel->getImgJoin($holiday_id, $this->type);
        $info['img'] = implode('|', $info['img']);
        //             print_R($article);
        $info['holiday_intro'] = htmlspecialchars_decode($info['holiday_intro']);
        $res = $CommonModel->getDestination_join($info['holiday_id'], $this->type);
        $info['destination_id'] = $res;
        
        $this->assign("info",$info);
        
//         $cid_type=1;
//         $category = M('cid')->where("cid_status=1 and cid_type=".$cid_type)->order('cid_sort asc')->select();
    
//         $this->assign('category', $category);
//         //查询节日对应的分类信息
//         $list = $CommonModel->getCidJoin($holiday_id, $this->type);
//         $infoCid = array();
//         if($list){
//             foreach($list as $val){
//                 $infoCid[$val] = 1;
//             }
//         }
//         $this->assign('infoCid',$infoCid);
        $destination = M('destination')->where("destination_status=1")->order('destination_sort asc')->select();
        $this->assign("destination",$destination);
        
        
        $route_list = M('holiday_join')->where(array("holiday_id"=>$holiday_id))->order("holiday_join_id asc")->select();
        
        if($route_list){
            
            foreach($route_list as &$info){
                $info['info'] = $CommonModel->getIdInfo($info['route_id'], 3);
            }
        }
        
        $this->assign("route_list",$route_list);
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
//         $cid_type=1;
//         $category = M('cid')->where("cid_status=1 and cid_type=".$cid_type)->order('cid_sort asc')->select();
    
//         $this->assign('category', $category);
//         $infoCid = array();
//         $this->assign('infoCid',$infoCid);
        $destination = M('destination')->where("destination_status=1")->order('destination_sort asc')->select();
        $this->assign("destination",$destination);
        $this->display('form_add');
    }

    /**
     * 新增节日
     */
    public function update_add(){
        $shop_id = session("shop_id");
        //新增的时候，必须是商家账号
        if(!$shop_id || $this->Group_id!=2){
            $this->error("请用商家账号新增");
        }
        
        $data['holiday_name'] = isset($_POST['holiday_name']) ? $_POST['holiday_name'] : false;//节日名称
        if(!$data['holiday_name'] || mb_strlen($data['holiday_name'])>20){
            $this->error("节日名称不能为空，且不能超过20个字");
        }
        
        $data['holiday_open_time'] = isset($_POST['holiday_open_time']) ? $_POST['holiday_open_time'] : '';//开放时间
        
        if(!$data['holiday_open_time']){
            $this->error("开放时间不能为空");
        }
        
        $data['holiday_phone'] = I('post.holiday_phone', '', 'strip_tags');//联系电话
        if(!$data['holiday_phone']){
            $this->error("电话号码不能为空");
        }
        
        $data['holiday_status'] = I('post.holiday_status', '0', 'intval');//状态
        
        $data['holiday_address'] = I('post.holiday_address', '', 'strip_tags');//详细地址
        $data['holiday_lon'] = I('post.holiday_lon');//经度
        $data['holiday_lat'] = I('post.holiday_lat');//维度
        
        if(!$data['holiday_address'] || !$data['holiday_lon'] || !$data['holiday_lat']){
            $this->error("请使用定位功能，获取位置");
        }
        
        $geoHashModel = new GeoHashModel();
        $data['holiday_geohash'] = $geoHashModel->encode($data['holiday_lat'], $data['holiday_lon']);
        
        
        $data['holiday_is_refund'] = I('post.holiday_is_refund', '0', 'intval');//是否可以退款
        
        $data['holiday_suggest'] = I('post.holiday_suggest');//建议游玩时长
        $data['holiday_price'] = I('post.holiday_price','0.00','float');
        if($data['holiday_price']<=0){
            $this->error("销售价格必须大于0");
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
        
        
        $data['holiday_intro'] = I('post.holiday_intro');
        if(!$data['holiday_intro']){
            $this->error("节日介绍必须填写");
        }
        $data['holiday_intro'] = htmlspecialchars($data['holiday_intro']);
        
//         $cid_id = $_POST['cid_id'];
//         if(count($cid_id)<1){
//             $this->error("最少选择一个分类");
//         }
        
        $CommonModel = D('Common');
        $CommonModel->startTrans();
        $data['holiday_updated_at'] = time();
        $data['shop_id'] = $shop_id;
        $data['holiday_created_at'] = time();
        $holiday_id = M('holiday')->data($data)->add();
        if ($holiday_id) {
            //更新目的地对应
            $res = $CommonModel->destination_join($destination_id, $holiday_id, $this->type);
            if(!$res['status']){
                $CommonModel->rollback();
                $this->error($res['msg']);
            }
            //更新图片对应
            $CommonModel->ImgJoin($holiday_id, $this->type, $Img);
//             //更新分类对应
//             $CommonModel->setCidJoin($holiday_id, $this->type, $cid_id);
            
            //增加节日对应的路线
            $route_id = $_POST['route_id'];
            M('holiday_join')->where(array("holiday_id"=>$holiday_id))->delete();
            if($route_id){
                $add_route_data = array();
                foreach($route_id as $route){
                    $add_route = array();
                    $add_route['holiday_id'] = $holiday_id;
                    $add_route['route_id'] = $route;
                    $add_route_data[] = $add_route;
                }
                M('holiday_join')->addAll($add_route_data);
            }
            
            addlog('新增节日，holiday_id：' . $holiday_id.":".json_encode($_POST));
        
            $CommonModel->commit();
            if($data['holiday_status']){
                $action=1;
            
                $searchUpdate = D("search");
                $searchUpdate->delType($holiday_id,$this->type,$action);
            }
            $this->success('恭喜！节日添加成功！');
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
        $data['holiday_id'] = isset($_POST['holiday_id']) ? intval($_POST['holiday_id']) : 0;
        
        if(!$data['holiday_id']){
            $this->error("商品不存在");
        }
        //读取商品库，看是否有权限修改
        $attrInfo = M('holiday')->where(array("holiday_id"=>$data['holiday_id']))->find();
        if(!$attrInfo){
            $this->error("商品不存在");
        }
        //是商家，如果商品不等于
        if($attrInfo['shop_id']!=$shop_id){
            $this->error("系统错误，请刷新再试");
        }
        //开启上下架
        if(I("get.act")=='status'){
            if(!$data['holiday_id']){
                $this->error("系统错误");
            }
            $data['holiday_status'] = intval(I('holiday_status'))?1:0;
            $data['holiday_updated_at'] = time();
            $res = M('holiday')->data($data)->where('holiday_id=' . $data['holiday_id'])->save();
            addlog('节日，holiday_id：' . $data['holiday_id'].":".intval(I('holiday_status'))?"上架":"下架");
            if($res){
                if($data['holiday_status']){
                    $action = 1;
                }else{
                    $action = 3;
                }
            
                $searchUpdate = D("search");
                $searchUpdate->delType($data['holiday_id'],$this->type,$action);
            }
            $this->success("修改状态成功");
        }
        
        
        $data['holiday_name'] = isset($_POST['holiday_name']) ? $_POST['holiday_name'] : false;//节日名称
        if(!$data['holiday_name'] || mb_strlen($data['holiday_name'])>20){
            $this->error("节日名称不能为空，且不能超过20个字");
        }
        
        $data['holiday_open_time'] = isset($_POST['holiday_open_time']) ? $_POST['holiday_open_time'] : '';//开放时间
        
        if(!$data['holiday_open_time']){
            $this->error("开放时间不能为空");
        }
        
        $data['holiday_phone'] = I('post.holiday_phone', '', 'strip_tags');//联系电话
        if(!$data['holiday_phone']){
            $this->error("电话号码不能为空");
        }
        
        $data['holiday_status'] = I('post.holiday_status', '0', 'intval');//状态
        
        $data['holiday_address'] = I('post.holiday_address', '', 'strip_tags');//详细地址
        $data['holiday_lon'] = I('post.holiday_lon');//经度
        $data['holiday_lat'] = I('post.holiday_lat');//维度
        
        if(!$data['holiday_address'] || !$data['holiday_lon'] || !$data['holiday_lat']){
            $this->error("请使用定位功能，获取位置");
        }
        
        $geoHashModel = new GeoHashModel();
        $data['holiday_geohash'] = $geoHashModel->encode($data['holiday_lat'], $data['holiday_lon']);
        
        
        $data['holiday_is_refund'] = I('post.holiday_is_refund', '0', 'intval');//是否可以退款
        
        $data['holiday_suggest'] = I('post.holiday_suggest');//建议游玩时长
        $data['holiday_price'] = I('post.holiday_price','0.00','float');
        if($data['holiday_price']<=0){
            $this->error("销售价格必须大于0");
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
        
        
        $data['holiday_intro'] = I('post.holiday_intro');
        if(!$data['holiday_intro']){
            $this->error("节日介绍必须填写");
        }
        $data['holiday_intro'] = htmlspecialchars($data['holiday_intro']);
        
        $cid_id = $_POST['cid_id'];
        if(count($cid_id)<1){
            $this->error("最少选择一个分类");
        }
        
        $CommonModel = D('Common');
        $CommonModel->startTrans();
        $data['holiday_updated_at'] = time();
        
        M('holiday')->data($data)->where('holiday_id=' . $data['holiday_id'])->save();
        //更新目的地对应
        $res = $CommonModel->destination_join($destination_id, $data['holiday_id'], $this->type);
        if(!$res['status']){
            $CommonModel->rollback();
            $this->error($res['msg']);
        }
        //更新图片对应
        $CommonModel->ImgJoin($data['holiday_id'], $this->type, $Img);
        //更新分类对应
        $CommonModel->setCidJoin($data['holiday_id'], $this->type, $cid_id);
        
        //增加节日对应的路线
        $route_id = $_POST['route_id'];
        M('holiday_join')->where(array("holiday_id"=>$data['holiday_id']))->delete();
        if($route_id){
            $add_route_data = array();
            foreach($route_id as $route){
                $add_route = array();
                $add_route['holiday_id'] = $data['holiday_id'];
                $add_route['route_id'] = $route;
                $add_route_data[] = $add_route;
            }
            M('holiday_join')->addAll($add_route_data);
        }
        
        addlog('编辑节日，holiday_id：' . $data['holiday_id'].':'.json_encode($_POST));
        $CommonModel->commit();
        if($data['holiday_status']){
            $action = 2;
        }else{
            $action = 3;
        }
        
        $searchUpdate = D("search");
        $searchUpdate->delType($data['holiday_id'],$this->type,$action);
        $this->success('恭喜！节日编辑成功！');
    }
    
    //管理员更新
    public function update($act = null)
    {
        if($this->Group_id!=1){
            $this->error("请刷新重试");
        }
        
        $data = array();
        $data['holiday_id'] = isset($_POST['holiday_id']) ? intval($_POST['holiday_id']) : 0;
        
        if(!$data['holiday_id']){
            $this->error("商品不存在");
        }
        //读取商品库，看是否有权限修改
        $attrInfo = M('holiday')->where(array("holiday_id"=>$data['holiday_id']))->find();
        if(!$attrInfo){
            $this->error("商品不存在");
        }
        
        //开启上下架
        if(I("get.act")=='status'){
            if(!$data['holiday_id']){
                $this->error("系统错误");
            }
            $data['holiday_status'] = intval(I('holiday_status'))?1:0;
            $data['holiday_updated_at'] = time();
            $res = M('holiday')->data($data)->where('holiday_id=' . $data['holiday_id'])->save();
            addlog('节日，holiday_id：' . $data['holiday_id'].":".intval(I('holiday_status'))?"上架":"下架");
            
            if($res){
                if($data['holiday_status']){
                    $action = 1;
                }else{
                    $action = 3;
                }
            
                $searchUpdate = D("search");
                $searchUpdate->delType($data['holiday_id'],$this->type,$action);
            }
            $this->success("修改状态成功");
        }
        
        
        $data['holiday_name'] = isset($_POST['holiday_name']) ? $_POST['holiday_name'] : false;//节日名称
        if(!$data['holiday_name'] || mb_strlen($data['holiday_name'])>20){
            $this->error("节日名称不能为空，且不能超过20个字");
        }
        
        $data['holiday_open_time'] = isset($_POST['holiday_open_time']) ? $_POST['holiday_open_time'] : '';//开放时间
        
        if(!$data['holiday_open_time']){
            $this->error("开放时间不能为空");
        }
        
        $data['holiday_phone'] = I('post.holiday_phone', '', 'strip_tags');//联系电话
        if(!$data['holiday_phone']){
            $this->error("电话号码不能为空");
        }
        
        $data['holiday_status'] = I('post.holiday_status', '0', 'intval');//状态
        
        $data['holiday_address'] = I('post.holiday_address', '', 'strip_tags');//详细地址
        $data['holiday_lon'] = I('post.holiday_lon');//经度
        $data['holiday_lat'] = I('post.holiday_lat');//维度
        
        if(!$data['holiday_address'] || !$data['holiday_lon'] || !$data['holiday_lat']){
            $this->error("请使用定位功能，获取位置");
        }
        
        $geoHashModel = new GeoHashModel();
        $data['holiday_geohash'] = $geoHashModel->encode($data['holiday_lat'], $data['holiday_lon']);
        
        
        
        
        $data['holiday_suggest'] = I('post.holiday_suggest');//建议游玩时长
        
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
        
        
        $data['holiday_intro'] = I('post.holiday_intro');
        if(!$data['holiday_intro']){
            $this->error("节日介绍必须填写");
        }
        $data['holiday_intro'] = htmlspecialchars($data['holiday_intro']);
        
        $cid_id = $_POST['cid_id'];
        if(count($cid_id)<1){
            $this->error("最少选择一个分类");
        }
        
        $CommonModel = D('Common');
        $CommonModel->startTrans();
        $data['holiday_updated_at'] = time();
        
        M('holiday')->data($data)->where('holiday_id=' . $data['holiday_id'])->save();
        //更新目的地对应
        $res = $CommonModel->destination_join($destination_id, $data['holiday_id'], $this->type);
        if(!$res['status']){
            $CommonModel->rollback();
            $this->error($res['msg']);
        }
        //更新图片对应
        $CommonModel->ImgJoin($data['holiday_id'], $this->type, $Img);
        //更新分类对应
        $CommonModel->setCidJoin($data['holiday_id'], $this->type, $cid_id);
        
        
        //增加节日对应的路线
        $route_id = $_POST['route_id'];
        M('holiday_join')->where(array("holiday_id"=>$data['holiday_id']))->delete();
        if($route_id){
            $add_route_data = array();
            foreach($route_id as $route){
                $add_route = array();
                $add_route['holiday_id'] = $data['holiday_id'];
                $add_route['route_id'] = $route;
                $add_route_data[] = $add_route;
            }
            M('holiday_join')->addAll($add_route_data);
        }
        
        addlog('编辑节日，holiday_id：' . $data['holiday_id'].':'.json_encode($_POST));
        $CommonModel->commit();
        if($data['holiday_status']){
            $action = 2;
        }else{
            $action = 3;
        }
        
        $searchUpdate = D("search");
        $searchUpdate->delType($data['holiday_id'],$this->type,$action);
        $this->success('恭喜！节日编辑成功！');
    }
    
    /**
     * 评论管理
     * @param unknown $hotel_id
     * @param number $p
     */
    public function replay($holiday_id,$p=1){
        $p = intval($p) > 0 ? $p : 1;
    
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
    
        $aid = intval($holiday_id);
        $article = M('holiday')->where('holiday_id=' . $aid)->find();
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
                $res = M('holiday')->where(array("holiday_id"=>$hall_id))->setDec("holiday_score_num");
                
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
    
    public function getList(){
        $keyword = I('post.search_keyword');
        $orderBy = "route_created_at desc";
        if($keyword){
            $where = "route_name like '%{$keyword}%' and route_status=1";
        }else{
            $where = "route_status=1";
        }
        $where .= " and user_id=0";
//         if($getHash){
//             $where .= " and route_geohash like '{$getHash}%'";
//         }
        
        $model = M('route');
        $list = $model->where($where)->select();
        $this->success($list);
    }
    
    
    /**
     * 报名用户
     * @param unknown $holiday_id
     * @param number $p
     */
    public function playUser($holiday_id,$p=1){
        
        
        $shop_id = session("shop_id");
        
        if($this->Group_id==2 && !$shop_id){
            $this->error("请求错误，请刷新后试一试");
        }
        
        
        $p = intval($p) > 0 ? $p : 1;
        $article = M('holiday');
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $prefix = C('DB_PREFIX');
        
        $holidayInfo = $article->where(array("holiday_id"=>$holiday_id))->find();
        $this->assign("holidayInfo",$holidayInfo);
        
        if(!$shop_id){
            $where = array(
                //"o.shop_id"=>$shop_id,
                "o.join_id"=>$holiday_id,
                "o.order_type"=>$this->type,
                "o.order_status"=>20
            );
        }else{
            $where = array(
                "o.shop_id"=>$shop_id,
                "o.join_id"=>$holiday_id,
                "o.order_type"=>$this->type,
                "o.order_status"=>20
            );
        }
        $orderby = "o.order_created_at asc";
        
        $count = M("order o")->where($where)->count();
        $list = M("order o")->where($where)->join("left join ".$prefix."user u on o.user_id=u.user_id")
        ->field("o.*,u.user_nickname,u.mobile")->order($orderby)->limit($offset . ',' . $pagesize)->select();
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }
    
}
