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
 * Class ProjectREController
 * @package app\admin\controller
 * 项目收支
 */
class ProjectREController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $customer_name=$this->request->param("customer_name");
            $select_by_year = $this->request->param("select_by_year");
            $g_c_id = $this->request->param("g_c_id");
            //默认查询当年数据
            if($select_by_year === null){
                $select_by_year = date("Y");
            }
            $status = $this->request->param("status");
            $colletion_status = $this->request->param("colletion_status");
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
            $db = $db->field("a.contract_name,a.contract_amount,a.customer_name,a.group_company_name,a.sign_date,a.customer_identifier,a.company_identifier,b.*,c.colletion_status,c.invoice_status,d.pay_amount outsourcing_pay_amount,e.amount reimbursement_amount,f.pay_amount_true business_pay_amount,g.pay_amount reward_pay_amount")
                ->leftJoin("sales_collection b","b.contract_id = a.id")
                ->leftJoin("invoice_records c","b.id = c.collection_id")
                ->leftJoin([$subsql_outsourcing_payment=>"d"],"b.id = d.collection_id")
                ->leftJoin([$subsql_expend_reimbursement=>"e"],"b.id = e.collection_id")
                ->leftJoin([$subsql_expend_business=>"f"],"b.id = f.collection_id")
                ->leftJoin([$subsql_expend_reward=>"g"],"b.id = g.collection_id")
            ;
            if(!empty($customer_name)){
                $db->where("a.customer_name","like","%$customer_name%");
            }
            if(!empty($status)){
                $db->where("b.status","eq",$status-1);
            }
            if(!empty($colletion_status)){
                $colletion_status = $colletion_status-1;
                if($colletion_status == 1){
                    $db->where("c.colletion_status","eq",1);
                }else{
                    $db->where("c.colletion_status is null or c.colletion_status = {$colletion_status}");
                }

            }
            if(!empty($select_by_year)){
                $select_by_time=$select_by_year."-01-01 00:00:00";
                //年初时间戳
                $year_start=strtotime($select_by_time);
                //年末时间戳
                $year_end=strtotime("+1 year",$year_start);
                $db->where("a.sign_date","egt",$year_start);
                $db->where("a.sign_date","lt",$year_end);
            }
            if(!empty($g_c_id)){
                $db->where("a.g_c_id","eq",$g_c_id);
            }
            //数据权限
            $db = self::dataPower($db,"a.b_l_id");
            //拷贝查询对象
            $db_cope = unserialize(serialize($db));
            //数据分页查询,处理
            $res = $db->order("a.id desc,b.id asc")->paginate($limit)->toArray();
            $now_time = time();
            foreach ($res["data"] as &$val){
                $val["status_name"] = SalesCollectionController::getStatusName($val["status"]);
                $val["colletion_status_name"] = $val["colletion_status"]==1?"已收款":"未收款";
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
                $val["surplus_format"] = amount_format($val["surplus"]);
                $val["expect_check_time_format"] = $val["expect_check_time"]?date("Y-m-d",$val["expect_check_time"]):"---";
                $val["expect_invoice_time_format"] = $val["expect_invoice_time"]?date("Y-m-d",$val["expect_invoice_time"]):"---";
                $val["expect_colletion_time_format"] = $val["expect_colletion_time"]?date("Y-m-d",$val["expect_colletion_time"]):"---";
                $val["sign_date_format"] = $val["sign_date"]?date("Y-m-d",$val["sign_date"]):"---";
                //状态颜色处理
                if($val["colletion_status_name"] == "已收款"){
                    $val["colletion_status_name"] = "<span style='color: blue'>{$val["colletion_status_name"]}</span>";
                }else{
                    if($val["expect_colletion_time"] == 0 || $val["expect_colletion_time"] > $now_time){
                        $val["colletion_status_name"] = "<span style='color: green'>{$val["colletion_status_name"]}</span>";
                    }else{
                        $val["colletion_status_name"] = "<span style='color: red'>{$val["colletion_status_name"]}({$val["expect_colletion_time_format"]})</span>";
                    }
                }
                if($val["status_name"] == SalesCollectionController::getStatusName(3)){
                    $val["status_name"] = "<span style='color: blue'>{$val["status_name"]}</span>";
                }
                if($val["status_name"] == SalesCollectionController::getStatusName(0)){
                    if($val["expect_check_time"] == 0 || $val["expect_check_time"] > $now_time){
                        $val["status_name"] = "<span style='color: green'>{$val["status_name"]}</span>";
                    }else{
                        $val["status_name"] = "<span style='color: red'>{$val["status_name"]}({$val["expect_check_time_format"]})</span>";
                    }
                }
                if($val["status_name"] == SalesCollectionController::getStatusName(1) || $val["status_name"] == SalesCollectionController::getStatusName(2)){
                    if($val["expect_invoice_time"] == 0 || $val["expect_invoice_time"] > $now_time){
                        $val["status_name"] = "<span style='color: green'>{$val["status_name"]}</span>";
                    }else{
                        $val["status_name"] = "<span style='color: red'>{$val["status_name"]}({$val["expect_invoice_time_format"]})</span>";
                    }
                }
            }
            unset($val);
            //数据统计
            $sql = $db_cope->buildSql();
            $sql = Db::table($sql." a")
                ->field("contract_amount,sum(collection_amount) collection_amount,sum(outsourcing_pay_amount) outsourcing_pay_amount,sum(reimbursement_amount) reimbursement_amount,sum(business_pay_amount) business_pay_amount,sum(reward_pay_amount) reward_pay_amount")
                ->group("a.contract_id")
                ->buildSql();
            $res_statistic = Db::table($sql." b")
                ->field("sum(contract_amount) contract_amount,sum(collection_amount) collection_amount,sum(outsourcing_pay_amount) outsourcing_pay_amount,sum(reimbursement_amount) reimbursement_amount,sum(business_pay_amount) business_pay_amount,sum(reward_pay_amount) reward_pay_amount")
                ->find();
            if(!empty($res["data"])){
                $statistic = $res["data"][0];
                foreach ($res_statistic as $key=>$val){
                    $res_statistic[$key] = $val?:0;
                }
                $res_statistic["surplus"] = $res_statistic["collection_amount"]-$res_statistic["outsourcing_pay_amount"]-$res_statistic["reimbursement_amount"]-$res_statistic["business_pay_amount"]-$res_statistic["reward_pay_amount"];
                foreach ($statistic as $key=>$v){
                    switch ($key){
                        case "id":$statistic[$key]="统计";break;
                        case "contract_amount_format":$statistic[$key]=amount_format($res_statistic["contract_amount"]);break;
                        case "collection_amount_format":$statistic[$key]=amount_format($res_statistic["collection_amount"]);break;
                        case "outsourcing_pay_amount_format":$statistic[$key]=amount_format($res_statistic["outsourcing_pay_amount"]);break;
                        case "reimbursement_amount_format":$statistic[$key]=amount_format($res_statistic["reimbursement_amount"]);break;
                        case "business_pay_amount_format":$statistic[$key]=amount_format($res_statistic["business_pay_amount"]);break;
                        case "reward_pay_amount_format":$statistic[$key]=amount_format($res_statistic["reward_pay_amount"]);break;
                        case "surplus_format":$statistic[$key]=amount_format($res_statistic["surplus"]);break;
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