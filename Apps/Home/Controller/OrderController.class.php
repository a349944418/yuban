<?php
namespace Home\Controller;

class OrderController extends BaseController
{
	/**
	 * 获取订单号
	 * @return [type] [description]
	 */
	public function getOrder()
	{
		$info['money'] = I('post.money');

		if($info['money'] < 0.01) {
			$this->return['code'] = 1003;
			$this->return['message'] = L('money_error');
			$this->goJson($this->return);
		}
		$data['orderId'] = $info['orderId'] = build_order_no();
		$info['ctime'] = time();
		$info['type'] = 1;
		$info['uid'] = $this->mid;
		$info['note'] = '充值'.$info['money'].'元';
		$id = D('mlog')->add($info);
		if($id) {
			$data['notifyUrl'] = C('WEBSITE_URL').'/index.php/Home/Order/ali_status';
			$data['title'] = L('chongzhi');
			$data['des'] = L('chongzhi_des',array('uname' => $this->redis->Hget('Userinfo:uid'.$this->mid, 'uname'),'money' => $info['money']));
			$this->return['data'] = $data;
		} else {
			$this->return['code'] = 1004;
			$this->return['message'] = L('opera_error');
		}
		$this->goJson($this->return);
	}

	/**
	 * 支付宝返回数据
	 * @return [type] [description]
	 */
	public function ali_status()
	{
		$info = $_REQUEST;
		F('alipay', $info);
	}

	/**
	 * [支付成功]
	 * @return [type] [description]
	 */
	public function orderSuccess()
	{
		$orderId = I('post.orderId');

		$res = D('mlog')->field('id, uid, money, status')->where('orderId="'.$orderId.'"')->find();

		if(!$res['id']) {
			$this->return['code'] = 1003;
			$this->return['message'] = L('orderId_null');
			$this->goJson($this->return);
		}

		if($res['uid'] != $this->mid) {
			$this->return['code'] = 1004;
			$this->return['message'] = L('user_error');
			$this->goJson($this->return);
		}

		if($res['status'] != 1) {
			$this->return['code'] = 1005;
			$this->return['message'] = L('orderType_error');
			$this->goJson($this->return);
		}
		$info['status'] = 2;
		D('mlog') -> where('id='.$res['id']) -> save($info);

		$flag = D('umoney') -> where('uid='.$this->mid) -> field('totalmoney, id') -> find();

		if($flag['id']) {
			D('umoney')->where('id='.$flag['id'])->setInc('totalmoney', $res['money']);
		} else {
			$info['uid'] = $this->mid;
			$info['totalmoney'] = $res['money'];
			D('umoney') -> add($info);
		}

		$this->goJson($this->return);
	}

	/**
	 * 提现订单
	 * @return [type] [description]
	 */
	public function tixian()
	{
		$info['money'] = I('post.money');
		$umoney = D('umoney')->field('totalmoney, not_tixian')->where('uid='.$this->mid)->find();
		if($info['money'] < 100 || $info['money'] > ($umoney['totalmoney']-$umoney['not_tixian']) ) {
			$this->return['code'] = 1003;
			$this->return['message'] = L('money_error');
			$this->goJson($this->return);
		}
		$map['uid'] = $this->mid;
		$map['is_del'] = 0;
		$ali_info = D('userAlipay') -> where($map) -> getField('id');
		if(!$ali_info) {
			$this->return['code'] = 1004;
			$this->return['message'] = L('ali_info_error');
			$this->goJson($this->return);
		}

		$info['orderId'] = build_order_no();
		$info['ctime'] = time();
		$info['type'] = 3;
		$info['uid'] = $this->mid;
		$info['note'] = '提现'.$info['money'].'元';
		$id = D('mlog')->add($info);
		D('umoney')->where('uid='.$this->mid)->setInc('not_tixian', $info['money']);
		$this->return['message'] = L('wait_moment');
		$this->goJson($this->return);
	}
}