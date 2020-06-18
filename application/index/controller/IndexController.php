<?php
namespace app\index\controller;

use think\Request;

class IndexController
{
    public function index(Request $request)
    {
        return $request->controller()."/".$request->action();
    }


}
