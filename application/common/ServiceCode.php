<?php
/**
 * Created by PhpStorm.
 * User: tyong
 * Date: 2020/6/7
 * Time: 13:27
 */

namespace app\common;

/**
 * 业务状态码
 * Class ServiceCode
 * @package app\common
 */
class ServiceCode
{
    /**
     * 业务成功
     */
    const SUCCESS = "20000";
    /**
     * 业务失败
     */
    const FAIL = "10000";
    /**
     * 未登录
     */
    const NOT_LOGIN = "10001";
}