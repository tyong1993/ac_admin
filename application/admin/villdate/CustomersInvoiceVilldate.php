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

class CustomersInvoiceVilldate extends Validate
{
    protected $rule =   [
        'c_s_id'   => 'require',
        'title'   => 'require',
        'tax_num'   => 'require',
        'address'   => 'require',
        'phone'   => 'require',
        'bank_account'   => 'require',
        'bank_name'   => 'require',
    ];

    protected $message  =   [
        'c_s_id.require' => '客户/供应商ID不能为空',
        'title.require' => '抬头必填',
        'tax_num.require' => '税号必填',
        'address.require' => '地址必填',
        'phone.require' => '电话必填',
        'bank_account.require' => '银行账号必填',
        'bank_name.require' => '银行名称必填',
    ];
}