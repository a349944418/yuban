<?php
namespace Home\Model;
use Think\Model;

class UserinfoModel extends Model 
{

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
        $audio = D('file')->field('savepath, savename')->where('id='.$res['audio_profile'])->find();
        $res['audio_profile_src'] = '/Uploads/File/'.$audio['savepath'].$audio['savename'];
        //视频介绍地址
        $video = D('file')->field('savepath, savename')->where('id='.$res['video_profile'])->find();
        $res['video_profile_src'] = '/Uploads/File/'.$video['savepath'].$video['savename'];
        //头像原图
        $res['headimg_src'] = D('picture')->where('id='.$res['headimg'])->getField('path');
        //其它头像地址
        $photo_arr = explode(',', $res['photo']);
        foreach($photo_arr as $v) {
            $photo_res = array();
            $photo_res['pid'] = $v;
            $photo_res['path'] = D('picture')->where('id='.$v)->getField('path');
            $photo[] = $photo_res;
        }
        $res['photo'] = json_encode($photo, JSON_UNESCAPED_UNICODE);
        //用户语言
        $res['language'] = D('userLanguage')->field('lid, type, self_level, sys_level')->where('uid='.$res['uid'])->select();
        $allLanguage = D('language')->getAllLanguage();
        
        foreach($res['language'] as $k=>$v) {
            $res['language'][$k]['lname'] = $allLanguage[ $v['lid'] ];
        }
        $res['language'] = json_encode($res['language'], JSON_UNESCAPED_UNICODE);

        //用户标签
        $usertags = D('userTags')->field('tid')->where('uid='.$res['uid'])->select();
        if($usertags){
            $allTags = D('tags')->getAllTags();
            foreach($usertags as $k=>$v){
                $usertags[$k]['tname'] = $allTags[$v['tid']];
            }
        } else {
            $usertags = array();
        }
        $res['tags'] = json_encode($usertags, JSON_UNESCAPED_UNICODE);
		return $res;
	}
}
?>