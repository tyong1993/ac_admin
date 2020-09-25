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

class GroupCompanyVilldate extends Validate
{
    protected $rule =   [
        'name'   => 'require',
    ];

    protected $message  =   [
        'name.require' => '单位名称必填',
    ];
}