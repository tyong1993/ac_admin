<?php
namespace app\common\exception;

use app\common\ServiceCode;
use Exception;

/**
 * 业务异常类
 * Class ServiceException
 * @package app\common\exception
 */
class ServiceException extends Exception
{
    private $data;
    public function __construct($errorMsg="操作失败",$errorCode=ServiceCode::FAIL,$data=[])
    {
        parent::__construct($errorMsg,$errorCode);
        $this->data=$data;
    }
    public function getData(){
        return $this->data;
    }
}