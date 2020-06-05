<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/6/4
 * Time: 14:23
 */

namespace app\admin\controller;


use app\admin\model\SystemNodeModel;
use think\Controller;

class IndexController extends Controller
{
    function index(){
        return $this->fetch();
    }

    function welcome(){
        return $this->fetch();
    }

    function getSystemInit(){
        $initData=[
            "homeInfo"=>[
                "title"=>"首页",
                "href"=>"admin/Index/welcome.html?t=1",
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
}