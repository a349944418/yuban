<?php
namespace Home\Model;
use Think\Model;

class FriendModel extends Model 
{
	/**
	 * 添加关注信息
	 * @param [type] $from_id 关注者id
	 * @param [type] $to_id   被关注者id
	 * @param [type] $type    类型（1.点击按钮关注2.聊天关注）
	 */
	public function addUser($from_id, $to_id, $type)
	{
		$info['from_id'] = $from_id;
		$info['to_id'] = $to_id;
		$info['type'] = $type;
		$info['time'] = time();
		$this->add($info);
	}
}