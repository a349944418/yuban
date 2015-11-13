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

        $uid = D('userinfo')->getUid($mobile);
        if(!$uid) {
            $this->return['code'] = 1002;
            $this->return['message'] = L('no_register');
            $this->goJson($this->return);
        }

        $res = $this->redis->HGETALL('Userinfo:uid'.$uid);
        if(!$res) {
            $res = D('userinfo')->getUserInfo($uid);
            $this->redis->HMSET('Userinfo:uid'.$uid, $res);
        }

        if(md5(md5(I('post.pwd')).$res['login_salt']) != $res['password']) {
        	$this->return['code'] = 1003;
        	$this->return['message'] = L('login_error');
        	$this->goJson($this->return);
        }
        
        unset($res['password']);
        unset($res['search_key']);
        unset($res['login_salt']);
        $res['token'] = $this->create_unique($res['uid']);
        $this->redis->SETEX('Token:uid'.$res['uid'], 2592000, $res['token']);
        $res['language'] = D('userLanguage')->field('lid, type, self_level, sys_level')->where('uid='.$res['uid'])->select();
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

    /**
     * 修改密码
     * @return [type] [description]
     */
    public function changePwd()
    {
        $mobile = I('post.mobile');
        $mobile = intval( I('post.mobile') );
        
        if(strlen($mobile) != 11) {
            $this->return['code'] = 1001;
            $this->return['message'] = L('mobile_error');
            $this->goJson($this->return);
        }

        $pwd = I('post.pwd');
        $repwd = I('post.repwd');
        if($pwd != $repwd || $pwd == '')
        {
            $this->return['code'] = 1002;
            $this->return['message'] = L('pwd_error');
            $this->goJson($this->return);
        }

        $uid = D('userinfo')->getUid($mobile);
        $login_salt = $this->redis->HGET('Userinfo:uid'.$uid, 'login_salt');
        $info['pwd'] = md5(md5($pwd).$login_salt);
        D('userinfo')->where('uid='.$uid)->save($info);
        $this->redis->HSET('Userinfo:uid'.$uid, 'pwd', $info['pwd']);
        $this->goJson($this->return);
    }
}
?>