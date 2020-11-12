<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\villdate\InvoiceRecordsVilldate;
use app\common\tools\excel\ExcelTool;
use think\Db;

/**
 * 开票管理
 */
class InvoiceRecordsController extends BaseController
{
    protected $table = "invoice_records";
    protected $table_name = "开票记录";
    /**
     * 列表
     */
    function index(){
        $invoice_status=$this->request->param("invoice_status");
        $colletion_status=$this->request->param("colletion_status");
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $sq_people=$this->request->param("sq_people");
            $contract_name=$this->request->param("contract_name");
            $db=db('invoice_records a');
            $db=$db->field("a.*,b.remarks,c.contract_name contract_name")
                ->leftJoin("sales_collection b","a.collection_id = b.id")
                ->leftJoin("sales_contract c","a.contract_id = c.id");
            if(!empty($sq_people)){
                $db->where("a.sq_people","like","%$sq_people%");
            }
            if(!empty($contract_name)){
                $db->where("c.contract_name","like","%$contract_name%");
            }
            if(!empty($invoice_status)){
                $db->where("a.invoice_status","eq",$invoice_status-1);
            }
            if(!empty($colletion_status)){
                $db->where("a.colletion_status","eq",$colletion_status-1);
            }
            $res = $db->order("id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                $val["invoice_time"] = !empty($val["invoice_time"])?date("Y-m-d",$val["invoice_time"]):"---";
                $val["colletion_time"] = !empty($val["colletion_time"])?date("Y-m-d",$val["colletion_time"]):"---";
                $val["invoice_amount"] = amount_format($val["invoice_amount"]);
            }
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        $this->assign("invoice_status",$invoice_status);
        $this->assign("colletion_status",$colletion_status);
        return $this->fetch();
    }
    /**
     * 导出
     */
    function export(){
        $sq_people=$this->request->param("sq_people");
        $contract_name=$this->request->param("contract_name");
        $db=db('invoice_records');
        if(!empty($sq_people)){
            $db->where("sq_people","like","%$sq_people%");
        }
        if(!empty($contract_name)){
            $db->where("contract_name","like","%$contract_name%");
        }
        $res = $db->order("id desc")->select();
        foreach ($res as &$val){
            $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
            $val["invoice_time"] = !empty($val["invoice_time"])?date("Y-m-d",$val["invoice_time"]):"---";
            $val["colletion_time"] = !empty($val["colletion_time"])?date("Y-m-d",$val["colletion_time"]):"---";
            $val["invoice_amount"] = amount_format($val["invoice_amount"]);
            $val["invoice_status"] = $val["invoice_status"]?"已开票":"未开票";
            $val["colletion_status"] = $val["invoice_status"]?"已收款":"未收款";
        }
        $cellName = [
            ["id","ID",10],
            ["create_time","申请日期",15],
            ["contract_name","合同名称",50],
            ["periods","期数",10],
            ["title","抬头",40],
            ["sq_people","申请人",15],
            ["invoice_amount","收款金额",15],
            ["invoice_time","开票时间",15],
            ["colletion_time","收款时间",15],
            ["invoice_status","开票状态",10],
            ["colletion_status","收款状态",10],
        ];
        ExcelTool::export2Excel(date("YmdHis"),$cellName,$res);
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,InvoiceRecordsVilldate::class);
            $param["invoice_time"] = strtotime($param["invoice_time"]);
            $param["colletion_time"] = strtotime($param["colletion_time"]);
            db('sales_collection')->update(["id"=>$param["colletion_id"],"remarks"=>$param["colletion_remarks"]]);
            unset($param["colletion_id"]);unset($param["colletion_remarks"]);
            db('invoice_records')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("invoice_records")->find($id);
        $row["invoice_time"] = $row["invoice_time"]?date("Y-m-d",$row["invoice_time"]):"";
        $row["colletion_time"] = $row["colletion_time"]?date("Y-m-d",$row["colletion_time"]):"";
        $colletion=db("sales_collection")->find($row["collection_id"]);

