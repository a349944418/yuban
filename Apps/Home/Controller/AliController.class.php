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
		D('userAlipay') -> where($map) -> find();
	}

	/**
	 * 用户绑定
	 * @return [type] [description]
	 */
	public function bind()
	{

	}

}