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
        if(!$select_by_year){
            $select_by_year = date("Y");
        }
        $select_by_time=$select_by_year."-01-01 00:00:00";
        //年初时间戳
        $year_start=strtotime($select_by_time);
        //年末时间戳
        $year_end=strtotime("+1 year",$year_start);

        $db=db('expend_reward a')
            ->where("a.create_time","egt",$year_start)
            ->where("a.create_time","lt",$year_end);
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
            $val["pay_status"] = $val["pay_status"]?"<span style='color: blue'>已付款</span>":"<span style='color: red'>未付款</span>";
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