<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/6/5
 * Time: 9:30
 */

namespace app\admin\controller;


use app\admin\model\SystemNodeModel;
use app\admin\villdate\SystemNodeVilldate;
use think\Db;

class SystemNodeController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $list=db('system_node')->select();
            return json(["code"=>0,"msg"=>"success","count"=>count($list),"data"=>$list]);
        }
        return $this->fetch();
    }

    /**
     * 添加
     */
    function add(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,SystemNodeVilldate::class);
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
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,"app\admin\\villdate\SystemNodeVilldate");
            db('system_node')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("system_node")->find($id);
        $selectList=SystemNodeModel::getSelectList();
        $this->assign("row",$row);
        $this->assign("selectList",$selectList);
        return $this->fetch();
    }
    /**
     * 删除
     */
    function delete(){
        $id=$this->request->param('id');
        if(db('system_node')->where("pid","=",$id)->count()){
            return jsonFail("该节点存在子节点,不可以删除");
        }
        //删除节点同时删除节点权限数据
        Db::startTrans();
        Db::name('system_role_node')->where("node_id","=",$id)->delete();
        Db::name('system_node')->delete($id);
        Db::commit();
        return jsonSuccess();
    }
    /**
     * 获取节点数据供授权使用
     */
    function getAuthorizeNodes(){
        $role_id=$this->request->param("role_id");
        $res=SystemNodeModel::getAuthorizeList($role_id);
        return jsonSuccess($res);
    }
}