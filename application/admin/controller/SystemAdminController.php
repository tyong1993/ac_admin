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

class SystemAdminController extends BaseController
{
    protected $table = "system_admin";
    protected $table_name = "用户";
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
                $val["role_name"] = db("system_role")->where("id","in",explode(",",$val["role_ids"]))->column("role_name");
                $val["role_name"] = implode(",",$val["role_name"]);
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
            $id=db($this->table)->insert($param,false,true);
            if($id){
                self::actionLog(1,$this->table,$this->table_name,$id);
            }
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
            $row = db($this->table)->find($param["id"]);
            if(db($this->table)->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$row["id"],$row);
            }
            cache('cleanable_cache',[]);
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
        foreach ($id_arr as $id){
            $row = db($this->table)->find($id);
            if(!empty($row)){
                self::actionLog(3,$this->table,$this->table_name,$id,$row);
                db($this->table)->where("id","eq",$id)->delete();
            }
        }
        cache('cleanable_cache',[]);
        return jsonSuccess();
    }
    /**
     * 修改密码
     */
    function updatePassword(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $admin = self::$login_admin;
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
     * 添加和编辑需要关联的数据
     */
    private function assign2AddAndEdit($param=[]){
        $roles=db("system_role")->where("status","=",1)->select();
        $this->assign("roles",$roles);
    }

}