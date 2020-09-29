<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\villdate\OutsourcingContractVilldate;

/**
 * 外包合同
 */
class OutsourcingContractController extends BaseController
{
    protected $table = "outsourcing_contract";
    protected $table_name = "外包合同";
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $contract_name=$this->request->param("contract_name");
            $supplier_name=$this->request->param("supplier_name");
            $project_leader=$this->request->param("project_leader");
            $sign_date=$this->request->param("sign_date");
            $g_c_id=$this->request->param("g_c_id");
            $db=db('outsourcing_contract');
            if(!empty($contract_name)){
                $db->where("contract_name","like","%$contract_name%");
            }
            if(!empty($supplier_name)){
                $db->where("supplier_name","like","%$supplier_name%");
            }
            if(!empty($project_leader)){
                $db->where("project_leader","like","%$project_leader%");
            }
            if(!empty($sign_date)){
                $sign_date_s_e = explode("~",$sign_date);
                $sign_date_start = strtotime($sign_date_s_e[0]);
                $sign_date_end = strtotime($sign_date_s_e[1]);
                $db->where("sign_date","egt",$sign_date_start);
                $db->where("sign_date","elt",$sign_date_end);
            }
            if(!empty($g_c_id)){
                $db->where("g_c_id","eq",$g_c_id);
            }
            $res = $db->order("id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                $val["sign_date"] = date("Y-m-d",$val["sign_date"]);
                $val["start_time"] = date("Y-m-d",$val["start_time"]);
                $val["end_time"] = date("Y-m-d",$val["end_time"]);
                $val["tax_rate"] = $val["is_contain_tax"]?"---":$val["tax_rate"];
                $val["contract_amount"] = amount_format($val["contract_amount"]);
                $val["all_amount"] = amount_format($val["all_amount"]);
            }
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        //签约单位数据
        $res = db('group_company')->select();
        $this->assign("group_companys",$res);
        return $this->fetch();
    }

    /**
     * 添加
     */
    function add(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,OutsourcingContractVilldate::class);
            $param["create_time"] = time();
            //公司合同编号是否已存在
            if(db('outsourcing_contract')->where(["company_identifier"=>$param["company_identifier"]])->count()){
                return jsonFail("公司合同编号已存在,请修改后再提交");
            }
            $this->dataWriteHandle($param);
            $id=db($this->table)->insert($param,false,true);
            if($id){
                self::actionLog(1,$this->table,$this->table_name,$id);
            }
            return jsonSuccess();
        }
        //公司合同编号
        $company_identifier = "AC_".date("Ymd");
        $max_id = db('outsourcing_contract')->max("id");
        $company_identifier = $company_identifier."_".($max_id+1);
        $this->assign("company_identifier",$company_identifier);
        $this->assign2AddAndEdit();
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,OutsourcingContractVilldate::class);
            //公司合同编号是否已存在
            if(db('outsourcing_contract')->where(["company_identifier"=>$param["company_identifier"]])->where("id","neq",$param["id"])->count()){
                return jsonFail("公司合同编号已存在,请修改后再提交");
            }
            $this->dataWriteHandle($param);
            $row = db($this->table)->find($param["id"]);
            if(db($this->table)->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$row["id"],$row);
            }
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("outsourcing_contract")->find($id);
        $row["sign_date"] = date("Y-m-d",$row["sign_date"]);
        $row["start_time"] = date("Y-m-d",$row["start_time"]);
        $row["end_time"] = date("Y-m-d",$row["end_time"]);
        $this->assign("row",$row);
        //联系人可选项
        $staff_ids = db("customer_supplier_staff_tocs")->where("c_s_id","=",$row["c_s_id"])->column("staff_id");
        //商务联系人
        $business_contacts = db("customer_supplier_staff")->where("id","in",$staff_ids)->where("type","in",[1,3])->column("name","id");
        //项目联系人
        $project_contacts = db("customer_supplier_staff")->where("id","in",$staff_ids)->where("type","in",[2,3])->column("name","id");
        $this->assign("business_contacts",$business_contacts);
        $this->assign("project_contacts",$project_contacts);
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
        //供应商单位数据
        $res = db('customer_supplier')->where("type","in",[2,3])->select();
        foreach ($res as &$val){
            //商务联系人
            $staff_ids = db("customer_supplier_staff_tocs")->where("c_s_id","=",$val["id"])->column("staff_id");
            $business_contacts = db("customer_supplier_staff")->where("id","in",$staff_ids)->where("type","in",[1,3])->column("name","id");
            $val["business_contact"] = "";
            foreach ($business_contacts as $k=>$v){
                $val["business_contact"].=$k."|--|".$v.",";
            }
            $val["business_contact"] = substr($val["business_contact"],0,-1);
            //项目联系人
            $staff_ids = db("customer_supplier_staff_tocs")->where("c_s_id","=",$val["id"])->column("staff_id");
            $project_contacts = db("customer_supplier_staff")->where("id","in",$staff_ids)->where("type","in",[2,3])->column("name","id");
            $val["project_contact"] = "";
            foreach ($project_contacts as $k=>$v){
                $val["project_contact"].=$k."|--|".$v.",";
            }
            $val["project_contact"] = substr($val["project_contact"],0,-1);
        }
        $this->assign("suppliers",$res);
        //销售合同数据
        $res = db('sales_contract')->order("id desc")->select();
        $this->assign("sales_contracts",$res);
        //签约单位数据
        $res = db('group_company')->select();
        $this->assign("group_companys",$res);
        //商务负责人，项目负责人数据
        $res = db('system_admin')->where("status","eq",1)->where("id","neq",1)->select();
        $this->assign("business_leaders",$res);
        $this->assign("project_leaders",$res);
    }
    /**
     * 数据写入处理
     */
    private function dataWriteHandle(&$param=[]){
        $param["sign_date"] = strtotime($param["sign_date"]);
        $param["start_time"] = strtotime($param["start_time"]);
        $param["end_time"] = strtotime($param["end_time"]);
        //总金额
        if($param["is_contain_tax"] == 1){
            $param["all_amount"] = $param["contract_amount"];
        }else{
            $param["all_amount"] = $param["contract_amount"]*(1+((int)$param["tax_rate"]/100));
        }
        //联系人
        $param["b_staff_id"] = $param["business_contact"];
        $param["p_staff_id"] = $param["project_contact"];
        $param["business_contact"] = db("customer_supplier_staff")->where(["id"=>$param["business_contact"]])->value("name")?:"";
        $param["project_contact"] = db("customer_supplier_staff")->where(["id"=>$param["project_contact"]])->value("name")?:"";
        //签约单位
        $param["group_company_name"] = db("group_company")->where(["id"=>$param["g_c_id"]])->value("name");
        //商务负责人，项目负责人
        $param["business_leader"] = db("system_admin")->where(["id"=>$param["b_l_id"]])->value("name")?:"";
        $param["project_leader"] = db("system_admin")->where(["id"=>$param["p_l_id"]])->value("name")?:"";
        return $param;
    }
}