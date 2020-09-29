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
use think\Db;

class BaseController extends Controller
{
    protected $table = "";
    protected $table_name = "";
    /**
     * 当前登录用户
     */
    public static $login_admin = null;
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
            self::$login_admin = db("system_admin")->find(session("admin_id"));
            //检测权限
            SystemAdminModel::checkAuth($authTag);
        }
    }
    /**
     * 数据鉴权
     */
    public static function dataPower($db = null,$power_field = ""){
        $admin = self::$login_admin;
        if($admin["id"] !== 1){
            if($admin["data_power"] !== 1){
                $db->where($power_field,"eq",$admin["id"]);
            }
        }
        return $db;
    }
    /**
     * 操作日志
     * $type:1新增,2更新,3删除
     */
    public static function actionLog($type,$table,$table_name,$object_id,$data=[],$remark=""){
        $admin = self::$login_admin;
        $insert_data = [
            "admin_id"=>$admin["id"],
            "admin_name"=>$admin["name"],
            "admin_username"=>$admin["username"],
            "type"=>$type,
            "table"=>$table,
            "table_name"=>$table_name,
            "object_id"=>$object_id,
            "create_time"=>time()
        ];
        if(!empty($data)){
            $insert_data["data"] = json_encode($data);
        }
        if(!empty($remark)){
            $insert_data["remark"] = $remark;
        }
        Db::name("system_log_action")->insert($insert_data);
    }
}