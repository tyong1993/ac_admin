<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;

/**
 * 开票明细
 */
class InvoiceNumsStatisticsController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        $customer_name = $this->request->param("customer_name");
        $g_c_id = $this->request->param("g_c_id");
        $num = $this->request->param("num");
        $select_by_year = $this->request->param("select_by_year");
        if(!$select_by_year){
            $select_by_year = date("Y");
        }
        $select_by_time=$select_by_year."-01-01 00:00:00";
        //年初时间戳
        $year_start=strtotime($select_by_time);
        //年末时间戳
        $year_end=strtotime("+1 year",$year_start);

        $db=db('invoice_nums a')->field("a.id,a.num,a.amount,a.status,a.remark,b.colletion_status,b.invoice_time,c.contract_name,c.customer_name");
        $db->leftJoin("invoice_records b","a.i_r_id = b.id")
            ->leftJoin("sales_contract c","c.id = b.contract_id")
            ->where("invoice_time","egt",$year_start)
            ->where("invoice_time","lt",$year_end);
        if($customer_name){
            $db->where("customer_name","like","%{$customer_name}%");
        }
        if(!empty($g_c_id)){
            $db->where("c.g_c_id","eq",$g_c_id);
        }
        if(!empty($num)){
            $db->where("a.num","like","%$num%");
        }
        $res = $db->order("contract_id asc,a.id asc")->select();
        //组装数据结构
        $data = [];$init_start = $year_start;
        for($i = 1;$i<=12;$i++){
            $name = $i."月小计";
            $month_start = $init_start;
            $month_end = strtotime("+1 month",$month_start);
            $data[]=[
                "name"=>$name,
                "month_start"=>$month_start,
                "month_end"=>$month_end,
                "month_start_str"=>date("Y-m-d",$month_start),
                "month_end_str"=>date("Y-m-d",$month_end),
                "data"=>[],
                "all_amount"=>0.00,
            ];
            $init_start = $month_end;
        }
        foreach ($res as $val){
            foreach ($data as &$dat){
                if($val["invoice_time"]>=$dat["month_start"] && $val["invoice_time"]<$dat["month_end"]){
                    $val["invoice_time"] = $val["invoice_time"]?date("Y-m-d",$val["invoice_time"]):"";
                    $val["amount_format"] = amount_format($val["amount"]);
                    if(!$val["status"]){
                        $val["amount_format"] = "<span style='color: red'>".$val["amount_format"]."</span>";
                    }
                    $val["status_name"] = $val["status"]?"正常":"<span style='color: red'>已作废</span>";
                    $val["colletion_status"] = $val["colletion_status"]?"<span style='color:blue;'>已回款</span>":"<span style='color:red;'>未回款</span>";
                    $dat["data"][]=$val;break;
                }
            }
        }
        unset($dat);
        //数据处理
        foreach ($data as $key=>&$dat){
            if(empty($dat["data"])){
                unset($data[$key]);
            }else{
                foreach ($dat["data"] as $val){
                    if($val["status"]){
                        $dat["all_amount"]+=$val["amount"];
                    }
                }
                $dat["all_amount"] = "<strong>".amount_format($dat["all_amount"])."</strong>";
            }
        }
        $this->assign("res",$data);
        $this->assign("customer_name",$customer_name);
        $this->assign("select_by_year",$select_by_year);
        //签约单位数据
        $res = db('group_company')->select();
        $this->assign("group_companys",$res);
        $this->assign("g_c_id",$g_c_id);
        $this->assign("num",$num);
        return $this->fetch();
    }

}