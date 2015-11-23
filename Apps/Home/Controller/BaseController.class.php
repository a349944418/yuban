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
            'Public'    => array('getBaseLanguage'=>1, 'getTags'=>1),
            'Passport'  => array('login'=>1, 'changePwd'=>1),
            'Reg'       => array('getMobileCode'=>1, 'register'=>1),
        );
        if(!$not_login[CONTROLLER_NAME][ACTION_NAME]) {
            $uid = I('post.userId');
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

        //更新经纬度
        if(I('post.userId')) {
            $this->mid = I('post.userId');         //当前用户id   
            $data['lati'] = I('post.lati');
            $data['longi'] = I('post.longi');
            if($data['lati'] && $data['longi']) {               
                D('userinfo')->where('uid='.$this->mid)->save($data);
                import("Common.Util.LBS");
                $this->lbs = new \LBS($this->redis);
                $this->lbs->upinfo('Userinfo:uid'.$this->mid, $data['lati'], $data['longi'] );
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
