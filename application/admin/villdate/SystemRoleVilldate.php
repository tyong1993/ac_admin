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

class SystemRoleVilldate extends Validate
{
    protected $rule =   [
        'role_name'  => 'require|checkRoleName',
    ];

    protected $message  =   [
        'role_name.require' => '角色名必填',
    ];

    /**
     * 角色名唯一
     */
    protected function checkRoleName($value,$rules,$param){
        //用户名唯一
        $db=db('system_role')->where("role_name","=",$param["role_name"]);
        //更新时
        if(!empty($param["id"])){
            $db->where("id","neq",$param["id"]);
        }
        $res=$db->count();
        if($res){
            throw new ServiceException("该角色名已存在");
        }
        return true;
    }
}