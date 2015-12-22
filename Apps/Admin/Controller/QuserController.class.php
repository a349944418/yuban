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
        $this->display();
    }
}