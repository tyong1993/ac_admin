<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\villdate\PaymentRecordsVilldate;
use think\Db;

/**
 * 付款管理
 */
class PaymentRecordsController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $sq_people=$this->request->param("sq_people");
            $contract_name=$this->request->param("contract_name");
            $db=db('payment_records a');
            $db = $db->field("a.*,b.contract_name contract_name,c.remarks")
                ->leftJoin("outsourcing_contract b","a.contract_id = b.id")
                ->leftJoin("outsourcing_payment c","a.payment_id = c.id");
            if(!empty($sq_people)){
                $db->where("sq_people","like","%$sq_people%");
            }
            if(!empty($contract_name)){
                $db->where("b.contract_name","like","%$contract_name%");
            }
            $res = $db->order("id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                $val["invoice_time"] = !empty($val["invoice_time"])?date("Y-m-d",$val["invoice_time"]):"---";
                $val["pay_time"] = !empty($val["pay_time"])?date("Y-m-d",$val["pay_time"]):"---";
                $val["tax_rate"] = !empty($val["tax_rate"])?$val["tax_rate"]:"---";
                $val["pay_amount"] = amount_format($val["pay_amount"]);
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
            $this->validate($param,PaymentRecordsVilldate::class);
            $param["invoice_time"] = strtotime($param["invoice_time"]);
            $param["pay_time"] = strtotime($param["pay_time"]);
            db('outsourcing_payment')->update(["id"=>$param["payment_id"],"remarks"=>$param["payment_remarks"]]);
            unset($param["payment_id"]);unset($param["payment_remarks"]);
            db('payment_records')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("payment_records")->find($id);
        $row["invoice_time"] = $row["invoice_time"]?date("Y-m-d",$row["invoice_time"]):"";
        $row["pay_time"] = $row["pay_time"]?date("Y-m-d",$row["pay_time"]):"";
        $this->assign("row",$row);
        $payment=db("outsourcing_payment")->find($row["payment_id"]);
        $this->assign("payment",$payment);
        return $this->fetch();
    }
    /**
     * 确认付款
     */
    function doPayment(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,PaymentRecordsVilldate::class);
            Db::startTrans();
            $param["pay_status"] = 1;
            $param["pay_time"] = time();
            $res1 = Db::name('payment_records')->update($param);
            //修改收款计划的状态
            $data["id"] = $param["payment_id"];
            $data["status"] = 3;
            $res2 = Db::name('outsourcing_payment')->update($data);
            if(!$res1 || !$res2){
                Db::rollback();
                return jsonFail("付款失败");
            }
            self::actionLog(2,"payment_records","付款管理",$param["id"],null,"确认付款");
            Db::commit();
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("payment_records")->find($id);
        $this->assign("row",$row);
        return $this->fetch();
    }
    /**
     * 确认收票
     */
    function doCollection(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $param["invoice_status"] = 1;
            $param["invoice_time"] = time();
            if(db('payment_records')->update($param)){
                self::actionLog(2,"payment_records","付款管理",$param["id"],null,"确认收票");
            };
            return jsonSuccess();
        }
    }

}