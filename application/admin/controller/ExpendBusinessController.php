<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\villdate\ExpendBusinessVilldate;

/**
 * 商务支出管理
 */
class ExpendBusinessController extends BaseController
{
    protected $table = "expend_business";
    protected $table_name = "商务支出";
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $business_contact=$this->request->param("business_contact");
            $contract_name=$this->request->param("contract_name");
            $db=db('expend_business');
            if(!empty($business_contact)){
                $db->where("business_contact","like","%$business_contact%");
            }
            if(!empty($contract_name)){
                $db->where("contract_name","like","%$contract_name%");
            }
            $res = $db->order("id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                $val["tax_rate"] = $val["is_need_tax"]?$val["tax_rate"]:"---";
                $val["pay_status"] = $val["pay_status"]?"已付款":"<span style='color: red'>未付款</span>";
                $val["collection_amount"] = amount_format($val["collection_amount"]);
                $val["pay_amount_need"] = amount_format($val["pay_amount_need"]);
                $val["pay_amount_true"] = amount_format($val["pay_amount_true"]);
                $val["pay_time"] = $val["pay_time"]?date("Y-m-d",$val["pay_time"]):"---";
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
            $this->validate($param,ExpendBusinessVilldate::class);
            $param["create_time"] = time();
            $param["pay_time"] = !empty($param["pay_time"])?strtotime($param["pay_time"]):0;
            $id=db($this->table)->insert($param,false,true);
            if($id){
                self::actionLog(1,$this->table,$this->table_name,$id);
            }
            return jsonSuccess();
        }
        //销售合同数据
        $res = db('sales_contract')->order("id desc")->select();
        $this->assign("sales_contracts",$res);
        //商务联系人数据
        $business_contacts = db("customer_supplier_staff")->where("type","in",[1,3])->column("name","id");
        $this->assign("business_contacts",$business_contacts);
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,ExpendBusinessVilldate::class);
            $param["pay_time"] = !empty($param["pay_time"])?strtotime($param["pay_time"]):0;
            $row = db($this->table)->find($param["id"]);
            if(db($this->table)->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$row["id"],$row);
            }
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("expend_business")->find($id);
        $row["pay_time"] = !empty($row["pay_time"])?date("Y-m-d",$row["pay_time"]):"";
        $this->assign("row",$row);
        //销售合同数据
        $res = db('sales_contract')->order("id desc")->select();
        $this->assign("sales_contracts",$res);
        //收款期数数据
        $res = db('sales_collection')->where(["contract_id"=>$row["contract_id"]])->order("id desc")->select();
        $this->assign("sales_collections",$res);
        //该期收款情况
        $res = db('sales_collection a')->field("a.*,b.colletion_status")->leftJoin("invoice_records b","a.id = b.collection_id")->where("a.id","eq",$row["collection_id"])->find();
        $this->assign("now_periods",$res);
        //商务联系人数据
        $business_contacts = db("customer_supplier_staff")->where("type","in",[1,3])->column("name","id");
        $this->assign("business_contacts",$business_contacts);
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
     * 获取销售合同对应的收款期数
     */
    function getSalesCollections(){
        $contract_id = $this->request->param("contract_id");
        $res = db('sales_collection')->where(["contract_id"=>$contract_id])->order("id desc")->select();
        $templete = "";
        $templete .= '<option value="">请选择收款期数</option>';
        foreach ($res as $val){
            $templete .= '<option value="'.$val['id'].'|-|'.$val['periods'].'|-|'.$val['collection_amount'].'">'.$val['periods'].'</option>';
        }
        return jsonSuccess($templete);
    }
}