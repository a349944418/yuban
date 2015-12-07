<?php
namespace Home\Model;
use Think\Model;

class FriendModel extends Model 
{
	/**
	 * 添加关注信息
	 * @param [type] $from_id 关注者id
	 * @param [type] $to_id   被关注者id
	 * @param [type] $type    类型（1.点击按钮关注2.聊天3.聊天+关注）
	 */
	public function addUser($from_id, $to_id, $type)
	{
		$res = $this->where('from_id='.$from_id.' and to_id='.$to_id)->field('id, type')->select();
		if(!$res){
			$info['from_id'] = $from_id;
			$info['to_id'] = $to_id;
			$info['type'] = $type;
			$info['ctime'] = time();
			$this->add($info);
		}else {
			if($res['type'] != 3 && $res['type'] != $type) {
				$info['type'] = 3;
				$this->where('id='.$res['id'])->save($info);
			}
		}
		return $info['type'];
	}

	/**
	 * 获取用户列表
	 * @param  [type] $map [description]
	 * @return [type]      [description]
	 */
	public function getFriend($map)
	{
		$res = $this->field('to_id')->where($map)->order('id desc')->select();
		return $res;
	}
}