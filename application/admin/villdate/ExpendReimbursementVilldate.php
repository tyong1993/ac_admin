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

class ExpendReimbursementVilldate extends Validate
{
    protected $rule =   [
        'contract_id'   => 'require',
        'collection_id'   => 'require',
        'bx_people_id'   => 'require',
        'amount'   => 'require',
    ];

    protected $message  =   [
        'contract_id.require' => '销售合同必选',
        'collection_id.require' => '收款期数必选',
        'bx_people_id.require' => '报销人必选',
        'amount.require' => '报销金额必填',
    ];
}