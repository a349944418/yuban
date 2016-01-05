<?php
namespace Admin\Controller;
use User\Api\UserApi;

/**
 * 支付宝log控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class alipayController extends AdminController 
{

	public function log() 
	{
		$type = I('type');
		$uid = I('uid');
		if($uid) {
			$map['uid'] = $uid;
		}
		if($type) {
			$map['type'] = $type;
		}
		$list = $this->lists('mlog', $map);
		foreach($list as &$v){
			$v['uname'] = $this->redis->HGET('Userinfo:uid'.$v['uid'], 'uname');
	        if(!$v['uname']) {
	            A('Home/User')->getUserinfoData($v['uid']);
	        }
	        switch ($v['status']) {
				case 1:
					$v['status'] = '未支付';
					break;
				case 2:
					$v['status'] = '已支付';
					break;
				case 3:
					$v['status'] = '打款中';
					break;
				case 4:
					$v['status'] = '系统出错，失败';
					break;
				case 5:
					$v['status'] = '提现失败，未通过审核';
					break;
			}
		}
		switch ($type) {
			case 1:
				$page_title = '充值记录';
				break;
			case 2:
				$page_title = '提现记录';
				break;
			case 3:
				$page_title = '聊天消费记录';
				break;
			case 4:
				$page_title = '聊天赚取记录';
				break;
		}
		$this->assign('page_title', $page_title);
		$this->assign('_list', $list);
	    $this->meta_title = '用户信息';
	    $this->display();
	}
	
	/**
	 * 提现审核列表
	 * @return [type] [description]
	 */
	public function tixian()
	{
		$map['type'] = 2;
		$map['status'] = 1;
		$list = $this->lists('mlog', $map);
		foreach($list as &$v){
			$v['uname'] = $this->redis->HGET('Userinfo:uid'.$v['uid'], 'uname');
	        if(!$v['uname']) {
	            A('Home/User')->getUserinfoData($v['uid']);
	        }
	        $v['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
	        $v['smoney'] = $v['money'] * 0.9;
	        $tmp = D('umoney') -> field('totalmoney, not_tixian') -> where('uid='.$v['uid']) -> find();
	        $v = array_merge($v, $tmp);
	    }
	    $this->assign('_list', $list);
	    $this->meta_title = '提现审核';
	    $this->display();
	}

	/**
	 * 提现通过审核
	 * @return [type] [description]
	 */
	public function pass()
	{
		$id = I('id');
		if ($id) {
			$info = D('mlog')->field('type, orderId, status, money, uid')->where('id='.$id)->find();
			dump($info);
			//判断是提现模式且未支付
			if($info['type'] == 2 && $info['status'] == 1) {
				$aliInfo = D('userAlipay')->field('ali_num, ali_name')->where('is_del=0 and uid='.$info['uid'])->find();
				//判断已绑定支付宝信息
				if($aliInfo) {
					$zzmoney = round($info['money']*C('ALIPAY_PARAM.ZHEKOU'), 2);
					$zzinfo = $info['orderId'].'^'.$aliInfo['ali_num'].'^'.$aliInfo['ali_name'].'^'.$zzmoney.'^提现';
					$res = $this->zhuanzhang($zzmoney, 1, $zzinfo);
					echo $res;
					die();
				} else {
					$this->error('支付宝未绑定');
				}
			}
		} 
		$this->error('参数有误');
	}

	/**
	 * 支付宝转账私有方法
	 * @param  [type] $WIDbatch_fee   总金额
	 * @param  [type] $WIDbatch_num   总笔数
	 * @param  [type] $WIDdetail_data 详细信息
	 * @return [type]                 [description]
	 */
	private function zhuanzhang($batch_fee, $batch_num, $detail_data)
	{
		$parameter = array(
			"service"        => "batch_trans_notify",
			"partner"        => C('ALIPAY_PARAM.PARTNER'),
			"notify_url"     => C('WEBSITE_URL').U('notify'),
			"email"          => C('ALIPAY_PARAM.EMAIL'),
			"account_name"   => C('ALIPAY_PARAM.ACCOUNT_NAME'),
			"pay_date"       => date('Ymd'),
			"batch_no"       => date('YmdHis'),
			"batch_fee"      => $batch_fee,
			"batch_num"      => $batch_num,
			"detail_data"    => $detail_data,
			"_input_charset" => C('ALIPAY_PARAM.INPUT_CHARSET')
		);
		import("Common.Util.AlipaySubmit");
		$alipaySubmit = new \AlipaySubmit(C('ALIPAY_PARAM'));
		$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
		return $html_text;
	}
}