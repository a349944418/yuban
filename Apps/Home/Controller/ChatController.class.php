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
		$this->redis->SADD('Userinfo:friend2:'.$post['from_id'], $post['to_id']);
		$this->redis->SADD('Userinfo:friend2:'.$post['to_id'], $post['from_id']);
		//将对方加入聊过的列表，然后进行聊天次数累加
		$res1 = $this->redis->SADD('Userinfo:spoken'.$post['from_id'], $post['to_id']);
		if($res1){
			D('userinfo')->where('uid='.$post['from_id'])->setInc('spoken_num');
		}
		$res2 = $this->redis->SADD('Userinfo:spoken'.$post['to_id'], $post['from_id']);
		if($res2){
			D('userinfo')->where('uid='.$post['to_id'])->setInc('spoken_num');
		}
		$this->goJson($this->return);
	}

	/**
	 * 结束聊天，记录信息
	 * @return [type] [description]
	 */
	public function chatEnd()
	{
		$cid = I('post.cid');
		//判断该聊天是否已经完成，如完成，直接跳出，不做任何处理
		$zetime = D('chat')->where('cid='.$cid)->getField('etime');
		if(!$zetime) {
			$info['etime'] = time();
			//保存聊天信息,并将两人从聊天用于队列中删除
			$res = D('chat')->field('from_id, to_id, stime, type')->where('cid='.$cid)->find();
			D('chat')->where('cid='.$cid)->save($info);
			$this->redis->SREM('Userinfo:chating', $res['from_id']);
			$this->redis->SREM('Userinfo:chating', $res['to_id']);
			$timelong_seconed = ($info['etime']-$res['stime']);  // 聊天时长（秒）
			//将聊天时长分别加入两人信息中
			D('userinfo')->where('uid='.$res['from_id'].' or uid='.$res['to_id'])->setInc('spoken_long', $timelong_seconed);
			//更新redis缓存
			$this->redis->HINCRBY('Userinfo:uid'.$res['from_id'], 'spoken_long', $timelong_seconed);
			$this->redis->HINCRBY('Userinfo:uid'.$res['to_id'], 'spoken_long', $timelong_seconed);
			$this->redis->HINCRBY('Userinfo:uid'.$res['from_id'], 'spoken_num', 1);
			$this->redis->HINCRBY('Userinfo:uid'.$res['to_id'], 'spoken_num', 1);
			//写入得分记录
			$timelong = floor($timelong_seconed/60);
			$score = json_decode($this->redis->GET('score_setting'), true);
			$score = $res['type'] == 1 ? $score['achat_score'] : $score['vchat_score'];
			$score_value = $score*$timelong; //分值
			D('scoreLog')->saveLog($score_value, array($res['from_id'], $res['to_id']), $res['type']);
			$this->redis->HINCRBY('Userinfo:uid'.$res['from_id'], 'grow_score', $score_value);
			$this->redis->HINCRBY('Userinfo:uid'.$res['to_id'], 'grow_score', $score_value);
			//扣费
			$price = $this->redis->HGET('Userinfo:uid'.$res['to_id'], 'price');
			$fmoney = D('umoney')->field('id, totalmoney, not_tixian')where('uid='.$res['from_id'])->find();
			if($fmoney['totalmoney'] - $price >= $fmoney['not_tixian']) {
				D('umoney') -> where('id='.$fmoney['id']) -> setDec('totalmoney', $price);
			} else {
				$fmoneyInfo['totalmoney'] = $fmoney['totalmoney'] - $price;
				$fmoneyInfo['not_tixian'] = $fmoneyInfo['totalmoney'] > 0 ? $fmoneyInfo['totalmoney'] : 0;
				D('umoney') -> where('id='.$fmoney['id']) -> save($fmoneyInfo);
			}
			$mlog['money'] = round(( $price / 60 ) * $timelong_seconed, 2);
			$mlog['status'] = 2;
			$mlog['uid'] = $res['from_id'];
			$mlog['ctime'] = time();
			$mlog['type'] = 3；
			$mlog['note'] = '聊天消费'.$mlog['money'].'元';
			$mlog['orderId'] = $cid;
			D('mlog')->add($mlog);
			//赚钱计费
			D('umoney') -> where('uid='.$res['to_id']) -> setInc('totalmoney', $price);
			$mlog['uid'] = $res['to_id'];
			$mlog['orderId'] = $cid;
			$mlog['type'] = 4;
			$mlog['note'] = '聊天收入'.$mlog['money'].'元';
			D('mlog')->add($mlog);
		}		

		$this->goJson($this->return);
	}

	/**
	 * 评论接口
	 * @return [type] [description]
	 */
	public function comment()
	{
		$data['cid'] = I('post.cid');
		$res = D('chat')->field('from_id, to_id, comment')->where('cid='.$data['cid'])->find();
		if(!$data['cid'] || !is_numeric($data['cid']) || !$res) {
			$this->return['code'] = 1003;
			$this->return['message'] = L('cid_error');
			$this->goJson($this->return);
		}
		$data['from_id'] = $this->mid;
		if($res['from_id'] != $data['from_id'] && $res['to_id'] != $data['from_id']) {
			$this->return['code'] = 1004;
			$this->return['message'] = L('from_id_error');
			$this->goJson($this->return);
		}
		$data['score'] = intval(I('post.score') );
		if(!$data['score'] || $data['score']>5 || $data['score']<1 || !is_numeric($data['score'])) {
			$this->return['code'] = 1005;
			$this->return['message'] = L('score_error');
			$this->goJson($this->return);
		}
		$data['content'] = trim(I('post.content'));
		if(!$data['content']) {
			$this->return['code'] = 1006;
			$this->return['message'] = L('content_null');
			$this->goJson($this->return);
		}
		$id = D('comment')->where('cid='.$data['cid'].' and from_id='.$data['from_id'])->getField('id');
		if($id) {
			$this->return['code'] = 1007;
			$this->return['message'] = L('comment_has');
			$this->goJson($this->return);
		}
		$data['ctime'] = time();
		$data['uid'] = $res['from_id'] == $data['from_id'] ? $res['to_id'] : $res['from_id'];
		if($res['comment'] == 0){
			if($res['from_id'] == $data['from_id']) {
				$info['comment'] = 1;
			} else {
				$info['comment'] = 2;
			}
		} else {
			$info['comment'] = 3;
		}
		D('comment') -> add($data);
		D('chat')->where('cid='.$data['cid'])->save($info);
		//重新计算等级
		$level = D('userLanguage')->field('self_level, sys_level')->where('type=4 and uid='.$data['uid'])->find();
		if($level) {
			$levelInfo['sys_level'] = ($level['sys_level']+$data['score'])/2;
			D('userLanguage')->where('type=4 and uid='.$data['uid'])->save($levelInfo);
			$userLevel['level'] = ($level['self_level'])/2+$levelInfo['sys_level'];
			D('userinfo')->where('uid='.$data['uid'])->save($userLevel);
			$userLevel['level'] = round($userLevel['level']);
			$this->redis->HSET('Userinfo:uid'.$data['uid'], 'level', $userLevel['level']);
		} 

		$this->goJson($this->return);
	}
}