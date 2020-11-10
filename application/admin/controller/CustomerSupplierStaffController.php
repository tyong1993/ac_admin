<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/6/8
 * Time: 9:48
 */

namespace app\admin\controller;

use app\admin\villdate\CustomerSupplierStaffVilldate;
use app\common\exception\ServiceException;

/**
 * 客户供应商联系人管理
 */
class CustomerSupplierStaffController extends BaseController
{
    protected $table = "customer_supplier_staff";
    protected $table_name = "联系人";
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $name=$this->request->param("name");
            $company_name=$this->request->param("company_name");
            $db=db($this->table);
            if(!empty($name)){
                $db->where("name","like","%$name%");
            }
            if(!empty($company_name)){
                $c_s_ids = db("customer_supplier")->where("company_name","like","%$company_name%")->column("id");
                $staff_ids = db("customer_supplier_staff_tocs")->where("c_s_id","in",$c_s_ids)->column("staff_id");
                $db->where("id","in",$staff_ids);
            }
            $res = $db->order("id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                switch ($val["type"]){
                    case 1:$val["type"] = "商务联系人";break;
                    case 2:$val["type"] = "项目联系人";break;
                    case 3:$val["type"] = "商务/项目联系人";break;
                }
                //所属公司
                $c_s_ids = db("customer_supplier_staff_tocs")->where("staff_id","=",$val["id"])->column("c_s_id");
                $company_names = db("customer_supplier")->where("id","in",$c_s_ids)->column("company_name");
                $company_names = implode(",",$company_names);
                $val["company_names"] = $company_names;
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
            $submit_ids=!empty($param["customer_suppliers"])?$param["customer_suppliers"]:[];
            unset($param["customer_suppliers"]);
            $this->validate($param,CustomerSupplierStaffVilldate::class);
            $param["create_time"] = time();
            $id=db($this->table)->insert($param,false,true);
            if($id){
                self::actionLog(1,$this->table,$this->table_name,$id);
            }
            $data = [];
            foreach ($submit_ids as $key=>$val){
                $data[]=["staff_id" =>$id,"c_s_id"=>$val];
            }
            db("customer_supplier_staff_tocs")->insertAll($data);
            return jsonSuccess();
        }
        $this->assign2AddAndEdit();
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $param["customer_suppliers"]=!empty($param["customer_suppliers"])?$param["customer_suppliers"]:[];
            $this->validate($param,CustomerSupplierStaffVilldate::class);
            //最新提交的
            $submit_ids=$param["customer_suppliers"];
            //已有的
            $exists=db("customer_supplier_staff_tocs")->where("staff_id","=",$param["id"])->column("c_s_id");
            //需要新增和删除的
            $needAdd=[];$needDel=[];
            foreach ($submit_ids as $key=>$val){
                if($val && !in_array($val,$exists)){
                    $needAdd[]=["staff_id" =>$param["id"],"c_s_id"=>$val];
                }
            }
            foreach ($exists as $key=>$val){
                if(!in_array($val,$submit_ids)){
                    $needDel[]=$val;
                }
            }
            $db=db("customer_supplier_staff_tocs");
            $db->startTrans();
            $db->insertAll($needAdd);
            if(!empty($needDel)){
                $db->where("staff_id","=",$param["id"])->where("c_s_id","in",$needDel)->delete();
            }
            $db->commit();
            unset($param["customer_suppliers"]);
            $row = db($this->table)->find($param["id"]);
            if(db($this->table)->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$row["id"],$row);
            }
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("customer_supplier_staff")->find($id);
        $c_s_ids=db("customer_supplier_staff_tocs")->where("staff_id","eq",$id)->column("c_s_id");
        $row["customer_supplier_ids"] = $c_s_ids;
        $this->assign("row",$row);
        $this->assign2AddAndEdit();
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
        foreach ($id_arr as $id){
            $row = db($this->table)->find($id);
            if(!empty($row)){
                self::actionLog(3,$this->table,$this->table_name,$id,$row);
                db($this->table)->where("id","eq",$id)->delete();
            }
        }
        return jsonSuccess();
    }

    /**
     * 添加和编辑需要关联的数据
     */
    private function assign2AddAndEdit($param=[]){
        $customer_suppliers=db("customer_supplier")->select();
        $this->assign("customer_suppliers",$customer_suppliers);
    }

}