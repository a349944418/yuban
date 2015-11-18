<?php
namespace Home\Model;
use Think\Model;

class LanguageModel extends Model 
{
	/**
	 * 获取全部语言
	 * @return [type] [description]
	 */
	public function getAllLanguage()
	{
		$all = F('allLanguage');
		if (!$all) {
			$res = $this->field('lid, language_name')->select();
			foreach($res as $v){
				$all[ $v['lid'] ] = $v['language_name'];
			}
			F('allLanguage', $all);
		}
		return $all;
	}
}
?>