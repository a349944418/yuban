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
}
?>