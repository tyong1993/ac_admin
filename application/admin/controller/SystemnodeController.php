<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/6/5
 * Time: 9:30
 */

namespace app\admin\controller;


use app\admin\model\SystemNodeModel;

class SystemnodeController extends BaseController
{
    /**
     * 权限节点列表
     */
    function index(){
        if($this->request->isAjax()){
            $list=db('system_node')->select();
            return json(["code"=>0,"msg"=>"success","count"=>count($list),"data"=>$list]);
        }
        return $this->fetch();
    }

    /**
     * 添加节点
     */
    function add(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            db('system_node')->insert($param);
            return jsonSuccess();
        }
        $pid=$this->request->param('pid');
        $selectList=SystemNodeModel::getSelectList();
        $this->assign("pid",$pid);
        $this->assign("selectList",$selectList);
        return $this->fetch();
    }
    /**
     * 编辑节点
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            db('system_node')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('node_id');
        $node=db("system_node")->find($id);
        $selectList=SystemNodeModel::getSelectList();
        $this->assign("row",$node);
        $this->assign("selectList",$selectList);
        return $this->fetch();
    }
    /**
     * 删除节点
     */
    function delete(){
        $id=$this->request->param('node_id');
        db('system_node')->delete($id);
        return jsonSuccess();
    }
}