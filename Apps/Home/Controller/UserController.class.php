<?php
/**
 * 用户个人中心 (资料修改及编辑)
 */
namespace Home\Controller;

Class UserController extends BaseController
{
	/**
	 * 获取用户数据Api
	 * @return [type] [description]
	 */
	public function getUserinfo()
	{
		$uid = I('post.uid');
		$res = $this->getUserinfoData($uid);
        $this->return['data'] = $res;
        $this->goJson($this->return);
	}

	/**
	 * 保存用户信息
	 * @return [type] [description]
	 */
	public function saveInfo() 
	{
		$post = I('post.');
		$o_info = $this->redis->HGETALL('Userinfo:uid'.$this->mid);

		$info = array();
		//头像
		$o_headimg = json_decode($o_info['headimg'], true) ? json_decode($o_info['headimg'], true) : array();
		$o_headimg_ids = getSubByKey($o_photo, 'rid');
		$o_headimg_str = $o_headimg_ids ? implode(',', $o_headimg_ids) : '';
		if($o_headimg_str != trim($post['headimg'],',')) {
			$info['headimg'] = trim($post['headimg'],',');
			$headimg_arr = explode(',', $info['headimg']);
			foreach($headimg_arr as $v) {
				$photo_res = array();
				$photo_res['rid'] = $v;
				$photo_res['url'] = C('WEBSITE_URL').D('picture')->where('id='.$v)->getField('path');
				$photo[] = $photo_res;
			}
			$this->redis->HSET('Userinfo:uid'.$this->mid, 'headimg', json_encode($photo, JSON_UNESCAPED_UNICODE));
		}
		//昵称
		if ($post['uname'] != $o_info['uname']) {
			$info['uname'] = $post['uname'];
			load("@.user");
	        $info['first_letter'] = getFirstLetter($post['uname']);
	        //如果包含中文将中文翻译成拼音
	        if ( preg_match('/[\x7f-\xff]+/', $post['uname'] ) ){
	            import("Common.Util.PinYin");
	            $pinyinClass = new \PinYin();
	            $pinyin = $pinyinClass->Pinyin( $post['uname'] );
	            //昵称和呢称拼音保存到搜索字段
	            $info['search_key'] = $post['uname'].' '.$pinyin;
	        } else {
	            $info['search_key'] = $post['uname'];
	        }
		}
		// 性别
		if ($post['sex'] != $o_info['sex']) 
			$info['sex'] = $post['sex'];
		// 个性签名
		if ($post['intro'] != $o_info['intro']) 
			$info['intro'] = $post['intro'];
		// 视频介绍
		$o_info['video_profile'] = json_decode($o_info['video_profile'], true) ? json_decode($o_info['video_profile'], true) : array();
		if ($post['video_profile'] != $o_info['video_profile']['rid']) {
			$info['video_profile'] = $post['video_profile'];
			$video = D('file')->field('savepath, savename')->where('id='.$post['video_profile'])->find();
			$video_profile['rid'] = $post['video_profile'];
			$video_profile['url'] =  C('WEBSITE_URL').'/Uploads/File/'.$video['savepath'].$video['savename'];
			$this->redis->HSET('Userinfo:uid'.$this->mid, 'video_profile', json_encode($video_profile, JSON_UNESCAPED_UNICODE));
		}
		// 音频介绍
		$o_info['audio_profile'] = json_decode($o_info['audio_profile'], true) ? json_decode($o_info['audio_profile'], true) : array();
		if ($post['audio_profile'] != $o_info['audio_profile']['rid']) {
			$info['audio_profile'] = $post['audio_profile'];
			$audio = D('file')->field('savepath, savename')->where('id='.$post['audio_profile'])->find();
			$audio_profile['rid'] = $post['audio_profile'];
			$audio_profile['url'] = C('WEBSITE_URL').'/Uploads/File/'.$audio['savepath'].$audio['savename'];
			$this->redis->HSET('Userinfo:uid'.$this->mid, 'audio_profile', json_encode($audio_profile, JSON_UNESCAPED_UNICODE));
		}			
		// 国家,省,城市信息
		if ($post['country'] != $o_info['country']) {
			$info['country'] = $post['country'];
			$info['province'] = $post['province'];
			$info['city'] = $post['city'];
			$info['location'] = $post['location'];
		} elseif ($post['province'] != $o_info['province']) {
			$info['province'] = $post['province'];
			$info['city'] = $post['city'];
			$info['location'] = $post['location'];
		} elseif ($post['city'] != $o_info['city']) {
			$info['city'] = $post['city'];
			$info['location'] = $post['location'];
		}
		//语言 更改
		$o_language = json_decode($o_info['language'], true) ? json_decode($o_info['language'], true) : array();
		if($post['language']['lid'] != $o_language['lid']) {
			$info['cur_language'] = $language['lid'] = $post['language']['lid'];			
		}
			
		if($post['language']['self_level'] != $o_language['self_level'])
			$info['level'] = $language['self_level'] = $post['language']['self_level'];
		
		if(count($language)) {
			D('userLanguage')->where('uid='.$this->mid)->save($language);
			$language = D('userLanguage')->where('uid='.$this->mid)->field('lid, type, sys_level, self_level')->select();
			$allLanguage = D('language')->getAllLanguage();
			foreach($language as $k=>$v){
				$language[$k]['language_name'] = $allLanguage[ $v['lid'] ];
			}
			$this->redis->HSET('Userinfo:uid'.$this->mid, 'language', json_encode($language, JSON_UNESCAPED_UNICODE));
		}

		if(count($info)){
			D('userinfo')->where('uid='.$this->mid)->save($info);
			unset($info['video_profile'], $info['headimg'], $info['audio_profile']);
			foreach($info as $k=>$v) {
				$this->redis->HSET('Userinfo:uid'.$this->mid ,$k ,$v);
			}
		}

		

		//标签
		$o_tags = json_decode($o_info['tags']) ? json_decode($o_info['tags'], true) : array();
		$o_tag_ids = getSubByKey($o_tags, 'tid');
		$tags_post = explode(',', $post['tags']);
		if(!count($tags_post)){
			D('userTags')->where('uid='.$this->mid)->delete();
		} else {
			$tags['uid'] = $this->mid;
			//有新增标签
			$add_tags = array_diff($tags_post, $o_tag_ids);
			if(count($add_tags)) {
				foreach($add_tags as $v) {
					$tags['tid'] = $v;
					D('userTags')->add($tags);
				}
			}
			//有删除标签
			$delete_tags = array_diff($o_tag_ids, $tags_post);
			if(count($delete_tags)) {
				foreach($delete_tags as $v) {
					D('userTags')->where('uid='.$this->mid.' and tid='.$v)->delete();
				}
			}
			//如果有变化，取出该用户全部标签进行缓存
			if(count($delete_tags) || count($add_tags)) {
				$allTags = D('tags')->getAllTags();
				$tags_res = D('userTags')->field('tid')->where('uid='.$this->mid)->select();
				foreach($tags_res as $k=>$val) {
					$tags_res[$k]['tag_name'] = $allTags[ $val['tid'] ];
				}
				$this->redis->HSET('Userinfo:uid'.$this->mid, 'tags', json_encode($tags_res, JSON_UNESCAPED_UNICODE));
			}
		}
		//$this->return['data'] = $this->getUserinfoData($this->mid);
		$this->goJson($this->return);
	}

	/**
	 * 加关注
	 * @return [type] [description]
	 */
	public function friend()
	{
		$uid = I('post.uid');
		if($this->mid == $uid) {
			$this->return['code'] = 1003;
			$this->goJson['message'] = L('follow_me');
			$this->goJson($this->return);
		}
		D('friend')->addUser($this->mid, $uid, 1);
		$this->redis->SADD('Userinfo:friend'.$this->mid, $uid);
		$this->goJson($this->return);
	}

	/**
	 * 关注用户列表
	 * @return [type] [description]
	 */
	public function friendList()
	{
		$data['index'] = I('post.index') ? I('post.index') : 1;
		$data['pageSize'] = I('post.pageSize') ? I('post.pageSize') : 10;
		$follow = $this->redis->sMembers('Userinfo:friend'.$this->mid);
		rsort($follow);
		if (!count($follow)) {
			$res = D('friend')->field('to_id')->where('from_id='.$this->mid)->order('id desc')->select();
			foreach($res as $v){
				$follow[] = $v['to_id']; 
				$this->redis->SADD('Userinfo:friend'.$this->mid, $v['to_id']);
			}
		}
		$start = ($data['index']-1)*$data['pageSize'];
		$end = $data['pageSize']*$data['index'];
		for($start; $start<$end; $start++){
			if (!$follow[$start]) {
				break;
			} else {
				$tmp['uid'] = $follow[$start];
				$tmp['uname'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'uname');
				$tmp['price'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'price');
				$location = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'location');
				$location = explode('/', $location);
				$tmp['location'] = $location[0].' '.$location[1];
				$tmp['language'] = json_decode($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'language'), true);
				$tmp['tags'] = json_decode($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'tags'), true);
				$tmp['level'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'level');
				$tmp['headimg'] = json_decode($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'headimg'), true);
				$tmp['headimg'] = $tmp['headimg'][0]['url'];
				$tmp['intro'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'intro');
				$data['datalist'][] = $tmp;
			}
		}
		$data['totalCount'] = count($follow);
		$this->return['data'] = $data;
		$this->goJson($this->return);
	}

	/**
	 * 获取用户数据(不含password)
	 * @param  [int] $uid [description]
	 * @return [array]     [description]
	 */
	public function getUserinfoData($uid)
	{
		$res = $this->redis->HGETALL('Userinfo:uid'.$uid);
        if(!$res) {
            $res = D('userinfo')->getUserInfo($uid);
            $this->redis->HMSET('Userinfo:uid'.$uid, $res);
        }
        $res['tags'] = json_decode($res['tags'], true);
        $res['language'] = json_decode($res['language'], true);
        $res['headimg'] = json_decode($res['headimg'], true);
        $res['video_profile'] = json_decode($res['video_profile'], true);
        $res['audio_profile'] = json_decode($res['audio_profile'], true);
        unset($res['password'], $res['pwd'], $res['search_key'], $res['login_salt'], $res['datecreated'], $res['lati'], $res['longi'], $res['level'], $res['cur_language']);
        return $res;
	}
}