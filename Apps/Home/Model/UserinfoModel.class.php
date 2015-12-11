<?php
namespace Home\Model;
use Think\Model;

class UserinfoModel extends Model 
{
    protected $redis = '';

    public function _initialize()
    {
        import("Common.Util.RedisPool");
        $this->redis = \RedisPool::getconnect();
    }

	/**
	 * 获取uid
	 * @param  [string] $mobile 手机号
	 * @return [string]         uid
	 */
	public function getUid($mobile)
	{
		$uid = $this->where('mobile="'.$mobile.'"')->getField('uid');
		return $uid;
	}

	/**
	 * 获取用户信息
	 * @param  [int] $uid 用户id
	 * @return [array]    用户信息
	 */
	public function getUserInfo($uid)
	{
		$res = $this->where('uid='.$uid)->find();
        //音频介绍介绍   
        if($res['audio_profile']) {
            $audio_profile['rid'] = $res['audio_profile'];
            $audio = D('file')->field('savepath, savename')->where('id='.$res['audio_profile'])->find();
            $audio_profile['url'] = C('WEBSITE_URL').'/Uploads/File/'.$audio['savepath'].$audio['savename'];
        } else {
            $audio_profile = array();
        }
        $res['audio_profile'] = json_encode($audio_profile, JSON_UNESCAPED_UNICODE);
        
        //视频介绍地址
        if($res['video_profile']) {
            $video_profile['rid'] = $res['video_profile'];
            $video = D('file')->field('savepath, savename')->where('id='.$res['video_profile'])->find();
            $video_profile['url'] = C('WEBSITE_URL').'/Uploads/File/'.$video['savepath'].$video['savename']; 
        }else{
            $video_profile = array();
        }
        $res['video_profile'] = json_encode($video_profile, JSON_UNESCAPED_UNICODE);
        //头像原图
        if($res['headimg']) {
            $photo_arr = explode(',', $res['headimg']);
            foreach($photo_arr as $v) {
                $photo_res = array();
                $photo_res['rid'] = $v;
                $photo_res['url'] = C('WEBSITE_URL').(D('picture')->where('id='.$v)->getField('path'));
                $photo[] = $photo_res;
            }
        }else{
            $photo = array();
        }
        $res['headimg'] = json_encode($photo, JSON_UNESCAPED_UNICODE);

        //用户语言
        $res['language'] = D('userLanguage')->field('lid, type, self_level, sys_level')->where('uid='.$uid)->select();
        if($res['language']) {            
            $allLanguage = D('language')->getAllLanguage();
            foreach($res['language'] as $k=>$v) {
                $res['language'][$k]['language_name'] = $allLanguage[ $v['lid'] ];
            }           
        }else{
            $res['language'] = array();
        }
        $res['language'] = json_encode($res['language'], JSON_UNESCAPED_UNICODE);
        
        //用户标签
        $usertags = D('userTags')->field('tid')->where('uid='.$uid)->select();
        if($usertags){
            $allTags = D('tags')->getAllTags();
            foreach($usertags as $k=>$v){
                $usertags[$k]['tag_name'] = $allTags[$v['tid']];
            }
        } else {
            $usertags = array();
        }
        $res['tags'] = json_encode($usertags, JSON_UNESCAPED_UNICODE);
        $res['level'] = round($res['level']);
		return $res;
	}

    /**
     * 搜索用户
     * @return [type] [description]
     */
    public function getSearchList($uid, $field = '*', $search = '', $whereRand = '')
    {
        $where = $join = '';
        //昵称筛选
        if ($search['uname']) {
            $where .= ' u.search_key like "%'.$search['uname'].'%" and';
        }

        //性别筛选
        if ($search['sex']) {
            $where .= ' u.sex='.I($search['sex']).' and';
        }
        //等级筛选
        if ($search['min_level']) {
            $where .= ' u.level >='.$search['min_level'].' and';
        } 
        if ($search['max_level']) {
            $where .= ' u.level <='.$search['max_level'].' and';
        }

        //地区筛选
        if ($search['city']) {
            $where .= ' u.city = '.$search['city'].' and';
        } elseif ($search['province']) {
            $where .= ' u.province ='.$search['province'].' and';
        } elseif ($search['country']) {
            $where .= ' u.country ='.$search['country'].' and';
        }

        //语言筛选
        if ($search['lid']) {
            $where .= ' u.cur_language='.$search['lid'].' and';
        }

        //时长筛选
        if ($search['time']) {
            $where .= ' u.spoken_long >='.$search['time'].' and';
        }

        //付费筛选
        if ($search['price']) {
            $where .= ' u.price >= 0.1 and';
        }

        //音频视频筛选
        if ($search['intro'] == 1) {
            $where .= ' u.audio_profile != 0 and';
        } elseif ($search['intro'] == 2) {
            $where .= ' u.video_profile != 0 and';
        }
        //标签搜索
        if ($search['tid']) {
            $where .= ' ut.tid = '.$search['tid'].' and';
            $join .= '__USER_TAGS__ as ut ON u.uid = ut.uid';
        }
        //范围搜索
        if ($search['range'] == 'yuban') {
            if(isset($search['isfriend'])) {
                if($search['isfriend']) {
                    $map['from_id'] = array('eq',$uid);
                    $map['type'] = array('eq', 3);
                } else {
                    $map['from_id'] = array('eq',$uid);
                    $map['type'] = array('eq', 2);
                }
                $res = D('friend')->getFriend($map);
                foreach($res as $v){
                    $follow[] = $v['to_id']; 
                }
            } else {
                $follow = $this->redis->sMembers('Userinfo:friend2:'.$uid);
                if (!count($follow)) {
                    $map['from_id'] = array('eq',$uid);
                    $map['type'] = array('in', array(2,3));
                    $res = D('friend')->getFriend($map);
                    foreach($res as $v){
                        $follow[] = $v['to_id']; 
                        $this->redis->SADD('Userinfo:friend2:'.$uid, $v['to_id']);
                    }
                }               
            }
            $uids = join(',', $follow);
            $where .= ' u.uid in ("'.$uids.'") and';
        } else {
            $follow = $this->redis->sMembers('Userinfo:friend1:'.$uid);
            if (!count($follow)) {
                $map['from_id'] = array('eq',$uid);
                $map['type'] = array('in', array(1,3));
                $res = D('friend')->getFriend($map);
                foreach($res as $v){
                    $follow[] = $v['to_id']; 
                    $this->redis->SADD('Userinfo:friend1:'.$uid, $v['to_id']);
                }
            }
            $uids = join(',', $follow);
            $where .= ' u.uid not  in ("'.$uids.'") and';
        }


        $where .= ' u.uname != "" and u.uid != '.$uid.$whereRand ;

        if($search['pageSize']) {
            $data['index'] = $search['index'] ? $search['index'] : 1;
            $data['pageSize'] = $search['pageSize'];
            $start = ($data['index']-1)*$data['pageSize'];
            $limit = $start.','.$data['pageSize'];
        } else {
            $limit = 0;
        } 

        $data['totalCount'] = M('userinfo as u')->where($where)->count('u.uid');
        $data['ulist'] = M('userinfo as u')->join($join)->field($field)->where($where)->limit($limit)->select();
        if($search['index']) {
            $data['index'] = $search['index'];
            $data['pageSize'] = $search['pageSize'];
        }
        //dump(M('userinfo as u')->getLastSql());

        return $data;
    }
}
?>