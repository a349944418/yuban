<?php
/**
 * 广场功能 （发现)
 * Auth: zbq
 */
namespace Home\Controller;

Class SquareController extends BaseController 
{
	/**
	 * 附近的人
	 * @return [type] [description]
	 */
	public function nearBy()
	{
		$data['lati'] = I('post.lati');
        $data['longi'] = I('post.longi');
        if(!$data['lati'] || !$data['longi']) {               
            $this->return['code'] = 1003;
            $this->return['message'] = L('latng_error');
        }
        $data['ctime'] = time();
        $data['uid'] = $this->mid;
        D('userPosition')->add($data);
        import("Common.Util.LBS");
        import("Common.Util.Geohash");
        $geohash = new \Geohash();
        $this->lbs = new \LBS($this->redis, $geohash);
        $this->lbs->upinfo($this->mid, $data['lati'], $data['longi'] );
		$re = $this->lbs->serach($data['lati'],$data['longi']);
		$stime = time()-7200;  //两小时过期，时间早于该时刻的都不算
		//取出全部语言
		$allLanguage = D('language')->getAllLanguage();
		$data = array();
		//取出基础信息和算出实际距离
		foreach($re as $key=>$val) {
			$tmp_userinfo = array();
			if($val == $this->mid) {
				continue;
			}
			$zposition = $this->redis->HGETALL('Position:uid'.$val);
			if($zposition['ctime'] < $stime) {
				continue;
			}
		    // $distance = getDistance($lati, $longi, $tmp_userinfo['lati'], $tmp_userinfo['longi']);
		    //基础信息
		    $tmp_userinfo = $this->redis->HGETALL('Userinfo:uid'.$val);
		    $data['uid'] = $val;
		    $data['uname'] = $tmp_userinfo['uname'];
		    $data['sex'] = $tmp_userinfo['sex'];
		    $tmp['headimg'] = json_decode($this->redis->HGET('Userinfo:uid'.$val, 'headimg'), true);
		    $data['headimg_src'] = $tmp['headimg'][0]['url'];
		    $data['lname'] = $allLanguage[$tmp_userinfo['cur_language']];
		    $data['level'] = $tmp_userinfo['level'];
		    $data['intro'] = $tmp_userinfo['intro'];
		    $data['price'] = $tmp_userinfo['price'];
		    $data['location']['lati'] = $zposition['lati'];
		    $data['location']['longi'] = $zposition['longi'];

		    //距离米
		    // $data[$key]['distance'] = $distance;
		    //排序列
		    // $sortdistance[$key] = $distance;
		    $this->return['data']['list'][] = $data;
		}
		//距离排序
		// array_multisort($sortdistance,SORT_ASC,$data);
		
		$this->goJson($this->return);		
	}

	/**
	 * 搜索找人
	 * @return [type] [description]
	 */
	public function search()
	{
		$search = I('post.');
		$field = 'u.uid';
		$data = D('userinfo')->getSearchList($this->mid, $field, $search);
		if($data['ulist']) {
            foreach( $data['ulist'] as $v){
                if($v) {
                    $tmp['uid'] = $v['uid'];
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
					$tmp['sex'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'sex');
                    $data['datalist'][] = $tmp;
                } else {
                    break;
                }
            }
        }
        unset($data['ulist']);

		$this->return['data'] = $data;
		$this->goJson($this->return);
	}

	/**
	 * 排行榜
	 * @return [type] [description]
	 */
	public function charts()
	{
		$data['index'] = I('post.index') ? I('post.index') : 1;
		$data['pageSize'] = I('post.pageSize');
		$start = ($data['index']-1)*$data['pageSize'];
		$data['totalCount'] = D('userinfo')->count('uid');
		$data['totalCount'] = $data['totalCount'] >= 100 ? 100 : $data['totalCount'];
		$limit = $start+$data['pageSize']-1 > 99 ? 99-$start : $data['pageSize'];
		$res = D('userinfo')->field('uid')->order('level desc')->limit($start, $limit)->select();
		if($res) {
			foreach( $res as $v){
				if($v) {
					$tmp['uid'] = $v['uid'];
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
					$tmp['sex'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'sex');
					$data['datalist'][] = $tmp;
				} else {
					break;
				}
			}
		}
		$this->return['data'] = $data;
		$this->goJson($this->return);
	}

	/**
	 * 话题广场
	 * @return [type] [description]
	 */
	public function topic()
	{
		$tags = F('tags');
		if(!$tags) {
            $tags = D('tags')->field('tid, tag_name')->select();
            F('tags', $tags);
		}
		if($this->mid) {
			if(!$this->redis->HLEN('Userinfo:uid'.$this->mid)) {
				A('Home/User')->getUserinfoData($this->mid);
			}
			$topic = json_decode($this->redis->HGET('Userinfo:uid'.$this->mid, 'tags'));
			if(!$topic) {
				$topic = $tags;
			}
		}else{

			$topic = $tags;
			
        }
		$this->return['data']['topic'] = $topic;
		$this->goJson($this->return);
	}

	/**
	 * 获取话题用户
	 * @return [type] [description]
	 */
	public function topicUser()
	{
		$data['tid'] = I('post.tid') ? I('post.tid') : 1;
		$data['index'] = I('post.index') ? I('post.index') : 1;
		$data['pageSize'] = I('post.pageSize');
		$start = ($data['index']-1)*$data['pageSize'];
		$where = $this->mid ? ' and uid!='.$this->mid : '';
		$data['totalCount'] = D('userTags')->where('tid='.$data['tid'].$where)->count('ut_id');
		$res = D('userTags')->field('uid')->where('tid='.$data['tid'].$where)->limit($start, $data['pageSize'])->select();
		if($res) {
			foreach( $res as $v) {
				if($v) {
					$tmp['uid'] = $v['uid'];
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
					$tmp['sex'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'sex');
					$data['datalist'][] = $tmp;
				} else {
					break;
				}
			}
		}
		$this->return['data'] = $data;
		$this->goJson($this->return);
	}

	/**
	 * 语加首页
	 * @return [type] [description]
	 */
	public function yujia()
	{
		$uid = $this->randuid($this->mid);
		$tmp['uid'] = $uid;
		if(!$this->redis->HLEN('Userinfo:uid'.$tmp['uid'])) {
			A('Home/User')->getUserinfoData($tmp['uid']);
		}
		$tmp['uname'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'uname');
		$tmp['price'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'price');
		$location = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'location');
		$location = explode('/', $location);
		$tmp['location'] = $location[0].' '.$location[1];
		$tmp['language'] = json_decode($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'language'), true);
		$tmp['level'] = intval($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'level'));
		$tmp['headimg'] = json_decode($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'headimg'), true);
		$tmp['headimg'] = $tmp['headimg'][0]['url'];
		$tmp['voipaccount'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'voipaccount');
		$tmp['sex'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'sex');
		$this->return['data'] = $tmp;
		$this->goJson($this->return);
	}

	private function randuid($uid)
	{
		$rand = $this->redis->SRANDMEMBER("Userinfo:online");
		if($rand == $uid){
			$rand = $this->randuid($uid);
		}
		return $rand;

	}

	/**
	 * 语加搜索结果
	 * @return [type] [description]
	 */
	public function yujiaSearch()
	{
		$search = I('post.');
		$search['pageSize'] = 20;
		$field = 'u.uid';
		$whereRand = ' and uid>='.$this->randuid($this->mid);
		$data = D('userinfo')->getSearchList($this->mid, $field, $search, $whereRand);
		
		if(!$data['ulist']) {
			$data = D('userinfo')->getSearchList($this->mid, $field, $search);
		}
		if($data['ulist']) {
			foreach( $data['ulist'] as $v){
				if($v) {
					$tmp['uid'] = $v['uid'];
					if(!$this->redis->HLEN('Userinfo:uid'.$tmp['uid'])) {
						A('Home/User')->getUserinfoData($tmp['uid']);
					}
					$tmp['uname'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'uname');
					$tmp['price'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'price');
					$location = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'location');
					$location = explode('/', $location);
					$tmp['location'] = $location[0].' '.$location[1];
					$tmp['language'] = json_decode($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'language'), true);
					$tmp['level'] = intval($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'level'));
					$tmp['headimg'] = json_decode($this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'headimg'), true);
					$tmp['headimg'] = $tmp['headimg'][0]['url'];
					$tmp['voipaccount'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'voipaccount');
					$tmp['sex'] = $this->redis->HGET('Userinfo:uid'.$tmp['uid'], 'sex');
					$data['datalist'][] = $tmp;
				} else {
					break;
				}
			}
			unset($data['ulist']);
		} 
		$this->return['data'] = $data;
		$this->goJson($this->return);
	}
}