<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;

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
            $db = db('sales_collection');
            $db = $db->field("a.*,b.*,a.id id,c.colletion_status,c.invoice_status,d.pay_amount outsourcing_pay_amount,e.amount reimbursement_amount,f.pay_amount_true business_pay_amount,g.pay_amount reward_pay_amount")->alias("a")->fetchSql(false)
                ->rightJoin("sales_contract b","a.contract_id = b.id")
                ->leftJoin("invoice_records c","a.id = c.collection_id")
                ->leftJoin([$subsql_outsourcing_payment=>"d"],"a.id = d.collection_id")
                ->leftJoin([$subsql_expend_reimbursement=>"e"],"a.id = e.collection_id")
                ->leftJoin([$subsql_expend_business=>"f"],"a.id = f.collection_id")
                ->leftJoin([$subsql_expend_reward=>"g"],"a.id = g.collection_id")
            ;
            if(!empty($customer_name)){
                $db->where("b.customer_name","like","%$customer_name%");
            }
            $res = $db->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["status_name"] = SalesCollectionController::getStatusName($val["status"]);
                $val["colletion_status_name"] = $val["colletion_status"]==1?"已收款":"未收款";
                $val["outsourcing_pay_amount"] = $val["outsourcing_pay_amount"]?$val["outsourcing_pay_amount"]:'0.00';
                $val["reimbursement_amount"] = $val["reimbursement_amount"]?$val["reimbursement_amount"]:'0.00';
                $val["business_pay_amount"] = $val["business_pay_amount"]?$val["business_pay_amount"]:'0.00';
                $val["reward_pay_amount"] = $val["reward_pay_amount"]?$val["reward_pay_amount"]:'0.00';
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