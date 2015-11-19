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
		$uid = I('post.uid');
		$re = $this->lbs->serach($lati,$longi);
		//算出实际距离
		foreach($re as $key=>$val) {
			$tmp_userinfo = array();
			if($val == $uid) {
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

}