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
