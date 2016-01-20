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

        //TODO: 用户登录检测
        $not_login = array(
            'Index'     => array('index'=>1, 'test'=>1),
            'Public'    => array('getbaselanguage'=>1, 'gettags'=>1),
            'Passport'  => array('login'=>1, 'changePwd'=>1, 'ologin'=>1),
            'User'      => array('index'=>1),
            'Reg'       => array('getmobilecode'=>1, 'register'=>1),
            'Square'    => array('topic'=>1, 'nearby'=>1, 'topicuser'=>1, 'charts'=>1),
            'Ad'        => array('getad'=>1),
        );
        if(!$not_login[CONTROLLER_NAME][ACTION_NAME]) {
            $this->mid = I('post.userId');
            $token = I('post.token');
            $server_token = $this->redis->GET('Token:uid'.$this->mid);
            if(!$server_token) {
                $this->return['code'] = 401;
                $this->return['message'] = L('token_lose');
                $this->goJson($this->return);
            }elseif($server_token != $token) {
                $this->return['code'] = 401;
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
