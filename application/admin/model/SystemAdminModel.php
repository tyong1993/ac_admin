<?php
/**
 * Created by PhpStorm.
 * User: tyong
 * Date: 2020/6/7
 * Time: 11:23
 */

namespace app\admin\model;


use app\common\exception\ServiceException;

class SystemAdminModel extends BaseModel
{
    /**
     * 权限检测
     * @param $authTag
     * @throws ServiceException
     */
    public static function checkAuth($authTag){
        //id为1默认为系统超级管理员,跳过权限检测
        if(session("admin_id") != 1){
            if(in_array($authTag,SystemNodeModel::getAllAuthTags())){
                if(!in_array($authTag,SystemNodeModel::getAdminAuthTags())){
                    throw new ServiceException("无操作权限");
                }
            }
        }
    }
    /**
     * 退出登录
     */
    public static function loginOut(){
        session("admin_id",null);
        session("admin_username",null);
    }

}