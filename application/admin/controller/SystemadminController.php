<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/6/8
 * Time: 9:48
 */

namespace app\admin\controller;


use app\admin\model\SystemAdminModel;
use app\admin\villdate\SystemAdminVilldate;
use app\common\exception\ServiceException;

class SystemadminController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $username=$this->request->param("username");
            $db=db('system_admin');
            //去掉超管
            $db->where("id","gt",1);
            if(!empty($username)){
                $db->where("username","like","%$username%");
            }
            $res = $db->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
            }
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
            $param["role_ids"]=!empty($param["role_ids"])?implode(',',$param["role_ids"]):"";
            $this->validate($param,SystemAdminVilldate::class);
            unset($param["re_password"]);
            $param["salt"] = createRandomStr();
            $param["password"] = createPassword($param["password"],$param["salt"]);
            $param["create_time"] = time();
            db('system_admin')->insert($param);
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
            $param["role_ids"]=!empty($param["role_ids"])?implode(',',$param["role_ids"]):"";
            $this->validate($param,SystemAdminVilldate::class.".edit");
            if(empty($param["password"])){
                unset($param["password"]);
             }
            unset($param["re_password"]);
            db('system_admin')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("system_admin")->find($id);
        $row["role_ids"] = explode(",",$row["role_ids"]);
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
        if(in_array(1,$id_arr)){
            return jsonFail("超级管理员不可以删除");
        }
        db('system_admin')->where("id","in",$id_arr)->delete();
        return jsonSuccess();
    }
    /**
     * 修改密码
     */
    function updatePassword(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $db=db('system_admin');
            $admin = $db->find(session("admin_id"));
            if(!checkPassword($param["password"],$admin["password"],$admin["salt"])){
                return jsonFail("原密码错误");
            }
            if(strlen($param["new_password"]) <6){
                throw new ServiceException("密码长度不能小于6个字符");
            }
            if($param["new_password"] != $param["re_new_password"]){
                throw new ServiceException("两次密码输入不一致");
            }
            $admin["salt"] = createRandomStr();
            $admin["password"] = createPassword($param["new_password"],$admin["salt"]);
            db('system_admin')->update($admin);
            SystemAdminModel::loginOut();
            return jsonSuccess();
        }
        return $this->fetch();
    }

    /**
     * 添加和删除需要关联的数据
     */
    private function assign2AddAndEdit($param=[]){
        $roles=db("system_role")->where("status","=",1)->select();
        $this->assign("roles",$roles);
    }

}