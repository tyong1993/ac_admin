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

class SystemNodeVilldate extends Validate
{
    protected $rule =   [
        'title'  => 'require',
        'auth_tag'   => 'require|authTagUnique',
    ];

    protected $message  =   [
        'title.require' => '节点名称必填',
        'auth_tag.require'   => '权限标识必填'
    ];
    /**
     * 权限标识唯一
     */
    protected function authTagUnique($value,$rules,$param){
        $db=db('system_node')->where("auth_tag","=",$param["auth_tag"]);
        //更新时
        if(!empty($param["id"])){
            $db->where("id","neq",$param["id"]);
        }
        $res=$db->count();
        if($res){
            throw new ServiceException("该权限标识已存在");
        }
        return true;
    }
}