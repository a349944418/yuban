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
		$info['score'] = $score;
		$info['type'] = $type;
		$info['cid'] = $cid;
		$info['ctime'] = time();
		foreach($userList as $v) {
			$info['uid'] = $v;
			$this->save($info);
		}
	}
}
?>