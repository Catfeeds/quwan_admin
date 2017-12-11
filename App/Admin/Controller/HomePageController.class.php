<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：后台首页控制器。
 *
 **/

namespace Admin\Controller;

class HomePageController extends ComController
{
    
    private $page_name = array(
        "1"=>"广告",
        "2"=>"路线",  //3条
        "3"=>"目的地", //3条
        "4"=>"景点", //3条
        "5"=>"节日", //3条
        "6"=>"推荐周边",
        "7"=>"景点分类" //6个分类
    );
    
    public function index()
    {
        $list = M('home_page')->order("home_page_sort asc")->select();
        if(!$list){
            for($i=1;$i<=7;$i++){
                $info = array();
                $info['home_page_id'] = $i;
                $info['home_page_status'] = 0;
                $info['home_page_type'] = $i;
                $info['home_page_sort'] = $i;
                $info['list'] = array();
//                 页面类型，1广告，2路线，3目的地，4景点，5节日，6推荐周边
                $info['home_page_name'] = $this->page_name[$i];
//                 if($i==1){
//                     $info['home_page_name'] = "广告";
//                 }elseif($i==2){
//                     $info['home_page_name'] = "路线";
//                 }elseif($i==3){
//                     $info['home_page_name'] = "目的地";
//                 }elseif($i==4){
//                     $info['home_page_name'] = "景点";
//                 }elseif($i==5){
//                     $info['home_page_name'] = "节日";
//                 }else{
//                     $info['home_page_name'] = "推荐周边";
//                 }
                $list[] = $info;
            }
        }else{
            $common = D("common");
            foreach($list as &$info){
                if($info['home_page_type']==1 || $info['home_page_type']==6){
                    $info['list'] = array();
                }elseif($info['home_page_type']==2){//路线
                    $info['list'] = M("home_page_value v")->join("left join ".C("DB_PREFIX")."route r on r.route_id=v.value_id")->field("r.*,v.home_page_value_id")->where("v.home_page_id=".$info['home_page_id'])->order("sort asc")->select();
                }elseif($info['home_page_type']==3){//目的地
                    $info['list'] = M("home_page_value v")->join("left join ".C("DB_PREFIX")."destination r on r.destination_id=v.value_id")->field("r.*,v.home_page_value_id")->where("v.home_page_id=".$info['home_page_id'])->order("sort asc")->select();
                }elseif($info['home_page_type']==4){//景点
                    $info['list'] = M("home_page_value v")->join("left join ".C("DB_PREFIX")."attractions r on r.attractions_id=v.value_id")->field("r.*,v.home_page_value_id")->where("v.home_page_id=".$info['home_page_id'])->order("sort asc")->select();
                    if($info['list']){
                        foreach($info['list'] as &$v){
                            $v['imgInfo'] = $common->getImgJoinOne($v['attractions_id'],1);
                        }
                    }
                    
                }elseif($info['home_page_type']==5){//节日
                    $info['list'] = M("home_page_value v")->join("left join ".C("DB_PREFIX")."holiday r on r.holiday_id=v.value_id")->field("r.*,v.home_page_value_id")->where("v.home_page_id=".$info['home_page_id'])->order("sort asc")->select();
                    if($info['list']){
                        foreach($info['list'] as &$v){
                            $v['imgInfo'] = $common->getImgJoinOne($v['holiday_id'],4);
                        }
                    }
                }elseif($info['home_page_type']==7){//景点分类
                    $info['list'] = M("home_page_value v")->join("left join ".C("DB_PREFIX")."cid r on r.cid_id=v.value_id")->field("r.*,v.home_page_value_id")->where("v.home_page_id=".$info['home_page_id']." and r.cid_type=1")->order("sort asc")->select();
                }
                $info["label_num"] = count($info['list']);
            }
        }
//         echo json_encode($list);
//         die;
        $this->assign("list",$list);
        $this->display();
    }
    
    public function upHomePage(){
        $post = $_POST;
        
        $home_page_ids = $post['home_page_id'];
        $i=1;
        $dataAll = array();
        foreach($home_page_ids as $key=> $home_page_id){
            $data = array();
            $data['home_page_name'] = $this->page_name[$home_page_id];
            $data['home_page_id'] = $home_page_id;
            $data['home_page_type'] = $home_page_id;
            $data['home_page_sort'] = $key;
            $data['home_page_status'] = 0;
            if(isset($post['home_page_status_'.$home_page_id]) && $post['home_page_status_'.$home_page_id]){
                $data['home_page_status'] = 1;
            }
            
            $info = M("home_page")->where(array("home_page_id"=>$home_page_id))-count();
            if($info){
                M("home_page")->where(array("home_page_id"=>$home_page_id))->save($data);
            }else{
                M("home_page")->add($data);
            }
        }
        
        echo json_encode($post);
    }
    
