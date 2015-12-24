<?php
namespace Home\Controller;

Class AliController extends BaseController
{
	/**
	 * 绑定列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$map['uid'] = $this->mid;
		$map['is_del'] = 0;
		$res = D('userAlipay') -> field('ali_name, ali_num') -> where($map) -> find();
		$this->return['data'] = $res;
		$this->goJson($this->return);
	}

	/**
	 * 用户绑定
	 * @return [type] [description]
	 */
	public function bind()
	{
		$data['ali_num'] = I('post.alipay_num');
		if(!$data['ali_num']) {
			$this->return['code'] = 1003;
			$this->return['message'] = L('alipay_num_error');
			$this->goJson($this->return);
		}
		$data['ali_name'] = I('post.alipay_name');
		if(!$data['ali_name']) {
			$this->return['code'] = 1004;
			$this->return['message'] = L('alipay_name_error');
			$this->goJson($this->return);
		}
		$data['uid'] = $this->mid;
		$info['status'] = 3;
		D('userAlipayTmp')->where('status = 1 and uid='.$this->mid)->save($info);
		D('userAlipayTmp')->add($data);
		$this->return['message'] = L('wait_moment');
		$this->goJson($this->return);
	}

}