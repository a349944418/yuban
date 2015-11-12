<?php
namespace Home\Controller;

Class PublicController extends BaseController 
{
    /**
     * 获取母语语言列表
     * @return [type] [description]
     */
    public function getBaseLanguage() {
        $this->return['data'] = F('baseLanguage');

        if(!$this->return['data']) {
            $this->return['data'] = D('language')->where('type=1')->field('lid, language_name')->select();
            F('baseLanguage', $this->return['data']);
        }

        $this->goJson($this->return);
    }
}
?>