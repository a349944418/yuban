<?php
namespace Home\Controller;

Class PublicController extends BaseController 
{
    /**
     * 获取母语语言列表
     * @return [type] [description]
     */
    public function getBaseLanguage() {
        $this->return['data']['language'] = F('baseLanguage');

        if(!$this->return['data']['language']) {
            $this->return['data']['language'] = D('language')->where('type=1')->field('lid, language_name')->select();
            F('baseLanguage', $this->return['data']['language']);
        }

        $this->goJson($this->return);
    }

    /* 文件上传 */
    public function upload(){
        /* 调用文件上传组件上传文件 */
        $File = D('File');
        $file_driver = C('DOWNLOAD_UPLOAD_DRIVER');

        $info = $File->upload(
            $_FILES,
            C('DOWNLOAD_UPLOAD'),
            C('DOWNLOAD_UPLOAD_DRIVER'),
            C("UPLOAD_{$file_driver}_CONFIG")
        );

        /* 记录附件信息 */
        if($info){
            $this->return['message'] = L('upload_success');
            $this->return['data']['path'] = '/Uploads/File/'.$info['savepath'].$info['savename'];
            $this->return['data']['fid'] = $info['id'];
        } else {
            $this->return['code'] = 1001;
            $this->return['message'] = $File->getError();
        }
        /* 返回JSON数据 */
        $this->goJson($this->return);
    }

    /**
     * 上传图片
     * @author huajie <banhuajie@163.com>
     */
    public function uploadPicture(){
        //TODO: 用户登录检测

        /* 调用文件上传组件上传文件 */
        $Picture = D('Picture');
        $pic_driver = C('PICTURE_UPLOAD_DRIVER');
        $info = $Picture->upload(
            $_FILES,
            C('PICTURE_UPLOAD'),
            C('PICTURE_UPLOAD_DRIVER'),
            C("UPLOAD_{$pic_driver}_CONFIG")
        ); //TODO:上传到远程服务器

        /* 记录图片信息 */
        if($info){
            $this->return['code'] = 0;
            $this->return['message'] = L('upload_success');
            $this->return['data']['path'] = $info['file']['path'];
            $this->return['data']['pid'] = $info['file']['id'];
        } else {
            $return['code'] = 1001;
            $this->return['message'] = $Picture->getError();
        }

        /* 返回JSON数据 */
        $this->goJson($this->return);
    }
}
?>
