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
class SendmsgModel extends Model{
    
    Protected $autoCheckFields = false;
    
    
    private $url = 'https://restapi.qu666.cn/quwan/get_access_token';
    
    private function getAccessToken(){
        $res = curl_request($this->url, '', '');
        return $res;
    }
    
    public function sendMsg($userInfo,$orderInfo,$time){
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=";
        $access = $this->getAccessToken();
        if(!$access){
            wirteFileLog("获取access_token失败",'access_token');
            return false;
        }
        $url .= $access;
        
        $data = array();
        $data['touser'] = $userInfo['openid'];
        $data['template_id'] = C('hs_order_tpl');
        $data['form_id'] = $orderInfo['prepay_id'];
        $data['page'] = "pages/orderDetail/orderDetail?orderId=".$orderInfo['order_id'];
        $data['data'] = array(
            "keyword1"=>array(
                "value"=>$orderInfo['order_sn'],
            ),
            "keyword2"=>array(
                "value"=>"您的订单已核销",
            ),
            "keyword3"=>array(
                "value"=>$orderInfo['order_amount'],
            ),
            "keyword4"=>array(
                "value"=>$time,
            ),
        );
        
        $post_data = json_encode($data, true);
        //将数组编码为 JSON
        
        $return = send_post( $url, $post_data);
        wirteFileLog($return,'sendMsg_tpl');
        $res = json_decode($return);
        return $res;
    }
    
    
    private function send_post( $url, $post_data ) {
        $options = array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-type:application/json',
                //header 需要设置为 JSON
                'content' => $post_data,
                'timeout' => 60
                //超时时间
            )
        );
    
        $context = stream_context_create( $options );
        $result = file_get_contents( $url, false, $context );
    
        return $result;
    }
}