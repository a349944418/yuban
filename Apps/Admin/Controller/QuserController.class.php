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
        $this->assign('_list', $list);
        $this->meta_title = '用户列表';
        $this->display();
    }

    /**
     * 绑定支付宝账号
     * @return [type] [description]
     */
    public function alipay()
    {
        $map['status'] = 1;
        $list   =   $this->lists('userAlipayTmp', $map);
        foreach( $list as &$v) {
            $tmp = D('userAlipay') -> field('ali_name as alipay_name, ali_num as alipay_num') -> where('is_del = 0 and uid ='.$v['uid']) -> find();
            $tmp['uname'] = $this->redis->HGET('Userinfo:uid'.$v['uid'], 'uname');
            if(!$tmp['uname']) {
                A('Home/User')->getUserinfoData($tmp['uid']);
            }
            $v = array_merge($v, $tmp);
        }
        $this->assign('_list', $list);
        $this->meta_title = '支付宝绑定审核';
        $this->display();
    }

    /**
     * 审核支付宝账户
     * @param  [type] $id     [description]
     * @param  [type] $status [description]
     * @return [type]         [description]
     */
    public function checkAlipay($id, $status) 
    {
        if($id && $status) {
            if($status == 2) {
                $info = D('userAlipayTmp')->field('ali_num, ali_name, uid')->where('id='.$id)->find();
                $save['is_del'] = 1;
                $save['status'] = 2;
                $info['ctime'] = $save['ctime'] = time();
                D('userAlipay')->where('uid='.$info['uid'].' and is_del=0')->save($save);
                D('userAlipayTmp')->where('id='.$id)->save($save);
                unset($save);
                D('userAlipay')->add($info);
            } else {
                $info['status'] = $status;
                $info['ctime'] = time();
                D('userAlipayTmp')->where('id='.$id)->save($info);
            }
            $res['status'] = 1;
            $res['info'] = '操作成功';
        } else {
            $res['status'] = 2;
            $res['info'] = '操作失败，请稍后重试';
        }
        die(json_encode($res));
    }
}