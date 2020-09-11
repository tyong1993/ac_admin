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
    /**
     * 列表
     */
    function index(){
        $contract_id = $this->request->param("id");
        $db=db('sales_collection');
        $db->where("contract_id","=",$contract_id);
        $res = $db->select();
        //合同税率
        $row = db('sales_contract')->find($contract_id);
        $tax_rate = $row["is_contain_tax"] == 1?"---":$row["tax_rate"];
        //收款金额合计,税额,合同总金额,合同ID
        $others=[
            "skjehj"=>0.00,
            "se"=>0.00,
            "htzje"=>$row["all_amount"],
            "htid"=>$row["id"],
        ];
        foreach ($res as &$val){
            $val["tax_rate"] = $tax_rate;
            $val["check_time"] = $val["check_time"]?date("Y-m-d",$val["check_time"]):"---";
            $val["status_name"] = self::getStatusName($val["status"]);
            $others["skjehj"] += $val["collection_amount"];
            $others["se"] += $row["is_contain_tax"]==1?0:$val["collection_amount"]*($row["tax_rate"]/100);
        }
        $others["hjzje"] = $others["skjehj"] + $others["se"];
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
            db('sales_collection')->insert($param);
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
            $param["check_time"] = !empty($param["check_time"])?strtotime($param["check_time"]):0;
            $param["collection_time"] = !empty($param["collection_time"])?strtotime($param["collection_time"]):0;
            db('sales_collection')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("sales_collection")->find($id);
        $row["check_time"] = !empty($row["check_time"])?date("Y-m-d",$row["check_time"]):"";
        $row["collection_time"] = !empty($row["collection_time"])?date("Y-m-d",$row["collection_time"]):"";
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
        db('sales_collection')->where("id","in",$id_arr)->delete();
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
            db('sales_collection')->update($param);
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
            $sales_collection["collected"] = $param["invoice_amount"];
            $res1 = Db::name('sales_collection')->update($sales_collection);
            //添加待开票记录
            $admin = SystemAdminModel::find(session("admin_id"));
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
            $data["tax_rate"] = $param["is_contain_tax"]?0:$param["tax_rate"];
            $data["create_time"] = time();
            $res2 = Db::name("invoice_records")->insert($data);
            if(!$res1 || !$res2){
                Db::rollback();
                return jsonFail("申请开票失败");
            }
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
}