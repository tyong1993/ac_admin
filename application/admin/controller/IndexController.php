<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/6/4
 * Time: 14:23
 */

namespace app\admin\controller;


use app\admin\model\SystemAdminModel;
use app\admin\model\SystemNodeModel;

class IndexController extends BaseController
{
    protected $notNeedLoginFun=["login"];

    function index(){
        return $this->fetch();
    }

    function welcome(){
        return $this->fetch();
    }

    /**
     * 初始化数据
     */
    function getSystemInit(){
        $initData=[
            "homeInfo"=>[
                "title"=>"首页",
                "href"=>"admin/Index/welcome",
            ],
            "logoInfo"=>[
                "title"=>"AC Admin",
                "image"=>"/static/admin/images/logo.png",
                "href"=>"",
            ],
            "menuInfo"=>SystemNodeModel::getMenuList()
        ];
        return json($initData);
    }

    /**
     * 登录
     */
    function login(){
        if($this->request->isPost()){
            $username=$this->request->param("username");
            $password=$this->request->param("password");
            $admin = db("system_admin")->where("username","=",$username)->find();
            if(empty($admin)){
                return jsonFail("账号或密码错误");
            }
            if(!checkPassword($password,$admin["password"],$admin["salt"])){
                return jsonFail("账号或密码错误");
            }
            session("admin_id",$admin["id"]);
            session("admin_username",$admin["username"]);
            return jsonSuccess();
        }
        return $this->fetch();
    }

    /**
     * 登出
     */
    function logout(){
        SystemAdminModel::loginOut();
        return jsonSuccess();
    }

    /**
     * 清理服务端缓存
     */
    function cleanCache(){
        cache('cleanable_ache',[]);
        return jsonSuccess([],"服务端缓存已清除",1);
    }
}