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
        $controller = strtolower($this->request->controller());
        $action = strtolower($this->request->action());
        $authTag = $controller . '/' . $action;;
        if($this->isNeedLogin && !in_array($action,$this->notNeedLoginFun)){
            //检测登陆
            if(empty(session("admin_id"))){
//                $this->redirect(url("Index/login"));
                throw new ServiceException("请先登陆",ServiceCode::NOT_LOGIN);
            }
            //检测权限
            SystemAdminModel::checkAuth($authTag);
        }
    }

}