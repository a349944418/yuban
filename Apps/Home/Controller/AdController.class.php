<?php
/**
 * 广告类
 * Author: zbq
 */
namespace Home\Controller;

Class AdController extends BaseController
{
	/**
	 * 获取广告内容
	 * @return [type] [description]
	 */
	public function getAd()
	{
		$post['type'] = I('post.type');
		if ( !$post['type'] ) {
			$this->return['code'] = 1003;
			$this->return['message'] = L('Adtype_null');
			$this->goJson($this->return);
		}
		$post['limit'] = I('post.limit') : I('post.limit') : 4;

		$res = D('ad')->field('pic')->where('is_del = 0 and type='.$post['type'])->order('sort Desc aid Desc')->limit($post['limit'])->select();

		$this->return['data']['ad'] = $res;
		$this->goJson($this->return);
	}
}