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
        "2"=>"路线",
        "3"=>"目的地",
        "4"=>"景点",
        "5"=>"节日",
        "6"=>"推荐周边"
    );
    
    public function index()
    {
        $list = M('home_page')->order("home_page_sort asc")->select();
        if(!$list){
            for($i=1;$i<=6;$i++){
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
            foreach($list as &$info){
                if($info['home_page_type']==1 || $info['home_page_type']==6){
                    $info['list'] = array();
                }elseif($info['home_page_type']==2){//路线
                    $info['list'] = M("home_page_value v")->join("left join ".C("DB_PREFIX")."route r on r.route_id=v.value_id")->field("r.*,v.home_page_value_id")->where("v.home_page_id=".$info['home_page_id'])->order("sort asc")->select();
                }elseif($info['home_page_type']==3){//目的地
                    $info['list'] = M("home_page_value v")->join("left join ".C("DB_PREFIX")."destination r on r.destination_id=v.value_id")->field("r.*,v.home_page_value_id")->where("v.home_page_id=".$info['home_page_id'])->order("sort asc")->select();
                }elseif($info['home_page_type']==4){//景点
                    $info['list'] = M("home_page_value v")->join("left join ".C("DB_PREFIX")."attractions r on r.attractions_id=v.value_id")->field("r.*,v.home_page_value_id")->where("v.home_page_id=".$info['home_page_id'])->order("sort asc")->select();
                }elseif($info['home_page_type']==5){//节日
                    $info['list'] = M("home_page_value v")->join("left join ".C("DB_PREFIX")."holiday r on r.holiday_id=v.value_id")->field("r.*,v.home_page_value_id")->where("v.home_page_id=".$info['home_page_id'])->order("sort asc")->select();
                }
            }
        }
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
            
//             $dataAll[] = $data;
        }
        
        echo json_encode($post);
    }
}