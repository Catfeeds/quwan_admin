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
class ReplayModel extends Model{
    
    Protected $autoCheckFields = false;
    
    /**
     * 分页获取评论列表
     * @param unknown $joinId 对应的id值
     * @param unknown $type 类型
     * @param unknown $offset 开始
     * @param unknown $limit 每页数量
     * @return number[]|NULL[]|unknown[]
     */
    public function getReplayList($joinId,$type,$offset,$limit){
        $return = array(
            "total"=>0,
            "list"=>array()
        );
        $return['total'] = M('score s')->where(array("s.join_id"=>$joinId,"s.score_type"=>$type,"s.score_status"=>1))->count();
        $list = M('score s')->where(array("s.join_id"=>$joinId,"s.score_type"=>$type,"s.score_status"=>1))
        ->join(C('DB_PREFIX')."user u on s.user_id=u.user_id")->field("s.*,u.user_nickname")
        ->limit($offset.",".$limit)->order("s.score_created_at desc")->select();
        
        if($list){
            $Common = new CommonModel();
            foreach($list as &$val){
                $imgList = $Common->getImgJoin($val['score_id'], 8);
                print_R($imgList);
                if(count($imgList)>=1){
                    $val['imgList'] = $Common->getImgJoin($val['score_id'], 8);
                }else{
                    $val['imgList'] = array();
                }
            }
        }
        
        $return['list'] = $list;
        return $return;
    }
    
    /**
     * 回复评论
     * @param unknown $score_id //评论id
     * @param unknown $content //评论内容
     * @return number[]|string[]
     */
    public function doReplay($score_id,$content){
        $return = array(
            "status"=>0,
            "msg"=>""
        );
        $info = M('score')->where(array('score_id'=>$score_id))->find();
        if(!$info || $info['score_status']!=1 || $info['score_replay_status']!=0){
            $return['msg'] = "请刷新后再试一试";
        }else{
            $data = array(
                "score_replay_content"=>$content,
                "score_replay_status"=>1,
            );
            M('score')->where(array('score_id'=>$score_id))->save($data);
            $return['status'] = 1;
        }
        return $return;
    }
}