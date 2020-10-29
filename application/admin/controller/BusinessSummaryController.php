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
        $business_contact = $this->request->param("business_contact");
        $contract_name = $this->request->param("contract_name");
        $select_by_year = $this->request->param("select_by_year");
        if($select_by_year === null){
            $select_by_year = date("Y");
        }

        $db=db('expend_business a')->field("a.*")
            ->leftJoin("sales_contract b","b.id = a.contract_id");
        //不限制年份
        if(!empty($select_by_year)){
            $select_by_time=$select_by_year."-01-01 00:00:00";
            //年初时间戳
            $year_start=strtotime($select_by_time);
            //年末时间戳
            $year_end=strtotime("+1 year",$year_start);
            $db = $db->where("a.pay_time","egt",$year_start)
                     ->where("a.pay_time","lt",$year_end);
        }
        if(!empty($business_contact)){
            $db->where("a.business_contact","like","%$business_contact%");
        }
        if(!empty($contract_name)){
            $db->where("b.contract_name","like","%$contract_name%");
        }
        $res = $db->select();
        $data = [];
        foreach ($res as $val){
            if(!isset($data[$val["business_contact"]])){
                $data[$val["business_contact"]] = [
                    "business_contact"=>$val["business_contact"],
                    "pay_amount_true"=>0,
                    "data"=>[]
                ];
            }
            $val["pay_status"] = $val["pay_status"]?"<span style='color: blue'>已付款</span>":"<span style='color: red'>未付款</span>";
            $val["pay_time"] = $val["pay_time"]?date("Y-m-d",$val["pay_time"]):"---";
            $data[$val["business_contact"]]["data"][]=$val;
            $data[$val["business_contact"]]["pay_amount_true"]+=$val["pay_amount_true"];
        }
        $this->assign("res",$data);
        $this->assign("business_contact",$business_contact);
        $this->assign("contract_name",$contract_name);
        $this->assign("select_by_year",$select_by_year);
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