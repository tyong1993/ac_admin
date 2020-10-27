<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\villdate\SalesContractVilldate;
use think\Db;

/**
 * 销售合同
 */
class SalesContractController extends BaseController
{
    protected $table = "sales_contract";
    protected $table_name = "销售合同";
    /**
     * 列表
     */
    function index(){
        $is_collection_completed=$this->request->param("is_collection_completed");
        $select_by_year=$this->request->param("select_by_year");
        //默认查询当年数据
        if($select_by_year === null){
            $select_by_year = date("Y");
        }
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $contract_name=$this->request->param("contract_name");
            $customer_name=$this->request->param("customer_name");
            $project_leader=$this->request->param("project_leader");
            $g_c_id=$this->request->param("g_c_id");
            $is_stamp_tax=$this->request->param("is_stamp_tax");
            $is_collection_completed=$this->request->param("is_collection_completed");
            //收款情况子查询
            $subsql_invoice_records = db("invoice_records")
                ->field('contract_id,if(invoice_status,invoice_amount,0) invoiced_amount,if(colletion_status,invoice_amount,0) collected_amount')
                ->buildSql();
            $subsql_invoice_records = Db::table($subsql_invoice_records." a")
                ->field('contract_id,sum(invoiced_amount) invoiced_amount_0,sum(collected_amount) collected_amount_0')
                ->group('contract_id')
                ->buildSql();
            $db=db('sales_contract a')
                ->field("a.*,IFNULL(b.invoiced_amount_0,0) invoiced_amount,IFNULL(b.collected_amount_0,0) colleted_amount")
                ->leftJoin([$subsql_invoice_records=>"b"],"a.id = b.contract_id");
            if(!empty($contract_name)){
                $db->where("contract_name","like","%$contract_name%");
            }
            if(!empty($customer_name)){
                $db->where("customer_name","like","%$customer_name%");
            }
            if(!empty($project_leader)){
                $db->where("project_leader","like","%$project_leader%");
            }
            if(!empty($select_by_year)){
                $select_by_time=$select_by_year."-01-01 00:00:00";
                //年初时间戳
                $year_start=strtotime($select_by_time);
                //年末时间戳
                $year_end=strtotime("+1 year",$year_start);
                $db->where("sign_date","egt",$year_start);
                $db->where("sign_date","elt",$year_end);
            }
            if(!empty($g_c_id)){
                $db->where("g_c_id","eq",$g_c_id);
            }
            if(!empty($is_stamp_tax)){
                $db->where("is_stamp_tax","eq",$is_stamp_tax-1);
            }
            if(!empty($is_collection_completed)){
                if($is_collection_completed == 2){
                    $db->where("contract_amount = collected_amount_0");
                }else{
                    $db->where("contract_amount != collected_amount_0");
                }
            }
            //数据权限
            $db = self::dataPower($db,"b_l_id");
            $res = $db->order("id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                $val["sign_date"] = $val["sign_date"]?date("Y-m-d",$val["sign_date"]):"---";
                $val["start_time"] = $val["start_time"]?date("Y-m-d",$val["start_time"]):"---";
                $val["end_time"] = $val["end_time"]?date("Y-m-d",$val["end_time"]):"---";
                $val["is_contain_tax"] = $val["is_contain_tax"]?"含税":"不含税";
                $val["is_stamp_tax"] = $val["is_stamp_tax"]?"有":"无";
                $val["contract_amount"] = amount_format($val["contract_amount"]);
                $val["invoiced_amount"] = amount_format($val["invoiced_amount"]);
                $val["colleted_amount"] = amount_format($val["colleted_amount"]);
                $val["colletions_amount"] = amount_format(SalesCollectionController::getCollectionsAmounts($val["id"]));
                if($val["invoiced_amount"] != $val["contract_amount"]){
                    $val["invoiced_amount"] = "<span style='color: red'>".$val["invoiced_amount"]."</span>";
                }
                if($val["colleted_amount"] != $val["contract_amount"]){
                    $val["colleted_amount"] = "<span style='color: red'>".$val["colleted_amount"]."</span>";
                }
                if($val["colletions_amount"] != $val["contract_amount"]){
                    $val["colletions_amount"] = "<span style='color: red'>".$val["colletions_amount"]."</span>";
                }
            }
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        $this->assign("is_collection_completed",$is_collection_completed);
        $this->assign("select_by_year",$select_by_year);
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
            $this->validate($param,SalesContractVilldate::class);
            //公司合同编号是否已存在
            if(db('sales_contract')->where(["company_identifier"=>$param["company_identifier"]])->count()){
                return jsonFail("公司合同编号已存在,请修改后再提交");
            }
            $param["create_time"] = time();
            $this->dataWriteHandle($param);
            $id=db($this->table)->insert($param,false,true);
            if($id){
                self::actionLog(1,$this->table,$this->table_name,$id);
            }
            return jsonSuccess();
        }
        //公司合同编号
        $company_identifier = "AC_".date("Ymd");
        $max_id = db('sales_contract')->max("id");
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
            $this->validate($param,SalesContractVilldate::class);
            //公司合同编号是否已存在
            if(db('sales_contract')->where(["company_identifier"=>$param["company_identifier"]])->where("id","neq",$param["id"])->count()){
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
        $row=db("sales_contract")->find($id);
        $row["sign_date"] = $row["sign_date"]?date("Y-m-d",$row["sign_date"]):"";
        $row["start_time"] = $row["start_time"]?date("Y-m-d",$row["start_time"]):"";
        $row["end_time"] = $row["end_time"]?date("Y-m-d",$row["end_time"]):"";
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
     * 印花税
     */
    function stampTax(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            if(db($this->table)->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$param["id"],null,"印花税");
            }
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("sales_contract")->find($id);
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
        //客户单位数据
        $res = db('customer_supplier')->where("type","in",[1,3])->select();
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
        $this->assign("customers",$res);
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