<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;

/**
 * 项目收支统计
 */
class ProjectREStatisticsController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        $customer_name = $this->request->param("customer_name");
        $select_by_year = $this->request->param("select_by_year");
        $g_c_id = $this->request->param("g_c_id");
        $statistic_field = $this->request->param("statistic_field")?:"colletion_time";
        if(!$select_by_year){
            $select_by_year = date("Y");
        }
        $select_by_time=$select_by_year."-01-01 00:00:00";
        //年初时间戳
        $year_start=strtotime($select_by_time);
        //年末时间戳
        $year_end=strtotime("+1 year",$year_start);

        //外包支出子查询
        $subsql_outsourcing_payment = db("outsourcing_payment")
            ->field('sales_collection_id collection_id,sum(pay_amount) pay_amount')
            ->group('sales_collection_id')
            ->buildSql();
        //报销支出子查询
        $subsql_expend_reimbursement = db("expend_reimbursement")
            ->field('collection_id,sum(amount) amount')
            ->group('collection_id')
            ->buildSql();
        //商务支出子查询
        $subsql_expend_business = db("expend_business")
            ->field('collection_id,sum(pay_amount_true) pay_amount_true')
            ->group('collection_id')
            ->buildSql();
        //奖金支出子查询
        $subsql_expend_reward = db("expend_reward")
            ->field('collection_id,sum(pay_amount) pay_amount')
            ->group('collection_id')
            ->buildSql();
        $db = db('sales_contract a');
        $db = $db->field("a.contract_name,a.contract_amount,a.customer_name,a.group_company_name,a.sign_date,a.company_identifier,b.*,c.colletion_time,c.invoice_time,c.invoice_amount,d.pay_amount outsourcing_pay_amount,e.amount reimbursement_amount,f.pay_amount_true business_pay_amount,g.pay_amount reward_pay_amount")
            ->rightJoin("sales_collection b","b.contract_id = a.id")
            ->leftJoin("invoice_records c","b.id = c.collection_id")
            ->leftJoin([$subsql_outsourcing_payment=>"d"],"b.id = d.collection_id")
            ->leftJoin([$subsql_expend_reimbursement=>"e"],"b.id = e.collection_id")
            ->leftJoin([$subsql_expend_business=>"f"],"b.id = f.collection_id")
            ->leftJoin([$subsql_expend_reward=>"g"],"b.id = g.collection_id")
            ->where(str_replace("time","status",$statistic_field),"eq",1)
            ->where($statistic_field,"egt",$year_start)
            ->where($statistic_field,"lt",$year_end)
        ;
        if(!empty($customer_name)){
            $db->where("a.customer_name","like","%$customer_name%");
        }
        if(!empty($g_c_id)){
            $db->where("a.g_c_id","eq",$g_c_id);
        }
        //数据权限
        $db = self::dataPower($db,"a.b_l_id");
        $res = $db->order("$statistic_field asc")->select();
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
                "statistics"=>[
                    //"contract_amount"=>0
                ],
                //"contract_ids_box" => []
            ];
            $init_start = $month_end;
        }
        foreach ($res as $val){
            foreach ($data as &$dat){
                if($val[$statistic_field]>=$dat["month_start"] && $val[$statistic_field]<$dat["month_end"]){
                    $val["colletion_time"] = $val["colletion_time"]?date("Y-m-d",$val["colletion_time"]):"---";
                    $val["invoice_time"] = $val["invoice_time"]?date("Y-m-d",$val["invoice_time"]):"---";
                    $val["outsourcing_pay_amount"] = $val["outsourcing_pay_amount"]?$val["outsourcing_pay_amount"]:'0.00';
                    $val["reimbursement_amount"] = $val["reimbursement_amount"]?$val["reimbursement_amount"]:'0.00';
                    $val["business_pay_amount"] = $val["business_pay_amount"]?$val["business_pay_amount"]:'0.00';
                    $val["reward_pay_amount"] = $val["reward_pay_amount"]?$val["reward_pay_amount"]:'0.00';
                    $val["surplus"] = $val["collection_amount"]-$val["outsourcing_pay_amount"]-$val["reimbursement_amount"]-$val["business_pay_amount"]-$val["reward_pay_amount"];
                    $val["outsourcing_pay_amount_format"] = amount_format($val["outsourcing_pay_amount"]);
                    $val["reimbursement_amount_format"] = amount_format($val["reimbursement_amount"]);
                    $val["business_pay_amount_format"] = amount_format($val["business_pay_amount"]);
                    $val["reward_pay_amount_format"] = amount_format($val["reward_pay_amount"]);
                    $val["contract_amount_format"] = amount_format($val["contract_amount"]);
                    $val["collection_amount_format"] = amount_format($val["collection_amount"]);
                    $val["invoice_amount_format"] = amount_format($val["invoice_amount"]);
                    $val["surplus_format"] = amount_format($val["surplus"]);
//                    if(!in_array($val["contract_id"],$dat["contract_ids_box"])){
//                        $dat["statistics"]["contract_amount"] += $val["contract_amount"];
//                    }
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
                $dat["statistics"]["collection_amount_format"]="<strong>".amount_format(array_sum(array_column($dat["data"],"collection_amount")))."</strong>";
                $dat["statistics"]["outsourcing_pay_amount_format"]="<strong>".amount_format(array_sum(array_column($dat["data"],"outsourcing_pay_amount")))."</strong>";
                $dat["statistics"]["reimbursement_amount_format"]="<strong>".amount_format(array_sum(array_column($dat["data"],"reimbursement_amount")))."</strong>";
                $dat["statistics"]["business_pay_amount_format"]="<strong>".amount_format(array_sum(array_column($dat["data"],"business_pay_amount")))."</strong>";
                $dat["statistics"]["reward_pay_amount_format"]="<strong>".amount_format(array_sum(array_column($dat["data"],"reward_pay_amount")))."</strong>";
                $dat["statistics"]["invoice_amount_format"]="<strong>".amount_format(array_sum(array_column($dat["data"],"invoice_amount")))."</strong>";
                $dat["statistics"]["surplus_format"]="<strong>".amount_format(array_sum(array_column($dat["data"],"surplus")))."</strong>";
//                $dat["statistics"]["contract_amount_format"]=amount_format($dat["statistics"]["contract_amount"]);
            }
        }
        $this->assign("res",$data);
        $this->assign("customer_name",$customer_name);
        $this->assign("select_by_year",$select_by_year);
        $this->assign("statistic_field",$statistic_field);
        //签约单位数据
        $res = db('group_company')->select();
        $this->assign("group_companys",$res);
        $this->assign("g_c_id",$g_c_id);
        return $this->fetch();
    }

}