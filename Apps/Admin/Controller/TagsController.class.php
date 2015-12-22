<?php
/*
 * 标签
 */
namespace Admin\Controller;

/**
 * 标签
 * @author huajie <banhuajie@163.com>
 */
class TagsController extends AdminController {

    /**
     * 行为日志列表
     * @author huajie <banhuajie@163.com>
     */
    public function index(){
    	$tree = D('Tags')->getTree(0,'tid, tag_name, pid, status');
        $this->assign('tree', $tree);
        C('_SYS_GET_TAGS_TREE_', true); //标记系统获取分类树模板
        $this->meta_title = '标签管理';
        $this->display();
    }

    /**
     * 显示分类树，仅支持内部调
     * @param  array $tree 分类树
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function tree($tree = null){
        C('_SYS_GET_TAGS_TREE_') || $this->_empty();
        $this->assign('tree', $tree);
        $this->display('tree');
    }

    /**
     * 编辑标签
     * @param  [type]  $id  [description]
     * @param  integer $pid [description]
     * @return [type]       [description]
     */
    public function edit($id = null, $pid = 0){
        $tags = D('Tags');

        if(IS_POST){ //提交表单
            if(false !== $tags->update()){
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $tags->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $tag = '';
            if($pid){
                /* 获取上级分类信息 */
                $tag = $tags->info($pid, 'tid, tag_name, pid, status');
                if(!($tag && 1 == $tag['status'])){
                    $this->error('指定的上级标签不存在或被禁用！');
                }
            }

            /* 获取标签信息 */
            $info = $id ? $tags->info($id) : '';

            $this->assign('info',       $info);
            $this->assign('tags',   $tag);
            $this->meta_title = '编辑标签';
            $this->display();
        }
    }

    /**
     * 新增标签
     */
    public function add($pid = 0) {
    	$tags = D('Tags');

        if(IS_POST){ //提交表单
            if(false !== $tags->update()){
                $this->success('新增成功！', U('index'));
            } else {
                $error = $tags->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $tag = array();
            if($pid){
                /* 获取上级分类信息 */
                $tag = $tags->info($pid, 'tid, tag_name, pid, status');
                if(!($tag && 1 == $tag['status'])){
                    $this->error('指定的上级标签不存在或被禁用！');
                }
            }

            /* 获取分类信息 */
            $this->assign('tags', $tag);
            $this->meta_title = '新增分类';
            $this->display('edit');
        }
    }

    /**
     * 删除一个标签
     * @author huajie <banhuajie@163.com>
     */
    public function remove(){
        $tid = I('id');
        if(empty($tid)){
            $this->error('参数错误!');
        }

        //判断该分类下有没有子分类，有则不允许删除
        $child = M('Tags')->where(array('pid'=>$tid))->field('tid')->select();
        if(!empty($child)){
            $this->error('请先删除该标签下的子标签');
        }

        //删除该分类信息
        $res = M('Tags')->delete($tid);
        if($res !== false){
            //记录行为
            action_log('update_tags', 'tags',  $tid, UID);
            $this->success('删除标签成功！');
        }else{
            $this->error('删除标签失败！');
        }
    }
}