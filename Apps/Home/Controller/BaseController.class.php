<?php
namespace Home\Controller;
use Think\Controller;

Class BaseController extends Controller 
{

	protected $return = array('code'=>0,'message'=>'','data'=>array());
	
    /**
     * 生成json格式并返回
     * @param  [type] $arr [description]
     * @return [type]      [description]
     */
    protected function goJson( $arr ) {
        echo json_encode($arr);
        die();
    }
}
?>
