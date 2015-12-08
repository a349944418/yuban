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
		$info['note'] = '充值';
		$id = D('mlog')->add($info);
		if($id) {
			$data['notifyUrl'] = C('WEBSITE_URL').'/index.php/Home/Order/ali_status';
			$data['title'] = L('chongzhi');
			$data['description'] = L('chongzhi_des',array('uname' => $this->redis->Hget('Userinfo:uid'.$this->mid, 'uname'),'money' => $info['money']));
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
		$info = I('post.');
		F('alipay', $info);
	}

}