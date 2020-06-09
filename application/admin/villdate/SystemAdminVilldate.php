<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/6/9
 * Time: 10:56
 */

namespace app\admin\villdate;


use app\common\exception\ServiceException;
use think\Validate;

class SystemAdminVilldate extends Validate
{
    protected $rule =   [
        'username'  => 'require|checkUsername',
        'password'   => 'require',
        're_password'   => 'require',
    ];

    protected $message  =   [
        'username.require' => '用户名必填',
        'password.require'   => '密码必填',
        're_password.require'   => '确认密码必填'
    ];
    protected $scene = [
        'edit'  =>  ['username'],
    ];
    /**
     * 用户名唯一
     * 确认密码
     */
    protected function checkUsername($value,$rules,$param){
        //用户名唯一
        $db=db('system_admin')->where("username","=",$param["username"]);
        //更新时
        if(!empty($param["id"])){
            $db->where("id","neq",$param["id"]);
        }
        $res=$db->count();
        if($res){
            throw new ServiceException("该用户名已存在");
        }
        //确认密码
        if(!empty($param["password"])){
            if(strlen($param["password"]) <6){
                throw new ServiceException("密码长度不能小于6个字符");
            }
            if($param["password"] != $param["re_password"]){
                throw new ServiceException("两次密码输入不一致");
            }
        }
        return true;
    }
}