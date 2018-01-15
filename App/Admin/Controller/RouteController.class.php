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

use Admin\Model\CommonModel;
use Think\Model;
use Admin\Model\GeoHashModel;

class RouteController extends ComController
{
    
    private $type=3;

    public function index()
    {
        $p = intval($p) > 0 ? $p : 1;
        
        $article = M('route');
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $prefix = C('DB_PREFIX');
        $keyword = isset($_GET['keyword']) ? htmlentities($_GET['keyword']) : '';
        $where = 'user_id = 0 ';
        
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        
        if($status==1){
            $where .= ' and route_status=1';
        }elseif($status==2){
            $where .= ' and route_status=0';
        }else{
            $where .= ' and route_status>=0';
        }
        
        //默认按照时间降序
        $orderby = "route_updated_at desc";
        
        
        if($keyword){
            $where .= " and route_name like '%{$keyword}%'";
        }
        
        
        
        $count = $article->where($where)->count();
        $list = $article->where($where)->order($orderby)->limit($offset . ',' . $pagesize)->select();
        
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function del()
    {
        $id = isset($_GET['route_id']) ? intval($_GET['route_id']) : false;
        if ($id) {
            $category = M('route');
            $res=$category->where('route_id=' . $id)->save(array("route_status"=>-1));
            if($res){
                $action = 3;
                
                $searchUpdate = D("search");
                $searchUpdate->delType($id,$this->type,$action);
            }
                addlog('删除路线，ID：' . $id);
            $this->success("删除路线成功");
        } else {
            $this->error("删除路线失败");
        }

    }

    public function edit()
    {
        $route_id = intval($_GET['route_id']);
        $route_info = M('route')->where(array("route_id"=>$route_id))->find();
        if(!$route_info){
            redirect(U('add'));
        }
        $this->assign("info",$route_info);
        $commonModel = D('Common');
        //获取路线对应的分类信息
        $list = $commonModel->getCidJoin($route_id,$this->type);
        $infoCid = array();
        if($list){
            foreach($list as $val){
                $infoCid[$val] = 1;
            }
        }
        $this->assign("infoCid",$infoCid);
        $day_list = M('route_day')->where(array("route_id"=>$route_id))->order("route_day_sort asc")->select();
        if($day_list){
            foreach($day_list as &$day){
                $list = M('route_day_join')->where(array("route_id"=>$route_id,"route_day_id"=>$day['route_day_id']))->order("route_day_join_sort asc")->select();
                foreach($list as &$type_info){
                    $type_info['info'] = $commonModel->getIdInfo($type_info['join_id'],$type_info['route_day_join_type']);
                    if($type_info['route_day_join_type']==1){
                        $type_info['name'] = "景点";
                        $type_info['name_s'] = $type_info['info']['attractions_name'];
                        $type_info['name_t'] = $type_info['info']['attractions_suggest'];
                        
                    }elseif($type_info['route_day_join_type']==5){
                        $type_info['name'] = "酒店";
                        $type_info['name_s'] = $type_info['info']['hotel_name'];
                        $type_info['name_t'] = $type_info['info']['hotel_score'];
                    }elseif($type_info['route_day_join_type']==6){
                        $type_info['name'] = "餐厅";
                        $type_info['name_s'] = $type_info['info']['hall_name'];
                        $type_info['name_t'] = $type_info['info']['hall_score'];
                    }
                }
                $day['list'] = $list;
            }
        }
        
        $this->assign("day_list",$day_list);
//         print_R($day_list);
//         die;
        $category = M('cid')->where("cid_status=1 and cid_type=".$this->type)->order('cid_sort asc')->select();
        
        $this->assign('category', $category);
        $this->display('form_edit');
    }

    public function add()
    {
        $category = M('cid')->where("cid_status=1 and cid_type=".$this->type)->order('cid_sort asc')->select();
    
        $this->assign('category', $category);
        $infoCid = array();
        $this->assign('infoCid',$infoCid);
        $this->display('form');
    }

    
    
    //管理员更新
    private function check_post()
    {
        
        $tx_msg = "";
        
        $data = array();
        
        //获取路线相关的信息
        $route_name = $_POST['route_name'];
        
        if(!$route_name){
            $this->error("请填写路线名称");
        }
        $route_day_num = count($_POST['route_day']);
        
        
        $route_info = array();
        $route_info['route_name'] = $route_name;
        $route_info['route_day_num'] = $route_day_num;
        $route_info['user_id'] = 0;
        $route_info['route_status'] = intval($_POST['route_status'])?1:0;
        $route_info['route_intro'] = htmlspecialchars($_POST['route_intro']);
        $route_info['route_updated_at'] = time();
//         $route_info['route_intro'] = $route_name;

        $route_info['route_address'] = I('post.route_address', '', 'strip_tags');//详细地址
        $route_info['route_lon'] = I('post.route_lon');//经度
        $route_info['route_lat'] = I('post.route_lat');//维度
        
        if(!$route_info['route_address'] || !$route_info['route_lon'] || !$route_info['route_lat']){
            $this->error("请使用定位功能，获取位置");
        }
        
        $geoHashModel = new GeoHashModel();
        $route_info['route_geohash'] = $geoHashModel->encode($route_info['route_lat'], $route_info['route_lon']);
        
        
        
        //赋值到返回信息中
        $data['route_info'] = $route_info;
        $route_day_desc = $_POST['route_name_day'];
        if($route_day_num<1){
            $this->error("请创建行程");
        }
        
        $i = 1;
        //每日行程
        $route_date_data = array();
        
        foreach($_POST['route_day'] as $keys=> $day_num){
            $type = $_POST["route_day_{$day_num}_type"];
            $value = $_POST["route_day_{$day_num}_value"];
            
            $route_day_info = array();
            $route_day_info['route_day_intro'] = $route_day_desc[$keys];
            $route_day_info['route_day_sort'] = $i;
            
            if(count($type)<1){
                $this->error("第{$i}日，您没有添加任何行程哦");
            }
            $attr_status=0;
            $hotel_status=0;
            $list = array();
            //每日行程中的小点
            foreach($type as $key=>$type_info){
                $type_join = 0;
                if($type_info==1){
                    $type_join = 1;
                    $attr_status++;
                }else{
                    if($type_info==2){
                        $type_join = 5;
                    }else{
                        $type_join = 6;
                    }
                    $hotel_status++;
                }
                $join_info = array();
                $join_info['join_id'] = $value[$key];
                $join_info['route_day_join_type'] = $type_info;
                $join_info['route_day_join_sort'] = $key;
                $list[] = $join_info;
            }
            $route_day_info['list'] = $list;
            
            if($attr_status==1){
                $tx_msg .= "第{$i}日，只有一个景点哦;";
            }if($attr_status==0){
                $tx_msg .= "第{$i}日，没有景点哦";
            }
            
            if($hotel_status==0){
                $tx_msg .= "第{$i}日，没有餐厅或酒店哦";
            }
            $route_date_data[] = $route_day_info;
            $i++;
        }
        $data['route_day'] = $route_date_data;
        $data['cid_id'] = $_POST['cid_id'];
        $data['tx_msg'] = $tx_msg;
        return $data;
    }
    
    /**
     * 先检查，如果有可以完善的，就给出需要完善的信息
     */
    public function check_attr(){
        $data = $this->check_post();
        $this->success($data['tx_msg']);
    }
    
    /**
     * 新增_保存路线
     */
    public function update(){
        
        $data = array();
        if(I("get.act")=='status'){
            $route_id = intval(I('route_id'));
            if(!$route_id){
                $this->error("系统错误");
            }
            $data['route_status'] = intval(I('route_status'))?1:0;
            $data['route_updated_at'] = time();
            $res = M('route')->data($data)->where('route_id=' . $route_id)->save();
            addlog('路线，hall_id：' . $route_id.":".intval(I('route_status'))?"上架":"下架");
            if($res){
                if($data['route_status']==1){
                    $action = 2;
                
                    $searchUpdate = D("search");
                    $searchUpdate->delType($route_id,$this->type,$action);
                }else{
                    $action = 3;
                
                    $searchUpdate = D("search");
                    $searchUpdate->delType($route_id,$this->type,$action);
                }
            }
            $this->success("Ok");
        }
        
        $data = $this->check_post();
        
        
        $route_id = intval($_POST['route_id']);
        
        $model = new Model();
        $model->startTrans();
        
        $commonModel = D('Common');
        
        
        if($route_id){
            $route_info = M('route')->where(array("route_id"=>$route_id))->find();
            if($route_info){
                $res = M('route')->where(array("route_id"=>$route_id))->save($data['route_info']);
                if(!$res){
                    $model->rollback();
                    $this->error("保存失败1");
                }
            }else{
                $route_id = 0;
            }
        }
        
        if(!$route_id){
            $data['route_info']['route_created_at'] = $data['route_info']['route_updated_at'];
            $route_id = M('route')->add($data['route_info']);
            if(!$route_id){
                $model->rollback();
                $this->error("入库失败1");
            }
            
        }
        //如果是保存的话，需要把之前对应的日和行程，以及分类删除
        $commonModel->delId($route_id,$this->type);
        M('route_day')->where(array("route_id"=>$route_id))->delete();
        M('route_day_join')->where(array("route_id"=>$route_id))->delete();
        
        //入库分类对应
        if($data['cid_id']){
            
            $commonModel->setCidJoin($route_id,$this->type,$data['cid_id']);
        }
        
        $i=1;
        
        foreach($data['route_day'] as $day_info){
            $list = $day_info['list'];
            $day_add = $day_info;
            unset($day_add['list']);
            $day_add['route_id'] = $route_id;
            $route_day_id = M('route_day')->add($day_add);
            if($route_day_id){
                $ii=1;
                foreach($list as $join){
                    $join['route_id'] = $route_id;
                    $join['route_day_id'] = $route_day_id;
                    $res = M('route_day_join')->add($join);
                    if(!$res){
                        $model->rollback();
                        $this->error("入库失败（第{$i}日，第{$ii}项）");
                    }
                    
                    $ii++;
                }
            }else{
                $model->rollback();
                $this->error("入库失败（第{$i}日）");
            }
            
            $i++;
        }
        $model->commit();
        addlog('编辑路线，ID：' . $route_id.json_encode($_POST));
        if($data['route_info']['route_status']==1){
            $action = 2;
            
            $searchUpdate = D("search");
            $searchUpdate->delType($route_id,$this->type,$action);
        }else{
            $action = 3;
            
            $searchUpdate = D("search");
            $searchUpdate->delType($route_id,$this->type,$action);
        }
        $this->success("添加成功",U('index'));
    }
    
    public function getList($p=1){
        $pageSize = 20;
        $type = I('post.search_type');
        $keyword = I('post.search_keyword');
        $id = I('post.id','0','intval');
        $getHash = '';
        if($id){
            $getHash = M('attractions')->where("attractions_id=".$id)->getField('attractions_geohash');
            if($getHash){
                $getHash = substr($getHash, 0,3);
            }
        }
        
        if(!$getHash && !$keyword){
//             $this->error("搜索请传入关键词");
        }
        
        //景点
        if($type==1){
            $orderBy = "attractions_created_at desc";
            if($keyword){
                $where = "attractions_name like '%{$keyword}%' and attractions_status=1";
            }else{
                $where = "attractions_status=1";
            }
            
            if($getHash){
                $where .= " and attractions_geohash like '{$getHash}%'";
            }
            
            $model = M('attractions');
        }elseif($type==5){//酒店
            if($keyword){
                $where = "hotel_name like '%{$keyword}%' and hotel_status=1";
            }else{
                $where = "hotel_status=1";
            }
            
            if($getHash){
                $where .= " and hotel_geohash like '{$getHash}%'";
            }
            $model = M('hotel');
            $orderBy = "hotel_created_at desc";
        }else{//餐厅
            if($keyword){
                $where = "hall_name like '%{$keyword}%' and hall_status=1";
            }else{
                $where = "hall_status=1";
            }
            
            if($getHash){
                $where .= " and hall_geohash like '{$getHash}%'";
            }
            
            $model = M('hall');
            $orderBy = "hall_created_at desc";
        }
        
        $offset = ($p-1)*$pageSize;
        
        $total = $model->where($where)->count();
        if($offset>$total){
            $list = array();
        }else{
            //->limit($offset.",".$pageSize)
            $list = $model->where($where)->select();
        }
        $this->success($list);
    }
}
