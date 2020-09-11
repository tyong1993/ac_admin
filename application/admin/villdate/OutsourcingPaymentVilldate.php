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

class OutsourcingPaymentVilldate extends Validate
{
    protected $rule =   [
        'contract_id'   => 'require',
        'periods'   => 'require',
        'pay_amount'   => 'require',
        'sales_collection_id'   => 'require',
    ];

    protected $message  =   [
        'contract_id.require' => '外包合同ID不能为空',
        'periods.require' => '期数必填',
        'pay_amount.require' => '付款金额必填',
        'sales_collection_id.require' => '收款期数必选',
    ];
}