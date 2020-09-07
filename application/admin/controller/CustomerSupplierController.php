<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\villdate\CustomerSupplierVilldate;

/**
 * Class CustomerSupplierController
 * @package app\admin\controller
 * 客户/供应商
 */
class CustomerSupplierController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $company_name=$this->request->param("company_name");
            $db=db('customer_supplier');
            if(!empty($company_name)){
                $db->where("company_name","like","%$company_name%");
            }
            $res = $db->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
            }
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        return $this->fetch();
    }

    /**
     * 添加
     */
    function add(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,CustomerSupplierVilldate::class);
            $param["create_time"] = time();
            db('customer_supplier')->insert($param);
            return jsonSuccess();
        }
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,CustomerSupplierVilldate::class);
            db('customer_supplier')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("customer_supplier")->find($id);
        $this->assign("row",$row);
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
        db('customer_supplier')->where("id","in",$id_arr)->delete();
        return jsonSuccess();
    }
}