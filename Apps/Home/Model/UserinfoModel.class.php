<?php
namespace Home\Model;
use Think\Model;

class UserinfoModel extends Model 
{

	/**
	 * 获取uid
	 * @param  [string] $mobile 手机号
	 * @return [string]         uid
	 */
	public function getUid($mobile)
	{
		$uid = $this->where('mobile="'.$mobile.'"')->getField('uid');
		return $uid;
	}

	/**
	 * 获取用户信息
	 * @param  [int] $uid 用户id
	 * @return [array]    用户信息
	 */
	public function getUserInfo($uid)
	{
		$info = $this->where('uid='.$uid)->find();
		return $info;
	}
}
?>