        $this->assign("row",$row);
        $this->assign("colletion",$colletion);
        return $this->fetch();
    }

    /**
     * 确认开票
     */
    function doInvoice(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,InvoiceRecordsVilldate::class);
            if($param["invoice_amount"] != $param["invoice_num_amounts"]){
                return jsonFail("待开票金额与已开票金额不一致");
            }
            Db::startTrans();
            $param["invoice_status"] = 1;
            $param["invoice_time"] = time();
            $res1 = Db::name('invoice_records')->strict(false)->update($param);
            //修改收款计划的状态
            $data["id"] = $param["collection_id"];
            $data["status"] = 3;
            $res2 = Db::name('sales_collection')->update($data);
            if(!$res1 || !$res2){
                Db::rollback();
                return jsonFail("开票失败");
            }
            self::actionLog(2,"invoice_records","开票管理",$param["id"],null,"确认开票");
            Db::commit();
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("invoice_records")->find($id);
        $this->assign("row",$row);
        //发票号金额
        $row=db("invoice_nums")->where("i_r_id","eq",$id)->where("status","eq",1)->column("amount");
        $invoice_num_amounts = number_format(array_sum($row),2,'.',"");
        $this->assign("invoice_num_amounts",$invoice_num_amounts);
        return $this->fetch();
    }
    /**
     * 确认收款
     */
    function doCollection(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $param["colletion_status"] = 1;
            $param["colletion_time"] = time();
            if(db('invoice_records')->update($param)){
                self::actionLog(2,"invoice_records","开票管理",$param["id"],null,"确认收款");
            };
            return jsonSuccess();
        }
    }
    /**
     * 取消开票
     */
    function doUnInvoice(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $invoice_record = Db::name('invoice_records')->find($param["id"]);
            //取消开票需要先删除发票
            $invoice_nums = db("invoice_nums")->where(["i_r_id"=>$invoice_record["id"]])->count();
            if($invoice_nums){
                return jsonFail("请先删除已录入的发票记录");
            }
            Db::startTrans();
            $invoice_record["invoice_status"] = 0;
            $invoice_record["invoice_time"] = 0;
            $res1 = Db::name('invoice_records')->update($invoice_record);
            //修改收款计划的状态
            $data["id"] = $invoice_record["collection_id"];
            $data["status"] = 2;
            $res2 = Db::name('sales_collection')->update($data);
            if(!$res1 || !$res2){
                Db::rollback();
                return jsonFail("取消开票失败");
            }
            self::actionLog(2,"invoice_records","开票管理",$param["id"],null,"取消开票");
            Db::commit();
            return jsonSuccess();
        }
    }
    /**
     * 取消收款
     */
    function doUnCollection(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $param["colletion_status"] = 0;
            $param["colletion_time"] = 0;
            if(db('invoice_records')->update($param)){
                self::actionLog(2,"invoice_records","开票管理",$param["id"],null,"取消收款");
            };
            return jsonSuccess();
        }
    }
    /**
     * 获取合同开票收款金额
     * $ic:invoiced/colleted
     */
    public static function getICAmount($contract_id,$ic){
        $db = db("invoice_records")
            ->where("contract_id","eq",$contract_id);
        if($ic == "invoiced"){
            $db = $db->where("invoice_status","=",1);
        }
        if($ic == "colleted"){
            $db = $db->where("colletion_status","=",1);
        }
        $res = $db->column("invoice_amount");
        return array_sum($res);
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
                //检测记录是否可以删除,已开票,已收款不能删除
                if($row["colletion_status"] || $row["invoice_status"]){
                    return jsonFail("已开票或者已收款的记录不可以删除");
                }
                Db::startTrans();
                //修改收款计划的状态
                $data["id"] = $row["collection_id"];
                $data["status"] = 1;
                Db::name('sales_collection')->update($data);
                self::actionLog(3,$this->table,$this->table_name,$id,$row);
                Db::name($this->table)->where("id","eq",$id)->delete();
                Db::commit();
            }
        }
        return jsonSuccess();
    }

}