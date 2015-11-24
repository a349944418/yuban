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
		$lati = I('post.lati');
		$longi = I('post.longi');
		$re = $this->lbs->serach($lati,$longi);
		//算出实际距离
		foreach($re as $key=>$val) {
			$tmp_userinfo = array();
			if($val == $this->mid) {
				continue;
			}
			$tmp_userinfo = $this->redis->HGETALL('Userinfo:uid'.$val);
		    $distance = getDistance($lati, $longi, $tmp_userinfo['lati'], $tmp_userinfo['longi']);
		    //基础信息
		    $data[$key]['uid'] = $val;
		    $data[$key]['uname'] = $tmp_userinfo['uname'];
		    $data[$key]['sex'] = $tmp_userinfo['sex'];
		    $data[$key]['headimg_src'] = $tmp_userinfo['headimg_src'];
		    $tmp_language = json_decode($tmp_userinfo['language'], true);
		    $data[$key]['lname'] = $tmp_language[0]['lname'];
		    $data[$key]['level'] = $tmp_language[0]['sys_level'] ? $tmp_language[0]['sys_level'] : $tmp_language[0]['self_level'];
		    $data[$key]['price'] = $tmp_language[0]['price'];
 
		    //距离米
		    $data[$key]['distance'] = $distance;
		    //排序列
		    $sortdistance[$key] = $distance;
		}
		//距离排序
		array_multisort($sortdistance,SORT_ASC,$data);

		$this->return['data']['list'] = $data;
		$this->goJson($this->return);		
	}

	/**
	 * 搜索找人
	 * @return [type] [description]
	 */
	public function search()
	{
		$where = '';
		//性别筛选
		if (I('post.sex')) {
			$where .= ' sex='.I('post.sex').' and';
		}
		//等级筛选
		if (I('post.min_level')) {
			$where .= ' level >='.I('post.min_level').' and';
		} 
		if (I('post.max_level')) {
			$where .= ' level <='.I('post.max_level').' and';
		}

		//地区筛选
		if (I('post.city')) {
			$where .= ' city = '.I('post.city').' and';
		} elseif (I('post.province')) {
			$where .= ' province ='.I('post.province').' and';
		} elseif (I('post.country')) {
			$where .= ' country ='.I('post.country').' and';
		}

		//语言筛选
		if (I('post.lid')) {
			$where .= ' cur_language='.I('post.lid').' and';
		}

		//时长筛选
		if (I('post.time')) {
			$where .= ' spoken_long >='.I('post.time').' and';
		}

		//付费筛选
		if (I('post.price')) {
			$where .= ' spoken_long >= 0.1 and';
		}

		//音频视频筛选
		if (I('post.intro') == 1) {
			$where .= ' audio_profile != 0 and';
		} else {
			$where .= ' video_profile != 0 and';
		}

		$where = rtrim($where, 'and');

		$data['index'] = I('post.index') ? I('post.index') : 1;
		$data['pageSize'] = I('post.pageSize');
		$start = ($data['index']-1)*$data['pageSize'];
        $data['totalCount'] = D('userinfo')->where($where)->count('uid');
		$res = D('userinfo')->field('uid')->where($where)->limit($start, $data['pageSize'])->select();
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
					$data['datalist'][] = $tmp;
				} else {
					break;
				}
			}
		}
		$this->return['data'] = $data;
		$this->goJson($this->return);
	}
}