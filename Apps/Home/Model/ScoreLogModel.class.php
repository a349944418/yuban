<?php
namespace Home\Model;
use Think\Model;

class ScoreLogModel extends Model 
{
	/**
	 * 记录信息
	 * @param  [type]  $score    分值
	 * @param  [type]  $userList 用户列表
	 * @param  [type]  $type     加分类型(1语音聊天2视频聊天)
	 * @param  integer $cid      聊天id
	 * @return [type]            [description]
	 */
	public function saveLog($score, $userList, $type, $cid=0)
	{
		$typeArr = array(1=>'语音聊天', 2=>'视频聊天');
		$zheng = array(1,2);
		$flag = in_array($type, $zheng) ? 1 : 2;
		$info['score'] = $flag == 1 ? $score : '-'.$score;
		$info['type'] = $type;
		$info['cid'] = $cid;
		$info['ctime'] = time();
		$info['info'] = $typeArr[$type];
		foreach($userList as $v) {
			$info['uid'] = $v;
			$this->save($info);
			if($flag == 1) {
				D('userinfo') -> where('uid='.$v)->setInc('grow_score', $score);
			} else {
				D('userinfo') -> where('uid='.$v)->setDec('grow_score', $score);
			}
			
		}
	}
}
?>