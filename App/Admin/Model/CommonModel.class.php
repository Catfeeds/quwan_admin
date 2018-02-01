<?php
namespace Admin\Model;
use Think\Model;
/**
* 文件用途描述
* @date: 2017年11月23日 上午9:29:47
* @author: bobo
* @qq:273719650
* @email:273719650@qq.com
* @version:
* ==============================================
* 版权所有 2017-2017 http://www.bobolucy.com
* ==============================================
*/
class CommonModel extends Model{
    
    Protected $autoCheckFields = false;
    
    /**
     * 目的地关联
     * @param unknown $destination_id 目的地id
     * @param unknown $join_id  关联id
     * @param unknown $type 1景点,2目的地，3路线,4节日，5酒店,6餐厅,7图片
     * @param number $sort 排序
     * @return mixed|boolean|unknown|string
     */
    public function destination_join($destination_id,$join_id,$type,$sort=1){
        $return = array(
            "status"=>0,
            "msg"=>"",
            "id"=>0
        );
        $infodestination_status = M('destination')->where(array('destination_id'=>$destination_id))->find();
        
        if(!$infodestination_status){
            $return['msg']="目的地不存在";
            return $return;
        }elseif($infodestination_status['destination_status']!=1){
            $return['msg']="目的地被禁用";
            return $return;
        }
        
        $info = M('destination_join')->where(array('join_id'=>$join_id,"destination_join_type"=>$type))->find();
        if($info){
            if($info['destination_join_sort']==$sort && $info['destination_id']==$destination_id){
                $return['status']=1;
                $return['id'] = $info['destination_join_id'];
                return $return;
            }else{
                $data = array(
                    "destination_join_sort"=>$sort,
                    "destination_id"=>$destination_id
                );
                
                M('destination_join')->where(array('destination_join_id'=>$info['destination_join_id']))->save($data);
                $return['status']=1;
                $return['id'] = $info['destination_join_id'];
                return $return;
            }
        }else{
            $data = array(
                'join_id'=>$join_id,
                "destination_join_type"=>$type,
                "destination_join_sort"=>$sort,
                "destination_id"=>$destination_id,
            );
            $res = M('destination_join')->add($data);
            if($res){
                $return['status']=1;
                $return['id'] = $res;
            }else{
                $return['msg']="入库失败";
            }
            return $return;
        }
    }
    
    /**
     * 删除目的关联
     * @param unknown $join_id
     * @param unknown $type
     */
    public function destination_join_del($join_id,$type){
        M('destination_join')->where(array('join_id'=>$join_id,"destination_join_type"=>$type))->delete();
        return true;
    }
    
    /**
     * 获取目的地
     * @param unknown $join_id
     * @param unknown $type
     * @return mixed|NULL|unknown|string[]|unknown[]|object
     */
    public function getDestination_join($join_id,$type){
        $res = M('destination_join')->where(array('join_id'=>$join_id,"destination_join_type"=>$type))->getField('destination_id');
        return $res;
    }
    
    
    /**
     * 新增图片
     * @param unknown $join_id
     * @param unknown $type
     * @param unknown $data
     * @return boolean
     */
    public function ImgJoin($join_id,$type,$data){
        $up = array(
            "img_status"=>0
        );
        M('img')->where(array("join_id"=>$join_id,"img_type"=>$type))->save($up);
        if($data){
            $AllData = array();
            $i = 1;
            foreach ($data as $val){
                $d = array();
                $d['join_id'] = $join_id;
                $d['img_type'] = $type;
                $d['img_url'] = $val;
                
                $info = M('img')->where($d)->find();
                $d['img_status'] = 1;
                $d['img_created_at'] = time();
                $d['img_updated_at'] = time();
                $d['img_sort'] = $i;
                $i++;
                if($info){
                    M('img')->where(array("img_id"=>$info['img_id']))->save($d);
                }else{
                    M('img')->add($d);
                }
                
                
//                 $AllData[] = $d;
            }
            
//             M('img')->addAll($AllData,array('join_id','img_type','img_url'),true);
//             echo M('img')->getLastSql();
//             die;
        }
        return true;
    }
    
    /**
     * 获取图片
     * @param unknown $join_id
     * @param unknown $type
     * @return string
     */
    public function getImgJoin($join_id,$type){
        $list=M('img')->where(array("join_id"=>$join_id,"img_type"=>$type,"img_status"=>1))->getField('img_url',true);
//         print_R($list);

        if(count($list)==1 && $list[0] == ''){
            return array();
        }
        return $list;//implode('|', $list);
    }
    /**
     * 获取图片
     * @param unknown $join_id
     * @param unknown $type
     * @return string
     */
    public function getImgJoinOne($join_id,$type){
        $list=M('img')->where(array("join_id"=>$join_id,"img_type"=>$type,"img_status"=>1))->order("img_sort asc")->getField('img_url');
        //         print_R($list);
        return $list;
    }
    
    /**
     * 删除指定的id和type的关联数据
     * @param unknown $join_id
     * @param unknown $type
     */
    public function delId($join_id,$type){
        M('img')->where("join_id={$join_id} and img_type=".$type)->delete();
        M('destination_join')->where("join_id={$join_id} and destination_join_type=".$type)->delete();
        M('cid_map')->where("join_id={$join_id} and cid_map_type=".$type)->delete();
        return true;
    }
    
    /**
     * 分类对应的  id
     * @param unknown $join_id
     * @param unknown $type
     * @param unknown $data
     * @return boolean
     */
    public function setCidJoin($join_id,$type,$data){
        M('cid_map')->where(array("join_id"=>$join_id,"cid_map_type"=>$type))->delete();
        if($data){
            $addAll = array();
            $i = 1;
            foreach ($data as $val){
                $d = array();
                $d['join_id'] = $join_id;
                $d['cid_map_type'] = $type;
                $d['cid_map_sort'] = $i;
                $d['cid_id'] = $val;
                $i++;
                $addAll[] = $d;
                M('cid_map')->add($d);
            }
//             M('cid_map')->addAll($addAll);
        }
        return true;
    }
    
    /**
     * 获取值对应的分类
     * @param unknown $join_id
     * @param unknown $type
     * @return mixed|NULL|unknown|string[]|unknown[]|object
     */
    public function getCidJoin($join_id,$type){
        return M('cid_map')->where(array("join_id"=>$join_id,"cid_map_type"=>$type))->getField('cid_id',true);
    }
    
    /**
     * 根据type和id返回响应的内容
     * @param unknown $join_id
     * @param unknown $type
     * @return mixed|boolean|NULL|string|unknown|object
     */
    public function getIdInfo($join_id,$type){
        if($type==1){
            return M('attractions')->where(array("attractions_id"=>$join_id))->find();
        }elseif($type==5){
            return M('hotel')->where(array("hotel_id"=>$join_id))->find();
        }elseif($type==6){
            return M('hall')->where(array("hall_id"=>$join_id))->find();
        }elseif($type==2){
            return M('destination')->where(array("destination_id"=>$join_id))->find();
        }elseif($type==3){
            return M('route')->where(array("route_id"=>$join_id))->find();
        }elseif($type==4){
            return M('holiday')->where(array("holiday_id"=>$join_id))->find();
        }else{
            return array();
        }
    }
    
}