<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/6/8
 * Time: 9:48
 */

namespace app\admin\controller;

use app\admin\villdate\GroupCompanyVilldate;
use app\common\exception\ServiceException;

/**
 * 登录日志
 */
class SystemLogLoginController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $username=$this->request->param("username");
            $db=db('system_log_login');
            if(!empty($username)){
                $db->where("username","like","%$username%");
            }
            $res = $db->order("id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
            }
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        return $this->fetch();
    }
    /**
     * 删除
     */
    function delete(){
        $id=$this->request->param('id');
        $id_arr=explode(",",$id);
        $id_arr=array_filter($id_arr);
        if(empty($id_arr)){
            return jsonFail("未找到需要删除的对象");
        }
        db('system_log_login')->where("id","in",$id_arr)->delete();
        return jsonSuccess();
    }

    /**
     * 添加和编辑需要关联的数据
     */
    private function assign2AddAndEdit($param=[]){

    }

}