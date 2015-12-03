<?php
namespace Home\Controller;
//use Think\Controller;

class IndexController extends BaseController
{
    public function index()
    {
    	$this->display();
        //$this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover{color:blue;}</style><div style="padding: 24px 48px;text-align:center"> <p>欢迎访问 <b>语加</b>官网！</p><br/>网站正在开发中！！！</div>','utf-8');
	}

    public function test()
    {
        // echo $this->redis->FLUSHALL();
        //dump($this->redis->del('Userinfo:uid1'));
        // dump(A('Home/User')->getUserinfoData(2));
        dump($this->redis->get('Token:uid2'));
        // dump($this->redis->HGetall('Userinfo:uid2'));
        // dump($this->redis->del('Userinfo:uid3'));
        // dump(A('Home/User')->getUserinfoData(3));
        // dump($this->redis->del('Userinfo:uid9'));
        // dump(A('Home/User')->getUserinfoData(9));
        // dump($this->redis->del('wx4f8h'));
        // dump($this->redis->del('wx4f8k'));
        dump($this->redis->sMembers('wx4f8h'));
        dump($this->redis->sMembers('wx4f8k'));
    }
}
