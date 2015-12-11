<?php
/**
 * 用户个人中心 (资料修改及编辑)
 */
namespace Home\Controller;

Class UserController extends BaseController
{
	//用户个人主页
	public function index()
	{
		$uid = I('post.uid');
		$res = $this->getUserinfoData($uid);
		//是否已关注
		if($this->mid != $uid) {
			$friendtype = D('friend')->where('from_id='.$this->mid.' and to_id='.$uid)->getField('type');
			$res['friendflag'] = in_array($friendtype, array(1, 3)) ? 1 : 0;
		} else {
			$res['friendflag'] = 1;
		}
		//瞬间
		$res['shunjian']['totalCount'] = D('shunjian')->where('uid='.$uid)->count();
		$shunjianlist = D('shunjian')->field('url, type, cover')->where('uid='.$uid)->order('rid desc')->limit(6)->select();
		if($shunjianlist) {
			foreach ($shunjianlist as $key => $value) {
				$shunjianlist[$key]['url'] = C('WEBSITE_URL').$value['url'];
				if($value['cover']) {
					$shunjianlist[$key]['cover'] = C('WEBSITE_URL').$value['cover'];
				}
			}
			$res['shunjian']['datalist'] = $shunjianlist; 
		}
		//粉丝
        $res['follow']['totalCount'] = D('friend')->where('to_id='.$uid)->count();
        $followlist = D('friend')->field('from_id')->where('to_id='.$uid)->order('id desc')->limit(10)->select();
        if($followlist) {
        	foreach ($followlist as $v) {
        		$tmp['uid'] = $v['from_id'];
        		if(!$this->redis->HLEN('Userinfo:uid'.$tmp['uid'])) {
					$this->getUserinfoData($tmp['uid']);
				}
				$tmp['headimg'] = json_decode($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'headimg'), true);
				$tmp['headimg'] = $tmp['headimg'][0]['url'];
				$res['follow']['datalist'][] = $tmp;
        	}
        }
		//评论
		$res['comment']['totalCount'] = D('comment')->where('uid='.$uid)->count();
		$commentlist = D('comment')->field('ctime, from_id, content')->where('uid='.$uid)->order('id desc')->limit(10)->select();
		if($commentlist) {
			foreach ($commentlist as $k=>$v) {
				unset($tmp);
				$tmp['uid'] = $v['from_id'];
				$tmp['ctime'] = $v['ctime'];
				$tmp['content'] = $v['content'];
				if(!$this->redis->HLEN('Userinfo:uid'.$tmp['uid'])) {
					$this->getUserinfoData($tmp['uid']);
				}
				$tmp['headimg'] = json_decode($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'headimg'), true);
				$tmp['headimg'] = $tmp['headimg'][0]['url'];
				$tmp['uname'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'uname');
				$location = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'location');
				$location = explode('/', $location);
				$tmp['location'] = $location[0].' '.$location[1];
				$res['comment']['datalist'][] = $tmp;
			}
		}
		$tmp_location = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'location');
		$tmp_location = explode('/', $tmp_location);
		$res['country'] = $tmp_location[0];
		unset($res['voippwd'], $res['subaccountid'], $res['subtoken'], $res['province'], $res['city'], $res['location'], $res['first_letter']);

        $this->return['data'] = $res;
        $this->goJson($this->return);
	}

	/**
	 * 获取瞬间 分页
	 * @return [type] [description]
	 */
	public function shunjian()
	{
		$shunjian['index'] = I('post.index') ? I('post.index') : 1;
		$shunjian['pageSize'] = I('post.pageSize') ? I('post.pageSize') : 6;
		$uid = I('post.uid');
		if(!$uid) {
			$this->return['code'] = 1003;
			$this->return['message'] = L('param_error');
			$this->goJson($this->return);
		}
		$start = ($shunjian['index']-1)*$shunjian['pageSize'];
		$shunjian['totalCount'] = D('shunjian')->where('uid='.$uid)->count();
		$shunjianlist = D('shunjian')->field('url, type, cover')->where('uid='.$uid)->order('rid desc')->limit($start, $shuanjian['pageSize'])->select();
		if($shunjianlist) {
			foreach ($shunjianlist as $key => $value) {
				$shunjianlist[$key]['url'] = C('WEBSITE_URL').$value['url'];
				if($value['cover']) {
					$shunjianlist[$key]['cover'] = C('WEBSITE_URL').$value['cover'];
				}
			}
			$shunjian['datalist'] = $shunjianlist; 
		}
		$this->return['data'] = $shunjian;
		$this->goJson($this->return);
	}

	/**
	 * 获取评论列表 分页
	 * @return [type] [description]
	 */
	public function comment()
	{
		$comment['index'] = I('post.index') ? I('post.index') : 1;
		$comment['pageSize'] = I('post.pageSize') ? I('post.pageSize') : 10;
		$uid = I('post.uid');
		if(!$uid) {
			$this->return['code'] = 1003;
			$this->return['message'] = L('param_error');
			$this->goJson($this->return);
		}
		$start = ($comment['index']-1)*$comment['pageSize'];
		$comment['totalCount'] = D('comment')->where('uid='.$uid)->count();
		$commentlist = D('comment')->field('ctime, from_id, content')->where('uid='.$uid)->order('id desc')->limit($start, $comment['pageSize'])->select();
		if($commentlist) {
			foreach ($commentlist as $k=>$v) {
				unset($tmp);
				$tmp['uid'] = $v['from_id'];
				$tmp['ctime'] = $v['ctime'];
				$tmp['content'] = $v['content'];
				if(!$this->redis->HLEN('Userinfo:uid'.$tmp['uid'])) {
					$this->getUserinfoData($tmp['uid']);
				}
				$tmp['headimg'] = json_decode($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'headimg'), true);
				$tmp['headimg'] = $tmp['headimg'][0]['url'];
				$tmp['uname'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'uname');
				$location = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'location');
				$location = explode('/', $location);
				$tmp['location'] = $location[0].' '.$location[1];
				$comment['datalist'][] = $tmp;
			}
		}
		$this->return['data'] = $comment;
		$this->goJson($this->return);
	}

	/**
	 * 获取粉丝列表 分页
	 * @return [type] [description]
	 */
	public function followlist(){
		$follow['index'] = I('post.index') ? I('post.index') : 1;
		$follow['pageSize'] = I('post.pageSize') ? I('post.pageSize') : 10;
		$uid = I('post.uid');
		if(!$uid) {
			$this->return['code'] = 1003;
			$this->return['message'] = L('param_error');
			$this->goJson($this->return);
		}
		$start = ($follow['index']-1)*$follow['pageSize'];
		$follow['totalCount'] = D('friend')->where('to_id='.$uid)->count();
        $followlist = D('friend')->field('from_id')->where('to_id='.$uid)->order('id desc')->limit(10)->select();
        if($followlist) {
        	foreach ($followlist as $v) {
        		$tmp['uid'] = $v['from_id'];
				if(!$this->redis->HLEN('Userinfo:uid'.$tmp['uid'])) {
					A('Home/User')->getUserinfoData($tmp['uid']);
				}
				$tmp['uname'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'uname');
				$tmp['price'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'price');
				$location = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'location');
				$location = explode('/', $location);
				$tmp['location'] = $location[0].' '.$location[1];
				$tmp['language'] = json_decode($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'language'), true);
				$tmp['tags'] = json_decode($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'tags'), true);
				$tmp['level'] = intval($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'level'));
				$tmp['headimg'] = json_decode($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'headimg'), true);
				$tmp['headimg'] = $tmp['headimg'][0]['url'];
				$tmp['intro'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'intro');
				$follow['datalist'][] = $tmp;
        	}
        }
		$this->return['data'] = $follow;
		$this->goJson($this->return);
	}

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
		$o_headimg_ids = getSubByKey($o_headimg, 'rid');
		$o_headimg_str = $o_headimg_ids ? implode(',', $o_headimg_ids) : '';
		
		if($o_headimg_str != trim($post['headimg'],',') && isset($post['headimg']) ) {
			$info['headimg'] = trim($post['headimg'],',');
			$headimg_arr = explode(',', $info['headimg']);
			foreach($headimg_arr as $v) {
				$photo_res = array();
				$photo_res['rid'] = $v;
				$photo_res['url'] = C('WEBSITE_URL').D('picture')->where('id='.$v)->getField('path');
				$photo[] = $photo_res;
			}
			$this->redis->HSET('Userinfo:uid'.$this->mid, 'headimg', json_encode($photo, JSON_UNESCAPED_UNICODE));

			$new_photo = explode(',', trim($post['headimg'], ',')) ? explode(',', trim($post['headimg'], ',')) : array();
			$o_headimg_ids = $o_headimg_ids ? $o_headimg_ids : array();
			$add_photo = array_diff($new_photo, $o_headimg_ids);
			if($add_photo){
				$sj_log['uid'] = $this->mid;
				$sj_log['type'] = 1;
				foreach($add_photo as $v){
					$sj_log['url'] = D('picture')->where('id='.$v)->getField('path');
					D('shunjian')->add($sj_log);
				}
			}
			
		}
		//昵称
		if ($post['uname'] != $o_info['uname'] && isset($post['uname'])) {
			if($post['uname'] == ''){
				$this->return['code'] == 1004;
				$this->return['message'] == L('uname_null');
				$this->goJson($this->return);
			}
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
		if ($post['sex'] != $o_info['sex'] && isset($post['sex'])) {
			if($post['sex'] != 1 && $post['sex'] != 2) {
				$this->return['code'] == 1003;
				$this->return['message'] == L('sex_error');
				$this->goJson($this->return);
			}
			$info['sex'] = $post['sex'];
			$sex_flag = $post['sex'] == 1 ? 2 : 1;
			$this->redis->sRem('User:sex'.$sex_flag,$this->mid);
			$this->redis->sAdd('User:sex'.$info['sex'],$this->mid);
		}
		// 个性签名
		if ($post['intro'] != $o_info['intro'] && isset($post['intro'])) {
			$info['intro'] = $post['intro'];
		}
		// 视频介绍
		$o_info['video_profile'] = json_decode($o_info['video_profile'], true) ? json_decode($o_info['video_profile'], true) : array();
		if ($post['video_profile'] != $o_info['video_profile']['rid'] && isset($post['video_profile'])) {
			$info['video_profile'] = $post['video_profile'];
			$video = D('file')->field('savepath, savename')->where('id='.$post['video_profile'])->find();
			$video_profile['rid'] = $post['video_profile'];
			$video_profile['url'] =  C('WEBSITE_URL').'/Uploads/File/'.$video['savepath'].$video['savename'];
			$this->redis->HSET('Userinfo:uid'.$this->mid, 'video_profile', json_encode($video_profile, JSON_UNESCAPED_UNICODE));
			$video_profile_data['type'] = 2;
			$video_profile_data['url'] = '/Uploads/File/'.$video['savepath'].$video['savename'];
			$video_profile_data['uid'] = $this->mid;
			D('shunjian')->add($video_profile_data);
			unset($video_profile_data);
		}
		// 音频介绍
		$o_info['audio_profile'] = json_decode($o_info['audio_profile'], true) ? json_decode($o_info['audio_profile'], true) : array();
		if ($post['audio_profile'] != $o_info['audio_profile']['rid'] && $post['audio_profile']) {
			$info['audio_profile'] = $post['audio_profile'];
			$audio = D('file')->field('savepath, savename')->where('id='.$post['audio_profile'])->find();
			$audio_profile['rid'] = $post['audio_profile'];
			$audio_profile['url'] = C('WEBSITE_URL').'/Uploads/File/'.$audio['savepath'].$audio['savename'];
			$this->redis->HSET('Userinfo:uid'.$this->mid, 'audio_profile', json_encode($audio_profile, JSON_UNESCAPED_UNICODE));
		}			
		// 国家,省,城市信息
		if(isset($post['country']) && $post['country'] != 0){
			if ($post['country'] != $o_info['country'] ) {
				$info['country'] = $post['country'];
				$info['province'] = $post['province'];
				$info['city'] = $post['city'];
				$info['location'] = $post['location'];
			} elseif ($post['province'] != $o_info['province'] && isset($post['province'])) {
				$info['province'] = $post['province'];
				$info['city'] = $post['city'];
				$info['location'] = $post['location'];
			} elseif ($post['city'] != $o_info['city'] && isset($post['city'])) {
				$info['city'] = $post['city'];
				$info['location'] = $post['location'];
			}
		}
		

		//语言 更改
		$o_language = json_decode($o_info['language'], true) ? json_decode($o_info['language'], true) : array();
		if($post['language']['lid'] != $o_language['lid'] && isset($post['language']['lid'])) {
			$info['cur_language'] = $language['lid'] = $post['language']['lid'];			
		}
			
		if($post['language']['self_level'] != $o_language['self_level'] && isset($post['language']['self_level']))
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
		if(isset($post['tags'])){
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
		$this->redis->SADD('Userinfo:friend1:'.$this->mid, $uid);
		$this->goJson($this->return);
	}

	/**
	 * 关注用户列表
	 * @return [type] [description]
	 */
	public function friendList()
	{
		$data['index'] = I('post.index') ? I('post.index') : 1;
		$ftype = I('post.type') == 1 ? 1 : 2;  //1为关注的人，2为聊天过的人
		$data['pageSize'] = I('post.pageSize') ? I('post.pageSize') : 10;
		$follow = $this->redis->sMembers('Userinfo:friend'.$ftype.':'.$this->mid);
		rsort($follow);
		if (!count($follow)) {
			$map['from_id'] = array('eq',$this->mid);
			$uArray = array(3);
			$uArray[] = $ftype;
			$map['type'] = array('in', $uArray);
			$res = D('friend')->getFriend($map);
			foreach($res as $v){
				$follow[] = $v['to_id']; 
				$this->redis->SADD('Userinfo:friend'.$ftype.':'.$this->mid, $v['to_id']);
			}
		}
		$start = ($data['index']-1)*$data['pageSize'];
		$end = $data['pageSize']*$data['index'];
		for($start; $start<$end; $start++){
			if (!$follow[$start]) {
				break;
			} else {
				$tmp['uid'] = $follow[$start];
				if(!$this->redis->HLEN('Userinfo:uid'.$tmp['uid'])) {
					$this->getUserinfoData($tmp['uid']);
				}
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
            if($res['sex'] == 1 || $res['sex'] == 2){
	            $this->redis->Sadd('User:sex'.$res['sex'], $uid);
	        }
	        $this->redis->HMSET('Userinfo:uid'.$uid, $res);
        }
        $res['tags'] = json_decode($res['tags'], true);
        $res['language'] = json_decode($res['language'], true);
        $res['headimg'] = json_decode($res['headimg'], true);
        $res['video_profile'] = (object) json_decode($res['video_profile']);
        $res['audio_profile'] = (object) json_decode($res['audio_profile']);
        unset($res['password'], $res['pwd'], $res['search_key'], $res['login_salt'], $res['datecreated'], $res['lati'], $res['longi'], $res['level'], $res['cur_language']);
        return $res;
	}

	/**
	 * 充值取现记录
	 * @return [type] [description]
	 */
	public function mlog()
	{
		//账户余额
		$data = D('umoney')->field('totalmoney, not_tixian')->where('uid='.$this->mid)->find();
		$data['totalmoney'] = $data['totalmoney'] ? $data['totalmoney'] : 0;
		$data['not_tixian'] = $data['not_tixian'] ? $data['not_tixian'] : 0;
		$data['can_use'] = $data['totalmoney']-$data['not_tixian'];
		//取现，充值记录
		$data['type'] = I('post.type') ? I('post.type') : 1;
		$data['index'] = I('post.index') ? I('post.index') : 1;
		$data['pageSize'] = I('post.pageSize') ? I('post.pageSize') : 10;
		$start = ($data['index']-1)*$data['pageSize'];
		$data['totalCount'] = D('mlog')->where('type='.$data['type'].' and uid='.$this->mid )->count('id');
		$res = D('mlog')->field('money, ctime, note')->where('type='.$data['type'].' and uid='.$this->mid )->order('id desc')->limit($start, $data['pageSize'])->select();
		$data['datalist'] = $res;

		$this->return['data'] = $data;
		$this->goJson($this->return);
	}


	/**
	 * 意见反馈内容
	 * @return [type] [description]
	 */
	public function suggest()
	{
		$data['content'] = I('post.content');
		if($data['content'] == '') {
			$this->return['code'] = 1003;
			$this->return['message'] = L('suggest_null');
			$this->goJson($this->return);
		}
		$data['uid'] = $this->mid;
		$data['ctime'] = time();
		D('suggest')->add($data);
		$this->goJson($this->return);
	}
}