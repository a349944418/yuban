<?php
namespace Home\Controller;
use Think\Controller;

Class BaseController extends Controller 
{

	protected $return = array('code'=>0,'message'=>'','data'=>array());
	protected $redis = '';

    public function _initialize()
    {
        import("Common.Util.RedisPool");
        $this->redis = \RedisPool::getconnect();
        /*geo 保存
        if(I('post.position') && I('post.token') && I('post.uid')) {
            if(I('post.token') = $this->redis->get('Token:uid'.I('post.uid')) ) {
                $this->redis->sadd();
            }
        }*/

        //TODO: 用户登录检测
        $not_login = array(
            'Index'     => array('index'=>1),
            'Public'    => array('getBaseLanguage'=>1, 'getTags'=>1),
            'Passport'  => array('login'=>1, 'changePwd'=>1),
            'Reg'       => array('getMobileCode'=>1, 'Register'=>1),
        );
        if(!$not_login[CONTROLLER_NAME][ACTION_NAME]) {
            $uid = I('post.uid');
            $token = I('post.token');
            $server_token = $this->redis->GET('Token:uid'.$uid);
            if(!$server_token) {
                $this->return['code'] = 1001;
                $this->return['message'] = L('token_lose');
                $this->goJson($this->return);
            }elseif($server_token != $token) {
                $this->return['code'] = 1002;
                $this->return['message'] = L('token_error');
                $this->goJson($this->return);
            }            
        }
    }

    /**
     * 生成json格式并返回
     * @param  [type] $arr [description]
     * @return [type]      [description]
     */
    protected function goJson( $arr ) {
	    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        die();
    }
}
?>
