<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * json成功返回
 */
function jsonSuccess($data=[],$msg="操作成功",$code="20000"){
    return json(['data' => $data, 'msg' => $msg, 'code' => $code]);
}

/**
 * json失败返回
 */
function jsonFail($msg="操作失败",$code="10000",$data=[]){
    return json([ 'msg' => $msg,'code' => $code, 'data' => $data]);
}