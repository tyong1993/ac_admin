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

class SalesContractVilldate extends Validate
{
    protected $rule =   [
        'contract_name'   => 'require',
        'contract_amount'   => 'require',
        'company_identifier'   => 'require',
        'c_s_id'   => 'require',
        'g_c_id'   => 'require',
    ];

    protected $message  =   [
        'contract_name.require' => '合同名称必填',
        'contract_amount.require' => '合同金额必填',
        'company_identifier.require' => '公司合同编号必填',
        'c_s_id.require' => '客户单位必选',
        'g_c_id.require' => '签约单位必选',
    ];
}