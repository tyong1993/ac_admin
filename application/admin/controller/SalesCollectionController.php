<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\model\SystemAdminModel;
use app\admin\villdate\SalesCollectionVilldate;
use think\Db;

/**
 * 销售合同收款计划
 */
class SalesCollectionController extends BaseController
{
    protected $table = "sales_collection";
    protected $table_name = "收款计划";
    /**
     * 列表
     */
    function index(){
        $contract_id = $this->request->param("id");
        $db=db('sales_collection a')->field("a.*,b.colletion_status,if(b.colletion_status = 1,invoice_amount,0) collected_amount")
            ->leftJoin("invoice_records b","a.id = b.collection_id");
        $db->where("a.contract_id","=",$contract_id);
        $res = $db->select();
        //合同税率
        $row = db('sales_contract')->find($contract_id);
        //收款金额合计,税额,合同总金额,合同ID
        $others=[
            "skjehj"=>0.00,
            "htje"=>$row["contract_amount"],
            "htid"=>$row["id"],
            "is_contain_tax"=>$row["is_contain_tax"]?"含税":"不含税"
        ];
        foreach ($res as &$val){
            $val["expect_check_time_format"] = $val["expect_check_time"]?date("Y-m-d",$val["expect_check_time"]):"---";
            $val["expect_invoice_time_format"] = $val["expect_invoice_time"]?date("Y-m-d",$val["expect_invoice_time"]):"---";
            $val["expect_colletion_time_format"] = $val["expect_colletion_time"]?date("Y-m-d",$val["expect_colletion_time"]):"---";
            $val["check_time"] = $val["check_time"]?date("Y-m-d",$val["check_time"]):"---";
            $val["status_name"] = self::getStatusName($val["status"]);
            if($val["status_name"] == "待验收" && self::isComeNowMonth($val["expect_check_time"])){
                $val["expect_check_time_format"] = "<span style='color: red'>".$val["expect_check_time_format"]."</span>";
            }
            if($val["status"] != 3 && self::isComeNowMonth($val["expect_invoice_time"])){
                $val["expect_invoice_time_format"] = "<span style='color: red'>".$val["expect_invoice_time_format"]."</span>";
            }
            if($val["colletion_status"]==0 && self::isComeNowMonth($val["expect_colletion_time"])){
                $val["expect_colletion_time_format"] = "<span style='color: red'>".$val["expect_colletion_time_format"]."</span>";
            }
            $val["colletion_status_name"] = $val["colletion_status"]?"已收款":"<span style='color: red'>未收款</span>";
            $val["collection_amount_format"] = amount_format($val["collection_amount"]);
            $val["uncollection_amount_format"] = amount_format($val["collection_amount"]-$val["collected_amount"]);
            if($val["uncollection_amount_format"] != "0.00"){
                $val["uncollection_amount_format"] = "<span style='color: red'>".$val["uncollection_amount_format"]."</span>";
            }
            $others["skjehj"] += $val["collection_amount"];
        }
        $others["skjehj_format"] = amount_format($others["skjehj"]);
        $others["htje_format"] = amount_format($others["htje"]);
        $this->assign("res",$res);
        $this->assign("others",$others);
        return $this->fetch();
    }

