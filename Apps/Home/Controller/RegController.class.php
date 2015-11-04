<?php
namespace Home\Controller;
use Think\Controller;

Class RegController extends Controller 
{
    /**
     * 获取短信验证码
     * return  [json]  返回代码
     * Auth : zbq  2015.11.04
     **/
    public functon getMobileCode()
    {
        $server_token = md5('yj+' . I('post.timestamp') . 'hash');
	if ($server_token != I('post.token') {
	    $this->return['code'] = 1001;
            echo json_encode($this->return);
	}
    }
}
?>
