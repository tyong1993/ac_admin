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

class InvoiceRecordsVilldate extends Validate
{
    protected $rule =   [
        'invoice_num'   => 'require',
    ];

    protected $message  =   [
        'invoice_num.require' => '发票号必填',
    ];
}