<?php
namespace Home\Controller;

Class PassportController extends BaseController
{
	/**
	 * 登录
	 * @return {[type]} [description]
	 */
	public function login()
	{
		$mobile = I('post.mobile');
		$mobile = intval( I('post.mobile') );
        if(strlen($mobile) != 11) {
            $this->return['code'] = 1001;
            $this->return['message'] = L('mobile_error');
            $this->goJson($this->return);
        }

        $res = D('userinfo')->where('mobile="'.$mobile.'"')->find();
        if(!$res) {
        	$this->return['code'] = 1002;
        	$this->return['message'] = L('no_register');
        	$this->goJson($this->return);
        }
	}
}
?>