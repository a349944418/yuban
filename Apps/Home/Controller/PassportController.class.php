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
        if(md5(md5(I('post.pwd')).$res['login_salt']) != $res['password']) {
        	$this->return['code'] = 1003;
        	$this->return['message'] = L('pwd_error');
        	$this->goJson($this->return);
        }
        $res['token'] = $this->create_unique($res['uid']);
        $res['language'] = D('user_language')->filed('lid, type, self_level, sys_level') -> select();
        $this->return['data'] = $res;
        $this->goJson($this->return);
	}

    /**
     * 生成token
     * @param  [type] $uid [description]
     * @return [type]      [description]
     */
    private function create_unique($uid)
    {
        $data = $_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR'].time().rand().$uid;    
        return sha1($data);  
    }
}
?>