<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\villdate\SalesContractVilldate;

/**
 * 销售合同
 */
class SalesContractController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $contract_name=$this->request->param("contract_name");
            $db=db('sales_contract');
            if(!empty($contract_name)){
                $db->where("contract_name","like","%$contract_name%");
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
            $this->validate($param,SalesContractVilldate::class);
            $param["create_time"] = time();
            //总金额
            if($param["is_contain_tax"] == 1){
                $param["all_amount"] = $param["contract_amount"];
            }else{
                $param["all_amount"] = $param["contract_amount"]*(1+((int)$param["tax_rate"]/100));
            }
            //公司合同编号是否已存在
            if(db('sales_contract')->where(["company_identifier"=>$param["company_identifier"]])->count()){
                return jsonFail("公司合同编号已存在,请修改后再提交");
            }
            db('sales_contract')->insert($param);
            return jsonSuccess();
        }
        //公司合同编号
        $company_identifier = "AC_".date("Ymd");
        $max_id = db('sales_contract')->max("id");
        if(empty($max_id)){$max_id = 1;}
        $company_identifier = $company_identifier."_".($max_id+1);
        $this->assign("company_identifier",$company_identifier);
        //客户单位数据
        $res = db('customer_supplier')->where("type","in",[1,3])->select();
        $this->assign("customers",$res);
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,SalesContractVilldate::class);
            //总金额
            if($param["is_contain_tax"] == 1){
                $param["all_amount"] = $param["contract_amount"];
            }else{
                $param["all_amount"] = $param["contract_amount"]*(1+((int)$param["tax_rate"]/100));
            }
            //公司合同编号是否已存在
            if(db('sales_contract')->where(["company_identifier"=>$param["company_identifier"]])->where("id","neq",$param["id"])->count()){
                return jsonFail("公司合同编号已存在,请修改后再提交");
            }
            db('sales_contract')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("sales_contract")->find($id);
        $this->assign("row",$row);
        //客户单位数据
        $res = db('customer_supplier')->where("type","in",[1,3])->select();
        $this->assign("customers",$res);
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
        db('sales_contract')->where("id","in",$id_arr)->delete();
        return jsonSuccess();
    }
}