<?php
namespace Home\Controller;

Class PublicController extends BaseController 
{
    /**
     * 获取母语语言列表
     * @return [type] [description]
     */
    public function getBaseLanguage() {
        //$this->return['data']['language'] = F('baseLanguage');

        if(!$this->return['data']['language']) {
            $this->return['data']['language'] = D('language')->where('type=1')->field('lid, language_name')->select();
            F('baseLanguage', $this->return['data']['language']);
        }

        $this->goJson($this->return);
    }
}
?>
