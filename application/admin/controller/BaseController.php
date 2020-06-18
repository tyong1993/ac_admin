<?php
/**
 * Created by PhpStorm.
 * User: tyong
 * Date: 2020/6/7
 * Time: 10:30
 */

namespace app\admin\controller;


use app\admin\model\SystemAdminModel;
use app\common\exception\ServiceException;
use app\common\ServiceCode;
use think\Controller;

class BaseController extends Controller
{
    /**
     * 控制器是否需要检测登陆
     */
    protected $isNeedLogin = true;
    /**
     * 控制器中不需要检测登陆的方法
     */
    protected $notNeedLoginFun = [];
    /**
     * 验证失败是否抛出异常
     */
    protected $failException = true;
    /**
     * 初始化
     */
    protected function initialize()
    {
        $controller = $this->request->controller();
        $action = $this->request->action();
        $authTag = $controller . '/' . $action;;
        $notNeedLoginFun = array_map("strtolower",$this->notNeedLoginFun);
        if($this->isNeedLogin && !in_array(strtolower($action),$notNeedLoginFun)){
            //检测登陆
            if(empty(session("admin_id"))){
                if($this->request->isAjax()){
                    throw new ServiceException("请先登陆",ServiceCode::NOT_LOGIN);
                }else{
                    $this->redirect(url("Index/login"));
                }
            }
            //检测权限
            SystemAdminModel::checkAuth($authTag);
        }
    }

}