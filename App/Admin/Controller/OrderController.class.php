<?php
/**
 *
 * 版权所有：波波<www.bobolucy.com>
 * 作    者：波波<273719650@qq.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：订单控制器。
 *
 **/

namespace Admin\Controller;

use Admin\Model\CommonModel;

class OrderController extends ComController
{
    
    private $type=1;

    public function index($p=1)
    {
        
        $shop_id = session("shop_id");
        
        if($this->Group_id==2 && !$shop_id){
            $this->error("请求错误，请刷新后试一试");
        }
        
        
        $p = intval($p) > 0 ? $p : 1;
        
        $article = M('order o');
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $prefix = C('DB_PREFIX');
        $keyword = isset($_GET['keyword']) ? htmlentities($_GET['keyword']) : '';
        
        
        if($shop_id){
            $where = 'o.shop_id='.$shop_id;
        }else{
            $where = "1=1";
        }
        
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
        
        if($status==10){
            $where .= ' and o.order_status=10';
        }elseif($status==0){
            $where .= ' and o.order_status=0';
        }elseif($status==20){
            $where .= ' and o.order_status=20';
        }elseif($status==30){
            $where .= ' and o.order_status=30';
        }elseif($status==40){
            $where .= ' and o.order_status=40';
        }else{
            $where .= ' and o.order_status>=0';
        }
        
        //默认按照时间降序
        $orderby = 'o.order_updated_at desc';
        
        
        if($keyword){
            $where .= " and (o.order_sn like '%{$keyword}%' or u.user_nickname like '%{$keyword}%' or u.user_mobile like '%{$keyword}%')";
        }
        
        
        $count = $article->where($where)->count();
        $list = $article->where($where)
        ->join("left join ".$prefix."user u on u.user_id=o.user_id left join ".$prefix."shop s on o.shop_id=s.shop_id")
        ->field("o.*,u.user_nickname,u.user_mobile,s.shop_name")->order($orderby)->limit($offset . ',' . $pagesize)->select();
        
        if($list){
            $commonModel = D('Common');
            foreach($list as &$info){
                $replay_status = 0;
                $replay_msg = "";
                $info['info'] = $commonModel->getIdInfo($info['join_id'],$info['order_type']);
                if($info['order_type']==1){
                    $info['product_name'] = $info['info']['attractions_name'];
                }elseif($info['order_type']==4){
                    $info['product_name'] = $info['info']['holiday_name'];
                }
                $order_status_msg = '';
                if($info['order_status']==10){
                    $order_status_msg .='<font color="red">未付款</font>';
                    $order_status_msg .='<br>下单时间:'.date("Y-m-d H:i；s",$info['order_created_at']);
                }elseif($info['order_status']==20){
                    $order_status_msg .='<font color="red">已付款</font>';
                    $order_status_msg .='<br>下单时间:'.date("Y-m-d H:i；s",$info['order_created_at']);
                }elseif($info['order_status']==30){
                    $order_status_msg .='<font color="red">待评价</font>';
                    $order_status_msg .='<br>下单时间:'.date("Y-m-d H:i；s",$info['order_created_at']);
                    $order_status_msg .='<br>核销时间:'.date("Y-m-d H:i；s",$info['order_check_at']);
                }elseif($info['order_status']==40){
                    $order_status_msg .='<font color="red">已完成</font>';
                    $order_status_msg .='<br>下单时间:'.date("Y-m-d H:i；s",$info['order_created_at']);
                    $order_status_msg .='<br>核销时间:'.date("Y-m-d H:i；s",$info['order_check_at']);
                    
                    $replay_info = M('source')->where(array("order_id"=>$info['order_id']))->find();
                    if($replay_info){
                        
                        $replay_msg = "评分:".$replay_info['score'];
                        $replay_msg .= "<br>".$replay_info['score_comment'];
                        
                        $info['replayInfo'] = $replay_info;
                        if($replay_info['score_replay_status']==1){
                            $replay_status = 2;
                            $replay_msg .= "<br><font color='red'>[回复]:".$replay_info['score_replay_content']."</font>";
                        }else{
                            $replay_status = 1;
                        }
                    }
                }elseif($info['order_status']==0){
                    if($info['order_cancel_type']==3){
                        $order_status_msg .='<font color="red">退款中</font>';
                    }else{
                        $order_status_msg .='<font color="red">已取消</font>';
                    }
                    $order_status_msg .='<br>下单时间:'.date("Y-m-d H:i；s",$info['order_created_at']);
                    $order_status_msg .='<br>取消时间:'.date("Y-m-d H:i；s",$info['order_cancel_at']);
                }
                $info['replay_status'] = $replay_status;
                $info['msg'] = $order_status_msg;
                $info['replay_msg'] = $replay_msg;
            }
        }
        
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
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
