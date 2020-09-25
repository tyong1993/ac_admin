<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;

/**
 * Class BusinessSummaryController
 * @package app\admin\controller
 * 商务汇总
 */
class BusinessSummaryController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $business_contact=$this->request->param("business_contact");
            $contract_name=$this->request->param("contract_name");
            //合同收款金额子查询
            $subsql_invoice_records = db("invoice_records")
                ->field('contract_id,sum(if(colletion_status = 1,invoice_amount,0)) colletion_amount')
                ->group('contract_id')
                ->buildSql();
            //合同商务金额子查询
            $subsql_expend_business = db("expend_business")
                ->field('contract_id,sum(pay_amount_true) all_need_pay_amount,sum(if(pay_status = 1,0,pay_amount_true)) all_unpay_amount')
                ->group('contract_id')
                ->buildSql();

            $db = db('sales_contract a');
            $db = $db->field("a.id,a.contract_name,a.contract_amount,a.business_contact,b.colletion_amount,c.all_need_pay_amount,c.all_unpay_amount")
                ->fetchSql(false)
                ->leftJoin([$subsql_invoice_records=>"b"],"a.id = b.contract_id")
                ->leftJoin([$subsql_expend_business=>"c"],"a.id = c.contract_id")
            ;
            if(!empty($business_contact)){
                $db->where("business_contact","like","%$business_contact%");
            }
            if(!empty($contract_name)){
                $db->where("contract_name","like","%$contract_name%");
            }
            $res = $db->order("a.id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["contract_amount"] = amount_format($val["contract_amount"]);
                $val["colletion_amount"] = amount_format($val["colletion_amount"]?$val["colletion_amount"]:'0.00');
                $val["all_need_pay_amount"] = amount_format($val["all_need_pay_amount"]?$val["all_need_pay_amount"]:'0.00');
                $val["all_unpay_amount"] = amount_format($val["all_unpay_amount"]?$val["all_unpay_amount"]:'0.00');
                $val["all_payed_amount"] = amount_format($val["all_need_pay_amount"]-$val["all_unpay_amount"]);
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
            $param["create_time"] = time();
            db('customer_supplier')->insert($param);
            return jsonSuccess();
        }
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            db('customer_supplier')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("customer_supplier")->find($id);
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
        db('customer_supplier')->where("id","in",$id_arr)->delete();
        return jsonSuccess();
    }
}