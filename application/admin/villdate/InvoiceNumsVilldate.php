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

class InvoiceNumsVilldate extends Validate
{
    protected $rule =   [
        'i_r_id'   => 'require',
        'num'   => 'require',
        'amount'   => 'require',
    ];

    protected $message  =   [
        'i_r_id.require' => '开票记录ID不能为空',
        'num.require' => '发票号必填',
        'amount.require' => '发票金额必填',
    ];
}