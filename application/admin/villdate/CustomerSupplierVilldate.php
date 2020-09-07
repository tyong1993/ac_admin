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

class CustomerSupplierVilldate extends Validate
{
    protected $rule =   [
        'company_name'   => 'require',
    ];

    protected $message  =   [
        'company_name.require' => '公司名称必填',
    ];
}