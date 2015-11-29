<?php
 /**
  * LBS核心类
  * @author name <simplephp@163.com>
  */
include_once('./Geohash.class.php');
 
class LBS 
{
    //索引长度 6位
    protected $index_len = 6;
    protected $redis;
    protected $geohash;
 
    public function __construct($redis) 
    {
        //redis
        $this->redis = $redis;
        //geohash
        $this->geohash = new \Geohash();
    }

    /**
    * 更新用户信息
    * @param mixed $latitude 纬度
    * @param mixed $longitude 经度
    */
    public function upinfo($user_id,$latitude,$longitude) 
    {
        //原数据处理
        //获取原Geohash
        $o_hashdata = $this->redis->hGet($user_id,'geo');
        if (!empty($o_hashdata)) {
            //原索引
            $o_index_key = substr($o_hashdata, 0, $this->index_len);
            //删除
            $this->redis->sRem($o_index_key,$user_id);
        }
        //新数据处理
        //纬度
        $this->redis->hSet($user_id,'lati',$latitude);
        //经度
        $this->redis->hSet($user_id,'longi',$longitude);

        $this->redis->hSet($user_id, 'ctime', time());
        //Geohash
        $hashdata = $this->geohash->encode($latitude,$longitude);
        $this->redis->hSet($user_id,'geo',$hashdata);
        //索引
        $index_key = substr($hashdata, 0, $this->index_len);
        //存入
        $this->redis->sAdd($index_key,$user_id);
        return true;
    }
    
    /**
    * 获取附近用户
    * @param mixed $latitude 纬度
    * @param mixed $longitude 经度
    */
    public function serach($latitude,$longitude) {
        //Geohash
        $hashdata = $this->geohash->encode($latitude,$longitude);
        //索引
        $index_key = substr($hashdata, 0, $this->index_len);
        //取得
        $user_id_array = $this->redis->sMembers($index_key);
        return $user_id_array;
    }
}
?>