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
use app\common\ServiceCode;
use app\admin\model\SystemAdminModel;
use app\common\exception\ServiceException;
/**
 * 断点打印调试函数
 * @param $data
 */
function dd($data) {
    dump($data);die;
}

/**
 * 生成随机字符串
 * $length 随机字符串长度
 * $type :
 *      0:数字字符混合
 *      1:纯字符
 *      2:纯数字
 */
function createRandomStr($length = 16, $type = 0) {
    $arr = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',1,2,3,4,5,6,7,8,9,0);
    $str  = '';
    switch($type) {
        case 0 : $s = 0;  $e = 61; break;
        case 1 : $s = 0;  $e = 51; break;
        case 2 : $s = 52; $e = 61; break;
    }
    for($i = 0; $i < $length; $i++) {
        $str .= $arr[rand($s, $e)];
    }
    return $str;
}

/**
 * 生产密码
 * @param $password
 * @return string
 */
function createPassword($password,$salt="") {

    return md5(md5($password.$salt));
}

/**
 * 检测密码
 * @param $dbPassword
 * @param $inPassword
 * @return bool
 */
function checkPassword($inPassword, $dbPassword,$salt="") {
    return (createPassword($inPassword,$salt) == $dbPassword);
}

/**
 * json成功返回
 */
function jsonSuccess($data=[],$msg="操作成功",$code=ServiceCode::SUCCESS){
    return json(['data' => $data, 'msg' => $msg, 'code' => $code]);
}

/**
 * json失败返回
 */
function jsonFail($msg="操作失败",$code=ServiceCode::FAIL,$data=[]){
    return json([ 'msg' => $msg,'code' => $code, 'data' => $data]);
}

/**
 * 权限检测
 */
function checkAuth($authTag){
    try{
        SystemAdminModel::checkAuth($authTag);
    }catch (ServiceException $e){
        return false;
    }
    return true;
}

/**
 * 可清理缓存
 * 后台的清理缓存按钮将会清理掉该函数存入的数据
 */
function cleanableCache($key,$data=null){
    if(empty($key)){
        throw new \think\Exception("不合法的缓存键");
    }
    $cleanableCacheData = cache("cleanable_cache");
    if(empty($cleanableCacheData)){
        $cleanableCacheData=[];
    }
    //取数据
    if($data === null){
        return isset($cleanableCacheData[$key])?$cleanableCacheData[$key]:null;
    }
    //存数据
    $cleanableCacheData[$key]=$data;
    cache("cleanable_cache",$cleanableCacheData);
}
/**
 * 金额格式处理
 */
function amount_format($amount){
    return number_format($amount,2);
    $right = "00";
    if(strpos($amount,".") !== false){
        $l_f = explode(".",$amount);
        $left = $l_f[0];
        $right = $l_f[1];
    }else{
        $left = $amount;
    }
    $temp = [];
    do{
        $unit = substr($left,-3);
        $left = substr($left,0,-(strlen($unit)));
        $temp[]=$left?",".$unit:$unit;
    }while($left);
    $left = "";
    foreach ($temp as $unit){
        $left=$unit.$left;
    }
    return $left.".".$right;
}

