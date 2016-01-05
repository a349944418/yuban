<?php
/**
 * 广告设置
 * @Author zbq 
 */
namespace Admin\Controller;

Class AdController extends AdminController
{
	/**
	 * 广告位置列表
	 * @return [type] [description]
	 */
	public function index() 
	{
		$list = $this->lists('adPos', array());
		foreach($list as &$v) {
			switch ($v['type']) {
			 	case '1':
			 		$v['type'] = '图片幻灯片';
			 		break;
			}
			$v['count'] = D('ad')->where('position='.$v['id'])->count('aid');
		}
		$this->assign('_list', $list);
		$this->display();
	}

	/**
	 * 广告列表
	 * @return [type] [description]
	 */
	public function adList()
	{
		$map['pos'] = I('pos');
		$list = $this->lists('ad', $map, 'sort');
		foreach($list as &$v) {
			$v['path'] = D('picture') -> where('id='.$v['pic']) -> getField('path');
			$v['purl'] = C('website_url').$v['path'];
		}
		$this->assign('_list', $list);
		$this->display();
	}

	/**
	 * 编辑广告
	 * @return [type] [description]
	 */
	public function adEdit()
	{
		$aid = I('id');
		$info['type'] = 1;
		if($aid) {
			$info = D('ad')->where('aid='.$aid)->find();
		} 
		$this->assign('info', $info);
		$this->display();
	}

	/**
	 * 保存广告
	 * @return [type] [description]
	 */
	public function adSave()
	{
		$info = I('post.');
		if(I('aid')) {
			$res = D('ad')->save($info);
		} else {
			$res = D('ad')->add($info);
		}
		if($res) {
			$this->success('保存成功！', U('adList'));
		} else {
			$error = D('ad')->getError();
            $this->error(empty($error) ? '未知错误！' : $error);
		}
	}
}