<?php
namespace Home\Model;
use Think\Model;

class TagsModel extends Model 
{
	/**
	 * 获取全部语言
	 * @return [type] [description]
	 */
	public function getAllTags()
	{
		$all = F('allTags');
		if (!$all) {
			$res = $this->field('tid, tags_name')->select();
			foreach($res as $v){
				$all[ $v['tid'] ] = $v['tags_name'];
			}
			F('allTags', $all);
		}
		return $all;
	}
}
?>