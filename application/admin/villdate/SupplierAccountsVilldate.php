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

class SupplierAccountsVilldate extends Validate
{
    protected $rule =   [
        'c_s_id'   => 'require',
        'payee'   => 'require',
        'bank_account'   => 'require',
        'bank_name'   => 'require',
    ];

    protected $message  =   [
        'c_s_id.require' => '客户/供应商ID不能为空',
        'payee.require' => '收款人必填',
        'bank_account.require' => '银行账号必填',
        'bank_name.require' => '银行名称必填',
    ];
}