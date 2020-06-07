<?php
namespace app\common\exception;

use app\common\ServiceCode;
use Exception;
use think\Container;
use think\exception\Handle as BaseHandle;
use think\exception\PDOException;
use think\exception\ValidateException;
use traits\controller\Jump;
use think\Response;

class Handle extends BaseHandle
{
    use Jump;

    /**
     * @param Exception $e
     * @return Handle|Response|\think\response\Json
     * 异常统一接管中心
     * 业务异常公共处理，处理程序中未捕获处理的异常
     */
    public function render(Exception $e)
    {
        // 记录系统异常日志
        //todo

        // 业务异常处理
        if ($e instanceof ServiceException) {
            return $this->ServiceExceptionHandler($e);
        }
        // 验证器异常处理
        if ($e instanceof ValidateException) {
            return $this->ServiceExceptionHandler(new ServiceException($e->getMessage()));
        }
        // 模型层异常统一处理,调试模式下不处理
        if ($e instanceof PDOException) {
            if(!config("app_debug")){
                return $this->ServiceExceptionHandler(new ServiceException("数据库异常"));
            }
        }
        // 其他错误交给系统处理
        return parent::render($e);
    }

    /**
     * @param ServiceException $e
     * @return $this|\think\response\Json
     * 业务异常公共处理器
     */
    public function ServiceExceptionHandler(ServiceException $e){
        //ajax请求
        if(request()->isAjax()){
            return jsonFail($e->getMessage(),$e->getCode(),$e->getData());
        }
        //非ajax请求,默认返回上一页,可根据具体业务码控制跳转页面
        $result = [
            'code' => 0,
            'msg'  => $e->getMessage(),
            'url'  => 'javascript:history.back(-1);',
            'wait' => 3,
        ];
        //未登录
        if($e->getCode() == ServiceCode::NOT_LOGIN){
            switch (request()->module()){
                case "admin":$result["url"] = url("Index/login");break;
                case "index":die("请先登陆");break;
                default:die("未知模块");
            }
        }
        return Response::create($result, 'jump')->header([])->options(['jump_template' =>Container::get("app")['config']->get('dispatch_error_tmpl')]);
    }

}