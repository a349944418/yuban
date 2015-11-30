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
       
        $res['token'] = $this->create_unique($uid);
        $this->redis->SETEX('Token:uid'.$uid, 2592000, $res['token']);

        $tmp['headimg'] = json_decode($this->redis->HGET('Userinfo:uid'.$uid, 'headimg'), true);
        $tmp['headimg'] = $tmp['headimg'][0]['url'];
        $return = array('uid'=>$uid, 'token'=>$res['token'], 'voipaccount'=>$res['voipaccount'], 'voippwd'=>$res['voippwd'], 'subaccountid'=>$res['subaccountid'], 'subtoken'=>$res['subtoken'], 'uname'=>$res['uname'], 'mobile'=>$res['mobile'], 'sex'=>$res['sex'],'headimg'=>$tmp['headimg']);
        

        $this->redis->SADD('Userinfo:online', $uid);    //在线用户列表
        if($res['sex'] == 1){
            $this->redis->SADD('Userinfo:sex', $uid);  //男性用户列表 
        }       
        $this->redis->SADD('Userinfo:country'.$res['country'], $uid);   //用户国籍列表

        unset($res);
        $this->return['data'] = $return;
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
        $pwd = I('post.pwd');
        $repwd = I('post.repwd');
        if($pwd != $repwd || $pwd == '')
        {
            $this->return['code'] = 1002;
            $this->return['message'] = L('pwd_error');
            $this->goJson($this->return);
        }
        
        if(!$this->mid) {
            $mobile = I('post.mobile');
        
            if(strlen($mobile) != 11) {
                $this->return['code'] = 1001;
                $this->return['message'] = L('mobile_error');
                $this->goJson($this->return);
            }

            $uid = D('userinfo')->getUid($mobile);
        } else {
            $uid = $this->mid;
        }
        
        $login_salt = $this->redis->HGET('Userinfo:uid'.$uid, 'login_salt');
        $info['password'] = md5(md5($pwd).$login_salt);
        D('userinfo')->where('uid='.$uid)->save($info);
        $this->redis->HSET('Userinfo:uid'.$uid, 'password', $info['password']);
        $this->goJson($this->return);
    }

    /**
     * 退出登录
     * @return [type] [description]
     */
    public function logout()
    {
        $this->redis->SREM('Userinfo:online', $this->mid);
        $this->redis->DEL('Token:uid'.$this->mid);
        $this->goJson($this->return);
    }
}
?>