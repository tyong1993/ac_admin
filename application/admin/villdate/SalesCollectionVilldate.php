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

class SalesCollectionVilldate extends Validate
{
    protected $rule =   [
        'contract_id'   => 'require',
        'periods'   => 'require',
        'collection_amount'   => 'require',
    ];

    protected $message  =   [
        'contract_id.require' => '销售合同ID不能为空',
        'periods.require' => '期数必填',
        'collection_amount.require' => '收款金额必填',
    ];
}