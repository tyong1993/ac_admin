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
            $db=db('invoice_records');
            if(!empty($sq_people)){
                $db->where("sq_people","like","%$sq_people%");
            }
            $res = $db->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                $val["invoice_time"] = !empty($val["invoice_time"])?date("Y-m-d H:i",$val["invoice_time"]):"---";
                $val["tax_rate"] = !empty($val["tax_rate"])?$val["tax_rate"]:"---";
            }
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        return $this->fetch();
    }

    /**
     * 确认开票
     */
    function doInvoice(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,InvoiceRecordsVilldate::class);
            Db::startTrans();
            $param["invoice_status"] = 1;
            $param["invoice_time"] = time();
            $res1 = Db::name('invoice_records')->update($param);
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
        return $this->fetch();
    }
    /**
     * 确认收款
     */
    function doCollection(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $param["colletion_status"] = 1;
            db('invoice_records')->update($param);
            return jsonSuccess();
        }
    }

}