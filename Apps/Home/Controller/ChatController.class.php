<?php
namespace Home\Controller;

Class ChatController extends BaseController
{
	/**
	 * 开始聊天之前，记录信息
	 * @return [type] [description]
	 */
	public function chatStart()
	{
		$post = I('post.');
		$post['stime'] = time();
		$cid = D('chat')->add($post);
		$this->return['data']['cid'] = $cid;
		$this->goJson($cid);
	}

	/**
	 * 结束聊天，记录信息
	 * @return [type] [description]
	 */
	public function chatEnd()
	{
		$post = I('post.');
	}
}