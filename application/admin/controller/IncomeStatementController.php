<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use think\Db;

/**
 * 利润表
 */
class IncomeStatementController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $contract_name=$this->request->param("contract_name");
            $g_c_id = $this->request->param("g_c_id");
            $select_by_year = $this->request->param("select_by_year");
            //默认查询当年数据
            if($select_by_year === null){
                $select_by_year = date("Y");
            }
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
            $db = $db->field("a.id,a.contract_name,a.contract_amount,sum(if(c.colletion_status = 1,invoice_amount,0)) collected_amount,sum(d.pay_amount) outsourcing_pay_amount,sum(e.amount) reimbursement_amount,sum(f.pay_amount_true) business_pay_amount,sum(g.pay_amount) reward_pay_amount")
                ->leftJoin("sales_collection b","b.contract_id = a.id")
                ->leftJoin("invoice_records c","b.id = c.collection_id")
                ->leftJoin([$subsql_outsourcing_payment=>"d"],"b.id = d.collection_id")
                ->leftJoin([$subsql_expend_reimbursement=>"e"],"b.id = e.collection_id")
                ->leftJoin([$subsql_expend_business=>"f"],"b.id = f.collection_id")
                ->leftJoin([$subsql_expend_reward=>"g"],"b.id = g.collection_id")
                ->group('a.id')
            ;
            if(!empty($contract_name)){
                $db->where("a.contract_name","like","%$contract_name%");
            }
            if(!empty($select_by_year)){
                $select_by_time=$select_by_year."-01-01 00:00:00";
                //年初时间戳
                $year_start=strtotime($select_by_time);
                //年末时间戳
                $year_end=strtotime("+1 year",$year_start);
                $db->where("a.sign_date","egt",$year_start);
                $db->where("a.sign_date","lt",$year_end);
            }if(!empty($g_c_id)){
                $db->where("a.g_c_id","eq",$g_c_id);
            }
            //拷贝查询对象
            $db_cope = unserialize(serialize($db));
            //数据分页查询,处理
            $res = $db->order("a.id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["outsourcing_pay_amount"] = $val["outsourcing_pay_amount"]?$val["outsourcing_pay_amount"]:'0.00';
                $val["reimbursement_amount"] = $val["reimbursement_amount"]?$val["reimbursement_amount"]:'0.00';
                $val["business_pay_amount"] = $val["business_pay_amount"]?$val["business_pay_amount"]:'0.00';
                $val["reward_pay_amount"] = $val["reward_pay_amount"]?$val["reward_pay_amount"]:'0.00';
                $val["surplus"] = $val["contract_amount"]-$val["outsourcing_pay_amount"]-$val["reimbursement_amount"]-$val["business_pay_amount"]-$val["reward_pay_amount"];
                $val["outsourcing_pay_amount_format"] = amount_format($val["outsourcing_pay_amount"]);
                $val["reimbursement_amount_format"] = amount_format($val["reimbursement_amount"]);
                $val["business_pay_amount_format"] = amount_format($val["business_pay_amount"]);
                $val["reward_pay_amount_format"] = amount_format($val["reward_pay_amount"]);
                $val["contract_amount_format"] = amount_format($val["contract_amount"]);
                $val["surplus_format"] = amount_format($val["surplus"]);
                $val["collected_amount_format"] = amount_format($val["collected_amount"]);
            }
            unset($val);
            //数据统计
            $sql = $db_cope->buildSql();
            $res_statistic = Db::table($sql." a")->field("sum(contract_amount) contract_amount,sum(collected_amount) collected_amount,sum(outsourcing_pay_amount) outsourcing_pay_amount,sum(reimbursement_amount) reimbursement_amount,sum(business_pay_amount) business_pay_amount,sum(reward_pay_amount) reward_pay_amount")->find();
            if(!empty($res["data"])){
                $statistic = $res["data"][0];
                foreach ($res_statistic as $key=>$val){
                    $res_statistic[$key] = $val?:0;
                }
                $res_statistic["surplus"] = $res_statistic["contract_amount"]-$res_statistic["outsourcing_pay_amount"]-$res_statistic["reimbursement_amount"]-$res_statistic["business_pay_amount"]-$res_statistic["reward_pay_amount"];
                foreach ($statistic as $key=>$v){
                    switch ($key){
                        case "id":$statistic[$key]="统计";break;
                        case "contract_amount_format":$statistic[$key]=amount_format($res_statistic["contract_amount"]);break;
                        case "outsourcing_pay_amount_format":$statistic[$key]=amount_format($res_statistic["outsourcing_pay_amount"]);break;
                        case "reimbursement_amount_format":$statistic[$key]=amount_format($res_statistic["reimbursement_amount"]);break;
                        case "business_pay_amount_format":$statistic[$key]=amount_format($res_statistic["business_pay_amount"]);break;
                        case "reward_pay_amount_format":$statistic[$key]=amount_format($res_statistic["reward_pay_amount"]);break;
                        case "surplus_format":$statistic[$key]=amount_format($res_statistic["surplus"]);break;
                        case "collected_amount_format":$statistic[$key]=amount_format($res_statistic["collected_amount"]);break;
                        default:$statistic[$key]="";
                    }
                }
                $res["data"][]=$statistic;
            }
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        $this->assign("select_by_year",date("Y"));
        //签约单位数据
        $res = db('group_company')->select();
        $this->assign("group_companys",$res);
        return $this->fetch();
    }

}