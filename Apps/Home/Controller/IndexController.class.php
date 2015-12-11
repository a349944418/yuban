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
        // echo $this->redis->FLUSHALL();
        //dump($this->redis->del('Userinfo:uid1'));
        // dump(A('Home/User')->getUserinfoData(2));
        //dump($this->redis->del('Userinfo:uid11'));
        dump($this->redis->get('Token:uid2'));
        // dump($this->redis->HGetall('Userinfo:uid2'));
        // dump($this->redis->del('Userinfo:uid3'));
        // dump(A('Home/User')->getUserinfoData(3));
        // dump($this->redis->del('Userinfo:uid9'));
        // dump(A('Home/User')->getUserinfoData(9));
        // 
        $res = D('userinfo as u') -> join('__USER_TAGS__ as ut ON u.uid = ut.uid')->where('ut.tid = 1 and u.uid in (1,3,2,4,9) and u.sex=1')->field('u.uid')->limit(0)->select();
        //D('userinfo')->getSearchList();
        dump($res);
        $res1 = D('userinfo as u') -> join('')->where(' u.uid in (1,3,2,4,9) and u.sex=1')->field('u.uid')->limit(0)->select();
        dump($res1);
        die();
    }


}
