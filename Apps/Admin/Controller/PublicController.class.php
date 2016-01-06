<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use User\Api\UserApi;

/**
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class PublicController extends \Think\Controller {

    /**
     * 后台用户登录
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function login($username = null, $password = null, $verify = null){
        if(IS_POST){
            /* 检测验证码 TODO: */
            if(!check_verify($verify)){
                $this->error('验证码输入错误！');
            }

            /* 调用UC登录接口登录 */
            $User = new UserApi;
            $uid = $User->login($username, $password);
            if(0 < $uid){ //UC登录成功
                /* 登录用户 */
                $Member = D('Member');
                if($Member->login($uid)){ //登录用户
                    //TODO:跳转到登录前页面
                    $this->success('登录成功！', U('Index/index'));
                } else {
                    $this->error($Member->getError());
                }

            } else { //登录失败
                switch($uid) {
                    case -1: $error = '用户不存在或被禁用！'; break; //系统级别禁用
                    case -2: $error = '密码错误！'; break;
                    default: $error = '未知错误！'; break; // 0-接口参数错误（调试阶段使用）
                }
                $this->error($error);
            }
        } else {
            if(is_login()){
                $this->redirect('Index/index');
            }else{
                /* 读取数据库中的配置 */
                $config	=	S('DB_CONFIG_DATA');
                if(!$config){
                    $config	=	D('Config')->lists();
                    S('DB_CONFIG_DATA',$config);
                }
                C($config); //添加配置
                
                $this->display();
            }
        }
    }

    /* 退出登录 */
    public function logout(){
        if(is_login()){
            D('Member')->logout();
            session('[destroy]');
            $this->success('退出成功！', U('login'));
        } else {
            $this->redirect('login');
        }
    }

    public function verify(){
        $verify = new \Think\Verify();
        $verify->entry(1);
    }

    /**
     * 支付结果通知
     * @return [type] [description]
     */
    public function notify()
    {
        F('alipayPLog', $_POST);
        F('alipayGLog', $_GET);
        import("Common.Util.alipay_notify");
        //计算得出通知验证结果
        $alipayNotify = new \AlipayNotify(C('ALIPAY_PARAM'));
        $verify_result = $alipayNotify->verifyNotify();

        if($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代

            
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            
            //批量付款数据中转账成功的详细信息

            $success_details = I('success_details');
            if($success_details) {
                $sInfo['status'] = 2;
                $success_details_arr = explode('|', $success_details);
                foreach($success_details_arr as $v) {
                    $tmp = explode('^', $v);
                    $tmpRes = D('mlog')->field('uid, money, id')->where('orderId='.$tmp[0])->find();
                    D('mlog')->where('id='.$tmpRes['id'])->save($sInfo['status']);
                    D('umoney')->where('uid='.$tmpRes['uid'])->setDec('totalmoney', $tmpRes['money']);
                    D('umoney')->where('uid='.$tmpRes['uid'])->setDec('not_tixian', $tmpRes['money']);
                }
            }

            //批量付款数据中转账失败的详细信息
            $fail_details = I('fail_details');
            if($fail_details) {
                $sInfo['status'] = 4;
                $fail_details_arr = explode('|', $fail_details);
                foreach($fail_details_arr as $v) {
                    $tmp = explode('^', $v);
                    $tmpRes = D('mlog')->field('uid, money, id')->where('orderId='.$tmp[0])->find();
                    D('mlog')->where('id='.$tmpRes['id'])->save($sInfo['status']);
                    D('umoney')->where('uid='.$tmpRes['uid'])->setDec('not_tixian', $tmpRes['money']);
                }
            }
            F('alipaySLog', $success_details);
            F('alipayFLog', $fail_details);
            //判断是否在商户网站中已经做过了这次通知返回的处理
                //如果没有做过处理，那么执行商户的业务程序
                //如果有做过处理，那么不执行商户的业务程序
                
            $RES = "success";     //请不要修改或删除
        } else {

            //验证失败
            $RES =  "fail";
        }
        F('alires', $RES);
    }
}
