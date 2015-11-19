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
		$uid = I('post.to_uid');
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
		$o_info = $this->redis->HGETALL('Userinfo:uid'.$post['uid']);

		$info = array();
		//头像
		if ($post['headimg'] != $o_info['headimg']) {
			$info['headimg'] = $post['headimg'];
			$info['headimg_src'] = D('picture')->where('id='.$post['headimg'])->getField('path');
		}
		//其它相册
		$o_photo = json_decode($o_info['photo'], true) ? json_decode($o_info['photo'], true) : array();
		$o_photo_ids = getSubByKey($o_photo, 'pid');
		$o_photo_str = $o_photo_ids ? implode(',', $o_photo_ids) : '';
		if($o_photo_str != $post['photo']) {
			$info['photo'] = $post['photo'];
			$photo_arr = explode(',', $post['photo']);
			foreach($photo_arr as $v) {
				$photo_res = array();
				$photo_res['pid'] = $v;
				$photo_res['path'] = D('picture')->where('id='.$v)->getField('path');
				$photo[] = $photo_res;
			}
			$this->redis->HSET('Userinfo:uid'.$post['uid'], 'photo', json_encode($photo, JSON_UNESCAPED_UNICODE));
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
		if ($post['video_profile'] != $o_info['video_profile']) {
			$info['video_profile'] = $post['video_profile'];
			$video = D('file')->field('savepath, savename')->where('id='.$post['video_profile'])->find();
			$info['video_profile_src'] = '/Uploads/File/'.$video['savepath'].$video['savename'];
		}
		// 音频介绍
		if ($post['audio_profile'] != $o_info['audio_profile']) {
			$info['audio_profile'] = $post['audio_profile'];
			$audio = D('file')->field('savepath, savename')->where('id='.$post['audio_profile'])->find();
			$info['audio_profile_src'] = '/Uploads/File/'.$audio['savepath'].$audio['savename'];
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

		if(count($info)){
			D('userinfo')->where('uid='.$post['uid'])->save($info);
			unset($info['photo']);
			foreach($info as $k=>$v) {
				$this->redis->HSET('Userinfo:uid'.$post['uid'] ,$k ,$v);
			}
		}

		//语言 更改
		$o_language = json_decode($o_info['language'], true) ? json_decode($o_info['language'], true) : array();
		if($post['language']['lid'] != $o_language['lid'])
			$language['lid'] = $post['language']['lid'];
		if($post['language']['self_level'] != $o_language['self_level'])
			$language['self_level'] = $post['language']['self_level'];
		
		if(count($language)) {
			D('userLanguage')->where('uid='.$post['uid'])->save($language);
			$allLanguage = D('language')->getAllLanguage();
			$language['lname'] = $allLanguage[ $language['lid'] ];
			$this->redis->HSET('Userinfo:uid'.$post['uid'], 'language', json_encode($language, JSON_UNESCAPED_UNICODE));
		}

		//标签
		$o_tags = json_decode($o_info['tags']) ? json_decode($o_info['tags'], true) : array();
		$o_tag_ids = getSubByKey($o_tags, 'tid');
		$tags_post = explode(',', $post['tags']);
		if(!count($tags_post)){
			D('userTags')->where('uid='.$post['uid'])->delete();
		} else {
			$tags['uid'] = $post['uid'];
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
					D('userTags')->where('uid='.$post['uid'].' and tid='.$v)->delete();
				}
			}
			//如果有变化，取出该用户全部标签进行缓存
			if(count($delete_tags) || count($add_tags)) {
				$allTags = D('tags')->getAllTags();
				$tags_res = D('userTags')->field('tid')->where('uid')->select();
				foreach($tags_res as $k=>$val) {
					$tags_res[$k]['tname'] = $allTags[ $val['tid'] ];
				}
				$this->redis->HSET('Userinfo:uid'.$post['uid'], 'tags', json_encode($tags_res, JSON_UNESCAPED_UNICODE));
			}
		}
		$this->return['data'] = $this->getUserinfoData($post['uid']);
		$this->goJson($this->return);
	}

	/**
	 * 获取用户数据(不含password)
	 * @param  [int] $uid [description]
	 * @return [array]     [description]
	 */
	private function getUserinfoData($uid)
	{
		$res = $this->redis->HGETALL('Userinfo:uid'.$uid);
        if(!$res) {
            $res = D('userinfo')->getUserInfo($uid);
            $this->redis->HMSET('Userinfo:uid'.$uid, $res);
        }
        $res['tags'] = json_decode($res['tags'], true);
        $res['language'] = json_decode($res['language'], true);
        $res['photo'] = json_decode($res['photo'], true);
        unset($res['password'], $res['pwd'], $res['search_key'], $res['login_salt']);
        return $res;
	}
}