    public function getList($p=1){
        $keyword = I('post.search_keyword');
        $page_id = I('post.page_id','0','intval');
        
    
        //路线
        if($page_id==2){
            $orderBy = "route_created_at desc";
            if($keyword){
                $where = "route_name like '%{$keyword}%' and route_status=1";
            }else{
                $where = "route_status=1";
            }
    
            
    
            $model = M('route');
        }elseif($page_id==3){//目的地
            $orderBy = "destination_created_at desc";
            if($keyword){
                $where = "destination_name like '%{$keyword}%' and destination_status=1";
            }else{
                $where = "destination_status=1";
            }
            $model = M('destination');
        }elseif($page_id==4){//景点
            $orderBy = "attractions_created_at desc";
            if($keyword){
                $where = "attractions_name like '%{$keyword}%' and attractions_status=1";
            }else{
                $where = "attractions_status=1";
            }
            $model = M('attractions');
        }elseif($page_id==5){//节日
            $orderBy = "holiday_created_at desc";
            if($keyword){
                $where = "holiday_name like '%{$keyword}%' and holiday_status=1";
            }else{
                $where = "holiday_status=1";
            }
            $model = M('holiday');
        }elseif($page_id==7){//节日
            $orderBy = "cid_id desc";
            if($keyword){
                $where = "cid_name like '%{$keyword}%' and cid_status=1 and cid_type=1";
            }else{
                $where = "cid_status=1 and cid_type=1";
            }
            $model = M('cid');
        }
        $pageSize = 20;
        $offset = ($p-1)*$pageSize;
    
        $total = $model->where($where)->count();
        if($offset>$total){
            $list = array();
        }else{
            //->limit($offset.",".$pageSize)
            $list = $model->where($where)->select();
            if($list && ($page_id==4 || $page_id==5)){
                $common = D("common");
                foreach($list as &$info){
                    if($page_id==4){
                        $imgInfo = "";
                        $img = $common->getImgJoinOne($info['attractions_id'],1);
                        if($img){
                            $imgInfo = getQiniuImgUrl($img);
                        }
                        $info['imgInfo'] = $imgInfo;
                    }elseif($page_id==5){
                        $imgInfo = "";
                        $img = $common->getImgJoinOne($info['holiday_id'],4);
                        if($img){
                            $imgInfo = getQiniuImgUrl($img);
                        }
                        $info['imgInfo'] = $imgInfo;
                    }
                }
            }
        }
        $this->success($list);
    }
    
    public function update(){
        $post = $_POST;
        
        $home_page_ids = $post['home_page_id'];
        $i=1;
        $dataAll = array();
        foreach($home_page_ids as $key=> $home_page_id){
            $data = array();
            $data['home_page_name'] = $this->page_name[$home_page_id];
            $data['home_page_id'] = $home_page_id;
            $data['home_page_type'] = $home_page_id;
            $data['home_page_sort'] = $key;
            $data['home_page_status'] = 0;
            if(isset($post['home_page_status_'.$home_page_id]) && $post['home_page_status_'.$home_page_id]){
                $data['home_page_status'] = 1;
            }
            
            $info = M("home_page")->where(array("home_page_id"=>$home_page_id))-count();
            if($info){
                M("home_page")->where(array("home_page_id"=>$home_page_id))->save($data);
            }else{
                M("home_page")->add($data);
            }
            $num = 0;
            if($home_page_id==2 || $home_page_id==3 || $home_page_id==4 || $home_page_id==5){
                $num = 3;
            }elseif($home_page_id==7){
                $num = 6;
            }
            
            if($num){
                if(count($post["page_name_value_".$home_page_id])>$num){
                    $this->error("不能超过规定项哦");
                }
            }
            
            M("home_page_value")->where(array("home_page_id"=>$home_page_id))->delete();
            if($num){
                $i = 1;
                $list = $post["page_name_value_".$home_page_id];
                foreach ($list as $page_value){
                    $addJoin = array();
                    $addJoin['home_page_id'] = $home_page_id;
                    $addJoin['value_id'] = $page_value;
                    $addJoin['sort'] = $i;
                    M("home_page_value")->add($addJoin);
                    $i++;
                }
            }
        }
        $this->success("保存成功");
    }
    
}