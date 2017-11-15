<?php


//用户行为记录调用
function log_page_view($page_name='',$page_value=''){
	$Model = M('page_view');
	
	session_start();
    $member_id = session('member_id');
	$member_id = intval($member_id);
	if(!$member_id){
		$member_id = 0;
	}
	
	$data = array();
	$data['member_id'] 		= $member_id;
	$data['page_name'] 		= $page_name;
	$data['page_value'] 	= $page_value;
	$data['add_time'] 		= date("Y-m-d H:i:s");
	$data['add_time_int'] 	= time();
	$data['ip'] 			= get_client_ip();
    $data['agent'] 			= $_SERVER['HTTP_USER_AGENT'];
    $data['url'] 			= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];;
    $Model->data($data)->add();
}

?>