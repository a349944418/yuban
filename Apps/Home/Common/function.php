<?php
/**
 * 查看用户是否登录
 * @param  {[type]}  $uid   [description]
 * @param  {[type]}  $token [description]
 * @param  {[type]}  $redis [description]
 * @return {Boolean}        [description]
 */
function is_login($uid, $token, $redis) {
	$server_token = $redis->GET('Token:uid'.$uid);
    dump($server_token);
    if(!$server_token) {
    	$return['error'] = 1;
        $return['code'] = 1001;
        $return['message'] = L('token_lose');
    }elseif($server_token != $token) {
    	$return['error'] = 1;
        $return['code'] = 1002;
        $return['message'] = L('token_error');
    }
    return $return;
}

?>