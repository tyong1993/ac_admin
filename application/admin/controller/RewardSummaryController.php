<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;

use app\admin\model\BaseModel;

/**
 * 奖金汇总
 */
class RewardSummaryController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        $receiver_id = $this->request->param("receiver_id");
        $contract_name = $this->request->param("contract_name");
        $select_by_year = $this->request->param("select_by_year");
        if($select_by_year === null){
            $select_by_year = date("Y");
        }

        $db=db('expend_reward a')
            ->field("a.*,IFNULL(c.colletion_status,0) colletion_status")
            ->leftJoin("invoice_records c","a.collection_id = c.collection_id");
        if(!empty($select_by_year)){
            $select_by_time=$select_by_year."-01-01 00:00:00";
            //年初时间戳
            $year_start=strtotime($select_by_time);
            //年末时间戳
            $year_end=strtotime("+1 year",$year_start);
            $db= $db->where("a.pay_time","egt",$year_start)
                    ->where("a.pay_time","lt",$year_end);
        }

        if(!empty($receiver_id)){
            $db->where("a.receiver_id","eq",$receiver_id);
        }
        if(!empty($contract_name)){
            $db->where("a.contract_name","like","%$contract_name%");
        }
        $res = $db->select();
        $data = [];
        foreach ($res as $val){
            if(!isset($data[$val["receiver_id"]])){
                $data[$val["receiver_id"]] = [
                    "receiver"=>$val["receiver"],
                    "pay_amount"=>0,
                    "data"=>[]
                ];
            }
            $val["pay_status"] = $val["pay_status"]?"已付款":"<span style='color: red'>未付款</span>";
            $val["colletion_status"] = $val["colletion_status"]?"已收款":"<span style='color: red'>未收款</span>";
            $val["pay_time"] = $val["pay_time"]?date("Y-m-d",$val["pay_time"]):"---";
            $data[$val["receiver_id"]]["data"][]=$val;
            $data[$val["receiver_id"]]["pay_amount"]+=$val["pay_amount"];
        }
        $this->assign("res",$data);
        $this->assign("receiver_id",$receiver_id);
        $this->assign("receivers",BaseModel::getAdmins());
        $this->assign("contract_name",$contract_name);
        $this->assign("select_by_year",$select_by_year);
        return $this->fetch();
    }

}