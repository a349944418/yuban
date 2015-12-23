<?php
namespace Admin\Controller;
use User\Api\UserApi;

/**
 * 前台用户控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class QuserController extends AdminController {

	/**
     * 前台用户首页
     * @return [type] [description]
     */
    public function index()
    {
    	$map = array();
    	//$count = D('userinfo')->count('uid');
    	$list   =   $this->lists('userinfo', $map);
    	$all_language = D('Home/Language')->getAllLanguage();
    	foreach($list as &$v){
    		$v['language_name'] = $all_language[$v['cur_language']];
    		$v['uname'] = $v['uname'] ? $v['uname'] : '<font style="color:red">第三方未绑定用户</font>';
    	}
    	//dump($list);
        $this->assign('_list', $list);
        $this->meta_title = '用户列表';
        $this->display();
    }
}