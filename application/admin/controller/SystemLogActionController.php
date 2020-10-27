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
 * 操作日志
 */
class SystemLogActionController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $username=$this->request->param("username");
            $table_name=$this->request->param("table_name");
            $object_id=$this->request->param("object_id");
            $type=$this->request->param("type");
            $db=db('system_log_action');
            if(!empty($username)){
                $db->where("admin_username","eq","$username");
            }
            if(!empty($type)){
                $db->where("type","eq","$type");
            }
            if(!empty($table_name)){
                $db->where("table_name","eq","$table_name");
            }
            if(!empty($object_id)){
                $db->where("object_id","eq","$object_id");
            }
            $res = $db->order("id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                if($val["type"] == 1){
                    $val["type"] = "新增";
                }
                if($val["type"] == 2){
                    $val["type"] = "更新";
                }
                if($val["type"] == 3){
                    $val["type"] = "删除";
                }
                $val["data"] = json_encode(json_decode($val["data"]),JSON_UNESCAPED_UNICODE);
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
        db('system_log_action')->where("id","in",$id_arr)->delete();
        return jsonSuccess();
    }

    /**
     * 添加和编辑需要关联的数据
     */
    private function assign2AddAndEdit($param=[]){

    }

}