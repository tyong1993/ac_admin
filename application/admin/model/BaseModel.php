<?php
/**
 * Created by PhpStorm.
 * User: tyong
 * Date: 2020/6/7
 * Time: 13:58
 */

namespace app\admin\model;


use app\admin\controller\BaseController;
use think\Model;

class BaseModel extends Model
{
    /**
     * 获取员工可选项
     */
    public static function getAdmins(){
        $res = db('system_admin')->where("status","eq",1)->where("id","neq",1)->select();
        return $res;
    }
    /**
     * 获取商务联系人可选项
     */
    public static function getBusinessContacts(){
        $business_contacts = db("customer_supplier_staff")->where("type","in",[1,3])->select();
        return $business_contacts;
    }
}