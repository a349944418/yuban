<?php
namespace Home\Controller;
//use Think\Controller;

class IndexController extends BaseController
{
    public function index()
    {
    	$this->display();
	}

    public function test()
    {
        //echo $this->redis->FLUSHALL();
        //dump($this->redis->del('Userinfo:uid2'));
        // dump(A('Home/User')->getUserinfoData(2));
        //dump($this->redis->del('Userinfo:uid11'));
        dump($this->redis->get('Token:uid2'));
        dump($this->redis->HGetall('Userinfo:uid2'));
        // dump($this->redis->del('Userinfo:uid3'));
        // dump(A('Home/User')->getUserinfoData(3));
        // dump($this->redis->del('Userinfo:uid9'));
        // dump(A('Home/User')->getUserinfoData(9));
        // 
        //D('userinfo')->getSearchList();
    }


}
