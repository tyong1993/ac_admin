<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/6/8
 * Time: 9:48
 */

namespace app\admin\controller;


use app\admin\villdate\SystemRoleVilldate;

class SystemRoleController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $role_name=$this->request->param("role_name");
            $db=db('system_role');
            if(!empty($role_name)){
                $db->where("role_name","like","%$role_name%");
            }
            $res = $db->paginate($limit)->toArray();
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        return $this->fetch();
    }

    /**
     * 添加
     */
    function add(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,SystemRoleVilldate::class);
            db('system_role')->insert($param);
            return jsonSuccess();
        }
        $this->assign2AddAndEdit();
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,SystemRoleVilldate::class);
            db('system_role')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("system_role")->find($id);
        $this->assign("row",$row);
        $this->assign2AddAndEdit();
        return $this->fetch();
    }
    /**
     * 删除
     */
    function delete(){
        $id=$this->request->param('id');
        $id_arr=explode(",",$id);
        $id_arr=array_filter($id_arr);
        if(empty($id_arr)){
            return jsonFail("未找到需要删除的对象");
        }
        //删除角色同时删除角色权限数据
        db()->startTrans();
        db('system_role_node')->where("role_id","in",$id_arr)->delete();
        db('system_role')->where("id","in",$id_arr)->delete();
        db()->commit();
        return jsonSuccess();
    }
    /**
     * 授权
     */
    function authorize(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            //最新提交的节点
            $submit_ids=explode(",",$param["node_ids"]);
            //角色已有的节点
            $role_nodes=db("system_role_node")->where("role_id","=",$param["role_id"])->column("node_id");
            //需要新增和删除的节点
            $needAddNodes=[];$needDelNodes=[];
            foreach ($submit_ids as $key=>$val){
                if($val && !in_array($val,$role_nodes)){
                    $needAddNodes[]=["role_id" =>$param["role_id"],"node_id"=>$val];
                }
            }
            foreach ($role_nodes as $key=>$val){
                if(!in_array($val,$submit_ids)){
                    $needDelNodes[]=$val;
                }
            }
            $db=db("system_role_node");
            $db->startTrans();
            $db->insertAll($needAddNodes);
            if(!empty($needDelNodes)){
                $db->where("role_id","=",$param["role_id"])->where("node_id","in",$needDelNodes)->delete();
            }
            $db->commit();
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("system_role")->find($id);
        $this->assign("row",$row);
        return $this->fetch();
    }
    /**
     * 添加和删除需要关联的数据
     */
    private function assign2AddAndEdit($param=[]){

    }

}