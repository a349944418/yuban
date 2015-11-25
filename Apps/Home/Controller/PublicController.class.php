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

    /**
     * 获取标签
     * @return [type] [description]
     */
    public function getTags() {
        $this->return['data']['tags'] = F('tags');
        if(!$this->return['data']['tags']) {
            $this->return['data']['tags'] = D('tags')->field('tid, tag_name')->select();
            F('tags', $this->return['data']['tags']);
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
            if(!$info['file']) {
                foreach($info as $v){
                    $this->return['data']['file'][] = array('url'=>C('WEBSITE_URL').'/Uploads/File/'.$v['savepath'].$v['savename'], 'rid'=>$v['id']);

                }
            } else {
                $this->return['data']['file'][] = array('url'=>C('WEBSITE_URL').'/Uploads/File/'.$info['file']['savepath'].$info['file']['savename'], 'rid'=>$info['file']['id']);
            }
            $this->return['message'] = L('upload_success');
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
            if(!$info['file']){
                foreach($info as $v){
                    $this->return['data']['pic'][] = array('url'=>C('WEBSITE_URL').$v['path'], 'rid'=>$v['id']);
                }
            } else {
                $this->return['data']['pic'][] = array('url'=>C('WEBSITE_URL').$info['file']['path'], 'rid'=>$info['file']['id']);
            }
            $this->return['message'] = L('upload_success');          
        } else {
            $return['code'] = 1003;
            $this->return['message'] = $Picture->getError();
        }

        /* 返回JSON数据 */
        $this->goJson($this->return);
    }
}
?>