    /**
     * 添加
     */
    function add(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,SalesCollectionVilldate::class);
            $param["expect_check_time"] = !empty($param["expect_check_time"])?strtotime($param["expect_check_time"]):0;
            $param["expect_invoice_time"] = !empty($param["expect_invoice_time"])?strtotime($param["expect_invoice_time"]):0;
            $param["expect_colletion_time"] = !empty($param["expect_colletion_time"])?strtotime($param["expect_colletion_time"]):0;
            $id=db($this->table)->insert($param,false,true);
            if($id){
                self::actionLog(1,$this->table,$this->table_name,$id);
            }
            return jsonSuccess();
        }
        $contract_id = $this->request->param("contract_id");
        $this->assign("contract_id",$contract_id);
        //期数预测
        $before = db('sales_collection')->where("contract_id","=",$contract_id)->order("id desc")->find();
        if(empty($before)){
            $maybe = "第一期";
        }else{
            $maybe = self::getPeriodsForSelect($before["periods"]);
        }
        $this->assign("maybe",$maybe);
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,SalesCollectionVilldate::class);
            $param["expect_check_time"] = !empty($param["expect_check_time"])?strtotime($param["expect_check_time"]):0;
            $param["expect_invoice_time"] = !empty($param["expect_invoice_time"])?strtotime($param["expect_invoice_time"]):0;
            $param["expect_colletion_time"] = !empty($param["expect_colletion_time"])?strtotime($param["expect_colletion_time"]):0;
            $param["check_time"] = !empty($param["check_time"])?strtotime($param["check_time"]):0;
            $row = db($this->table)->find($param["id"]);
            if(db($this->table)->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$row["id"],$row);
            }
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("sales_collection")->find($id);
        $row["expect_check_time"] = !empty($row["expect_check_time"])?date("Y-m-d",$row["expect_check_time"]):"";
        $row["expect_invoice_time"] = !empty($row["expect_invoice_time"])?date("Y-m-d",$row["expect_invoice_time"]):"";
        $row["expect_colletion_time"] = !empty($row["expect_colletion_time"])?date("Y-m-d",$row["expect_colletion_time"]):"";
        $row["check_time"] = !empty($row["check_time"])?date("Y-m-d",$row["check_time"]):"";
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
                if($row["status"] > 1){
                    return jsonFail("当前记录已申请开票,不能删除,请先删除开票申请记录");
                }
                self::actionLog(3,$this->table,$this->table_name,$id,$row);
                db($this->table)->where("id","eq",$id)->delete();
            }
        }
        return jsonSuccess();
    }
    /**
     * 待验收
     */
    function status0(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            //可开票
            $param["status"] = 1;
            $param["check_time"] = time();
            if(db('sales_collection')->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$param["id"],null,"验收");
            }
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("sales_collection")->find($id);
        $this->assign("row",$row);
        return $this->fetch();
    }
    /**
     * 可开票
     */
    function status1(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            if(empty($param["invoice_amount"])){
                return jsonFail("请填写开票金额");
            }
            Db::startTrans();
            //申请开票
            $sales_collection["id"] = $param["id"];
            $sales_collection["status"] = 2;
            //$sales_collection["collected"] = $param["invoice_amount"];
            $res1 = Db::name('sales_collection')->update($sales_collection);
            //添加待开票记录
            $admin = self::$login_admin;
            $data["contract_id"] = $param["contract_id"];
            $data["collection_id"] = $param["id"];
            $data["contract_name"] = $param["contract_name"];
            $data["invoice_amount"] = $param["invoice_amount"];
            $data["surplus_amount"] = $param["collection_amount"]-$param["invoice_amount"];
            $data["periods"] = $param["periods"];
            $data["sq_people"] = $admin["name"];
            $data["title"] = $param["title"];
            $data["tax_num"] = $param["tax_num"];
            $data["bank_name"] = $param["bank_name"];
            $data["bank_account"] = $param["bank_account"];
            $data["address"] = $param["address"];
            $data["phone"] = $param["phone"];
            $data["create_time"] = time();
            $res2 = Db::name("invoice_records")->insert($data);
            if(!$res1 || !$res2){
                Db::rollback();
                return jsonFail("申请开票失败");
            }
            self::actionLog(2,$this->table,$this->table_name,$param["id"],null,"申请开票");
            Db::commit();
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("sales_collection")->find($id);
        //开票信息
        $contract = db('sales_contract')->find($row["contract_id"]);
        $invoices = db('customers_invoice')->where(["c_s_id"=>$contract["c_s_id"]])->select();
        $this->assign("row",$row);
        $this->assign("invoices",$invoices);
        $this->assign("contract",$contract);
        return $this->fetch();
    }

    /**
     * 获取收款计划状态名称
     */
    public static function getStatusName($status){
        switch ($status){
            case 0:return "待验收";
            case 1:return "可开票";
            case 2:return "待开票";
            case 3:return "已开票";
            default:return "---";
        }
    }
    /**
     * 计划期数
     */
    public static function getPeriodsForSelect($now_periods){
        $map = [
            "1"=>"一",
            "2"=>"二",
            "3"=>"三",
            "4"=>"四",
            "5"=>"五",
            "6"=>"六",
            "7"=>"七",
            "8"=>"八",
            "9"=>"九",
            "10"=>"十",
            "20"=>"二十",
            "30"=>"三十",
            "40"=>"四十",
            "50"=>"五十",
            "60"=>"六十",
            "70"=>"七十",
            "80"=>"八十",
            "90"=>"九十",
            "100"=>"一百",
        ];
        $before = 0;
        for ($i = 1;$i <= 100;$i++){
            $temp = "";
            if(isset($map[$i])){
                $temp = "第".$map[$i]."期";
            }else{
                $ii = str_split($i);
                if($ii[0] == 1){
                    $temp = "第"."十".$map[$ii[1]]."期";
                }else{
                    $temp = "第".$map[$ii[0]]."十".$map[$ii[1]]."期";
                }
            }
            if($before == 1){
                return $temp;
            }
            if($temp == $now_periods){
                $before = 1;
            }
        }
        return "";
    }
    /**
     * 是否到了当月
     */
    public static function isComeNowMonth($check_time){
        if(!$check_time){
            return false;
        }
        $time = strtotime(date("Y-m",$check_time));
        if($time>time()){
            return false;
        }
        return true;
    }
    /**
     * 获取合同分期金额合计
     */
    public static function getCollectionsAmounts($contract_id){
        $db = db("sales_collection")
            ->where("contract_id","eq",$contract_id);
        $res = $db->column("collection_amount");
        return array_sum($res);
    }

}