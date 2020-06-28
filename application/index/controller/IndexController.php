<?php
namespace app\index\controller;

use think\Request;

class IndexController
{
    public function index(Request $request)
    {
        echo 123;
        return $request->controller()."/".$request->action();
    }


}
