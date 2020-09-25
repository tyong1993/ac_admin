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

class CustomerSupplierStaffVilldate extends Validate
{
    protected $rule =   [
        'name'   => 'require',
        'phone'   => 'require',
    ];

    protected $message  =   [
        'name.require' => '姓名必填',
        'phone.require' => '手机号必填',
    ];
}