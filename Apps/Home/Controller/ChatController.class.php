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
		//将两个用户加入正在聊天中队列
		$this->redis->SADD('Userinfo:chating', $post['from_id']);
		$this->redis->SADD('Userinfo:chating', $post['to_id']);
		//将对方加入关注表
		D('friend')->addUser($post['from_id'], $post['to_id'], 2);
		D('friend')->addUser($post['to_id'], $post['from_id'], 2);
		$this->redis->SADD('Userinfo:friend'.$post['from_id'], $post['to_id']);
		$this->redis->SADD('Userinfo:friend'.$post['to_id'], $post['from_id']);
		$this->goJson($this->return);
	}

	/**
	 * 结束聊天，记录信息
	 * @return [type] [description]
	 */
	public function chatEnd()
	{
		$info['etime'] = time();
		$cid = I('post.cid');
		//保存聊天信息,并将两人从聊天用于队列中删除
		$res = D('chat')->field('from_id, to_id, stime, type')->where('cid='.$cid)->find();
		D('chat')->where('cid='.$cid)->save($info);
		$this->redis->SREM('Userinfo:chating', $post['from_id']);
		$this->redis->SREM('Userinfo:chating', $post['to_id']);
		unset($info);
		//写入得分记录
		$timelong = floor(($post['etime']-$res['stime'])/60);
		$score = json_decode($this->redis->GET('score_setting'), true);
		$score = $res['type'] == 1 ? $score['achat_score'] : $score['vchat_score'];
		$score_value = $score*$timelong; //分值
		D('scoreLog')->saveLog($score_value, array($res['from_id'], $res['to_id']), $res['type']);

		$this->goJson($this->return);
	}
}