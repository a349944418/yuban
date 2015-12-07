<?php
namespace Home\Model;
use Think\Model;

class AdModel extends Model 
{

	/**
	 * 获取广告
	 * @return [type] [description]
	 */
	public function getAd($post)
	{
		$return = D('ad')->field('pic, url')->where('is_del = 0 and type='.$post['type'])->order('sort Desc aid Desc')->limit($post['limit'])->select();
		foreach($return as $k=>$v) {
			if($v['pic']) {
				$return[$k]['pic'] = C('WEBSITE').D('Picture') -> where('id='.$v['pic']) -> getField('path');
			}			
		}
		return $return;
	}
}