<?php
namespace Home\Controller;

Class RegController extends BaseController 
{

    /**
     * 获取短信验证码
     * @return  [json]  返回代码
     * @Auth : zbq  2015.11.04
     **/
    public function getMobileCode()
    {	
        $mobile = I('post.mobile');
        if(strlen($mobile) != 11) {
            $this->return['code'] = 1001;
            $this->return['message'] = L('mobile_error');
	    $this->return['data']['mobile'] = $mobile;
            $this->return['data']['len'] = strlen($mobile);
            $this->goJson($this->return);
        }
        $server_token = md5('yj+' . I('post.timestamp') . 'hash');
        if ( strtolower($server_token) != strtolower(I('post.token')) ) {
            $this->return['code'] = 1002;
            $this->return['message'] = L('token_error');
            $this->goJson($this->return);
        }
        
        $verify = rand(1000,9999);
        $datas = array($verify, C('smsTime'));
        $this->return['data']['verify'] = intval( $verify );
        //$mobile = '18601995223';
        //获取模板id
        $mobanId = strtolower(I('post.type')) == 'forgotpwd' ? 50642 : 47024;
        $this->sendTemplateSMS($mobile, $datas, $mobanId);      
    }

    /**
     * 发送短信接口
     * @param  [type] $to     [description]
     * @param  [type] $datas  [description]
     * @param  [type] $tempId [description]
     * @return [type]         [description]
     */
    private function sendTemplateSMS($to,$datas,$tempId)
    {   
        import("Common.Util.CCPRestSmsSDK");
        // 初始化REST SDK
        $rest = new \REST( C('smsServerIP'), C('smsServerPort'), C('smsSoftVersion') );
        $rest->setAccount( C('smsAccountSid'), C('smsAccountToken') );
        $rest->setAppId( C('smsAppId') );
        
        // 发送模板短信
        $result = $rest->sendTemplateSMS($to,$datas,$tempId);
        if($result == NULL ) {
            $this->return['code'] = 1003;
            $this->return['message'] = "result error!";
            break;
        }
        if($result->statusCode!=0) {
            $this->return['code'] = intval( $result->statusCode ) ;
            $this->return['message'] = "error msg :" . $result->statusMsg ;
            //TODO 添加错误处理逻辑
        }else{
            // 获取返回信息
            $smsmessage = (array) $result->TemplateSMS;
            $this->return['data']["dateCreated"] = $smsmessage['dateCreated'];
            $this->return['data']["smsMessageSid"] = $smsmessage['smsMessageSid'];
            //TODO 添加成功处理逻辑
        }
        $this->goJson($this->return);
    }

    /**
     * 用户注册
     */
    public function Register ( )
    {
        $post = I('post.');
        // 验证手机号
        $mobile = I('post.mobile');
        if(strlen($mobile) != 11) {
            $this->return['code'] = 1001;
            $this->return['message'] = L('mobile_error');
            $this->goJson($this->return);
        }
        if($this->checkMobile($mobile)) {
            $this->return['code'] = 1002;
            $this->return['message'] = L('mobile_has');
            $this->goJson($this->return);
        }
        // 验证昵称
        if(!$post['uname']) {
            $this->return['code'] = 1003;
            $this->return['message'] = L('uname_null');
            $this->goJson($this->return);
        }
        // 验证性别
        if($post['sex'] != 1 && $post['sex'] != 2) {
            $this->return['code'] = 1004;
            $this->return['message'] = L('sex_error');
            $this->goJson($this->return);
        }

        load("@.user");
        $post['first_letter'] = getFirstLetter($post['uname']);
        //如果包含中文将中文翻译成拼音
        if ( preg_match('/[\x7f-\xff]+/', $post['uname'] ) ){
            import("Common.Util.PinYin");
            $pinyinClass = new \PinYin();
            $pinyin = $pinyinClass->Pinyin( $post['uname'] );
            //昵称和呢称拼音保存到搜索字段
            $post['search_key'] = $post['uname'].' '.$pinyin;
        } else {
            $post['search_key'] = $post['uname'];
        }
        // 密码
        if(!$post['password']) {
            $this->return['code'] = 1004;
            $this->return['message'] = L('pwd_null');
            $this->goJson($this->return);
        }
        $post['login_salt'] = rand(11111, 99999);
        $post['password'] = md5(md5($post['password']).$post['login_salt']);

        $post['ctime'] = time();
        $post['level'] = $post['self_level'];
        $post['cur_language'] = $post['lid'];
        
        $uid = D('userinfo')->add($post);
        if(!$uid) {
            $this->return['code'] = 1005;
            $this->return['message'] = L('reg_error');
            $this->goJson($this->return);
        } else {
            $language_info['uid'] = $uid;
            $language_info['lid'] = $post['lid'];
            $language_info['type'] = 4;
            $language_info['self_level'] = $post['self_level'];
            D('userLanguage')->add($language_info);
            /*$location = explode('/', $post['location']);
            $info = array('uid'=>$uid, 'uname'=>$post['uname'], 'mobile'=>$post['mobile'], 'sex'=>$post['sex'], 'country'=>$post['country'], 'province'=>$post['province'], 'city'=>$post['city'], 'country_name'=>$location[0], 'province_name'=>$location[1], 'city_name'=>$location[2]);*/
            $this->redis->Sadd('User:sex'.$post['sex'], $uid);
            $this->createSubAccount('yujia'.$uid, $uid);
        }
        
    }
    
    /**
     * 注册容联子账号
     * @param  [type] $friendlyName [description]
     * @return [type]               [description]
     */
    private function createSubAccount($friendlyName, $uid) 
    {
        import("Common.Util.CCPRestSDK");
        // 初始化REST SDK
        $rest = new \REST(C('smsServerIP'), C('smsServerPort'), C('smsSoftVersion'));
        $rest->setAccount(C('smsAccountSid'), C('smsAccountToken'));
        $rest->setAppId(C('smsAppId'));
        
        $result = $rest->CreateSubAccount($friendlyName);
        if($result == NULL ) {
            $this->return['code'] = 1006;
            $this->return['message'] = L('regSubAccount_error');
            $this->goJson($this->return);
        }
        if($result->statusCode!=0) {
            $result = (array) $result;
            $this->return['code'] = $result['statusCode'];
            $this->return['message'] = $result['statusMsg'];
            $this->goJson($this->return);
        }else {
            
            // 获取返回信息 把云平台子帐号信息存储在您的服务器上
            $subaccount = (array) $result->SubAccount;
            $info["subAccountid"] = $subaccount['subAccountSid'];
            $info["subToken"] = $subaccount['subToken'];
            $info["dateCreated"] = $subaccount['dateCreated'];
            $info["voipAccount"] = $subaccount['voipAccount'];
            $info["voipPwd"] = $subaccount['voipPwd'];
            D('userinfo')->where('uid='.$uid)->save($info);

            $this->return['message'] = L('reg_success');
            $this->goJson($this->return);
        }      
    }

    /**
     * 检测手机号是否已经注册过
     * @param  [type] $mobile 手机号
     * @return [type]         [description]
     */
    private function checkMobile($mobile)
    {
        $uid = D('userinfo')->where('mobile="'.$mobile.'"')->getField('uid');
        return $uid;
    }

}
?>
