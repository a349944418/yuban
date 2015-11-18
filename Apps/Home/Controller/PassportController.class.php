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
            $zflag = 1;
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
        unset($res['pwd']);
        $res['token'] = $this->create_unique($res['uid']);
        $this->redis->SETEX('Token:uid'.$res['uid'], 2592000, $res['token']);
        //用户头像,视频，音频介绍    
        $audio = D('file')->field('savepath, savename')->where('id='.$res['audio_profile'])->find();
        $res['audio_profile_src'] = '/Uploads/File/'.$audio['savepath'].$audio['savename'];
        $video = D('file')->field('savepath, savename')->where('id='.$res['video_profile'])->find();
        $res['vedio_profile_src'] = '/Uploads/File/'.$video['savepath'].$video['savename'];
        $res['headimg_src'] = D('picture')->where('id='.$res['headimg'])->getField('path');
        $res['photo'] = D('userinfo')->where('uid='.$uid)->getField('photo');
        $photo_arr = explode(',', $res['photo']);
        foreach($photo_arr as $v) {
            $photo_res = array();
            $photo_res['pid'] = $v;
            $photo_res['path'] = D('picture')->where('id='.$v)->getField('path');
            $photo[] = $photo_res;
        }
        $res['photo'] = $photo;
        $this->redis->HSET('Userinfo:uid'.$uid, 'photo', json_encode($photo, JSON_UNESCAPED_UNICODE));
        //用户语言
        $res['language'] = D('userLanguage')->field('lid, type, self_level, sys_level')->where('uid='.$res['uid'])->select();
        $allLanguage = D('language')->getAllLanguage();
        
        foreach($res['language'] as $k=>$v) {
            $res['language'][$k]['lname'] = $allLanguage[ $v['lid'] ];
        }
        $this->redis->HSET('Userinfo:uid'.$uid, 'language', json_encode($res['language'], JSON_UNESCAPED_UNICODE));

        //用户标签
        $usertags = D('userTags')->field('tid')->where('uid='.$res['uid'])->select();
        if($usertags){
            $allTags = D('tags')->getAllTags();
            foreach($usertags as $k=>$v){
                $usertags[$k]['tname'] = $allTags[$v['tid']];
            }
        } else {
            $usertags = array();
        }
        $this->redis->HSET('Userinfo:uid'.$uid, 'tags', json_encode($usertags, JSON_UNESCAPED_UNICODE));
        $res['tags'] = $usertags;
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
        $mobile = I('post.mobile');
        
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
        $info['password'] = md5(md5($pwd).$login_salt);
        D('userinfo')->where('uid='.$uid)->save($info);
        $this->redis->HSET('Userinfo:uid'.$uid, 'password', $info['password']);
        $this->goJson($this->return);
    }
}
?>