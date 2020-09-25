<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\villdate\InvoiceRecordsVilldate;
use think\Db;

/**
 * 开票管理
 */
class InvoiceRecordsController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $sq_people=$this->request->param("sq_people");
            $contract_name=$this->request->param("contract_name");
            $db=db('invoice_records');
            if(!empty($sq_people)){
                $db->where("sq_people","like","%$sq_people%");
            }
            if(!empty($contract_name)){
                $db->where("contract_name","like","%$contract_name%");
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
        return $this->fetch();
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
            db('invoice_records')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("invoice_records")->find($id);
        $row["invoice_time"] = date("Y-m-d",$row["invoice_time"]);
        $row["colletion_time"] = date("Y-m-d",$row["colletion_time"]);
        $this->assign("row",$row);
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
            Db::commit();
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("invoice_records")->find($id);
        $this->assign("row",$row);
        //发票号金额
        $row=db("invoice_nums")->where("i_r_id","eq",$id)->column("amount");
        $invoice_num_amounts = array_sum($row);
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
            db('invoice_records')->update($param);
            return jsonSuccess();
        }
    }

}