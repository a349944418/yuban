<?php
namespace Home\Controller;
use Home\Controller;

Class RegController extends BaseController 
{

    /**
     * 获取短信验证码
     * @return  [json]  返回代码
     * @Auth : zbq  2015.11.04
     **/
    public function getMobileCode()
    {
        
        $mobile = intval( I('post.mobile') );
        if(strlen($mobile) != 11) {
            $this->return['code'] = 1001;
            $this->return['message'] = L('mobile_error');
            $this->goJson($this->return);
        }
        $server_token = md5('yj+' . I('post.timestamp') . 'hash');
        if ( $server_token != I('post.token') ) {
            $this->return['code'] = 1002;
            $this->return['message'] = L('token_error');
            $this->goJson($this->return);
        }
        
        $verify = rand(1000,9999);
        $datas = array($verify, C('smsTime'));
        $this->return['data']['verify'] = intval( $verify );
        //$mobile = '18601995223';
        $this->sendTemplateSMS($mobile, $datas, 9081);      
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
        $mobile = intval( I('post.mobile') );
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
        
        $uid = D('userinfo')->add($post);
        if(!$uid) {
            $this->return['code'] = 1005;
            $this->return['message'] = L('reg_error');
        } else {
            $language_info['uid'] = $uid;
            $language_info['lid'] = $post['lid'];
            $language_info['type'] = 1;
            $language_info['self_level'] = $post['self_level'];
            D('userLanguage')->add($language_info);
            /*$location = explode('/', $post['location']);
            $info = array('uid'=>$uid, 'uname'=>$post['uname'], 'mobile'=>$post['mobile'], 'sex'=>$post['sex'], 'country'=>$post['country'], 'province'=>$post['province'], 'city'=>$post['city'], 'country_name'=>$location[0], 'province_name'=>$location[1], 'city_name'=>$location[2]);*/
            $this->return['message'] = L('reg_success');
            $this->return['data'] = $info;
        }
        $this->goJson($this->return);
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

    /**
     * 获取母语语言列表
     * @return [type] [description]
     */
    public function getBaseLanguage() {
        $this->return['data'] = F('baseLanguage');

        if(!$this->return['data']) {
            $this->return['data'] = D('language')->where('type=1')->field('lid, language_name')->select();
            F('baseLanguage', $this->return['data']);
        }

        $this->goJson($this->return);
    }
}
?>
