<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\villdate\ExpendRewardVilldate;

/**
 * 奖金支出管理
 */
class ExpendRewardController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $receiver=$this->request->param("receiver");
            $contract_name=$this->request->param("contract_name");
            $db=db('expend_reward');
            if(!empty($receiver)){
                $db->where("receiver","like","%$receiver%");
            }
            if(!empty($contract_name)){
                $db->where("contract_name","like","%$contract_name%");
            }
            $res = $db->order("id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                $val["pay_status"] = $val["pay_status"]?"已付款":"未付款";
                $val["contract_amount"] = amount_format($val["contract_amount"]);
                $val["collection_amount"] = amount_format($val["collection_amount"]);
                $val["deduct_reimbursement"] = amount_format($val["deduct_reimbursement"]);
                $val["deduct_outsource"] = amount_format($val["deduct_outsource"]);
                $val["deduct_business"] = amount_format($val["deduct_business"]);
                $val["surplus_amount"] = amount_format($val["surplus_amount"]);
                $val["pay_amount"] = amount_format($val["pay_amount"]);
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
            $this->validate($param,ExpendRewardVilldate::class);
            $param["create_time"] = time();
            $param["receiver"] = db("system_admin")->where(["id"=>$param["receiver_id"]])->value("name")?:"";
            db('expend_reward')->insert($param);
            return jsonSuccess();
        }
        //销售合同数据
        $res = db('sales_contract')->order("id desc")->select();
        $this->assign("sales_contracts",$res);
        //奖金领取人数据
        $res = db('system_admin')->where("status","eq",1)->where("id","neq",1)->select();
        $this->assign("receivers",$res);
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,ExpendRewardVilldate::class);
            $param["receiver"] = db("system_admin")->where(["id"=>$param["receiver_id"]])->value("name")?:"";
            db('expend_reward')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("expend_reward")->find($id);
        $this->assign("row",$row);
        //销售合同数据
        $res = db('sales_contract')->order("id desc")->select();
        $this->assign("sales_contracts",$res);
        //奖金领取人数据
        $res = db('system_admin')->where("status","eq",1)->where("id","neq",1)->select();
        $this->assign("receivers",$res);
        //收款期数数据
        $res = db('sales_collection')->where(["contract_id"=>$row["contract_id"]])->order("id desc")->select();
        $this->assign("sales_collections",$res);
        //该期收款情况
        $res = db('sales_collection a')->field("a.*,b.colletion_status")->leftJoin("invoice_records b","a.id = b.collection_id")->where("a.id","eq",$row["collection_id"])->find();
        $this->assign("now_periods",$res);
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
        db('expend_reward')->where("id","in",$id_arr)->delete();
        return jsonSuccess();
    }
    /**
     * 获取销售合同对应的收款期数
     */
    function getSalesCollections(){
        $contract_id = $this->request->param("contract_id");
        $res = db('sales_collection')->where(["contract_id"=>$contract_id])->order("id desc")->select();
        $templete = "";
        $templete .= '<option value="">请选择收款期数</option>';
        foreach ($res as $val){
            $templete .= '<option value="'.$val['id'].'|-|'.$val['periods'].'|-|'.$val['collection_amount'].'">'.$val['periods'].'</option>';
        }
        return jsonSuccess($templete);
    }
    /**
     * 获取支出费用
     */
    function getExpends(){
        $collection_id = $this->request->param("collection_id");
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
        $db = db('sales_collection a');
        $db = $db->field("d.pay_amount outsourcing_pay_amount,e.amount reimbursement_amount,f.pay_amount_true business_pay_amount")
            ->leftJoin([$subsql_outsourcing_payment=>"d"],"a.id = d.collection_id")
            ->leftJoin([$subsql_expend_reimbursement=>"e"],"a.id = e.collection_id")
            ->leftJoin([$subsql_expend_business=>"f"],"a.id = f.collection_id")
        ;
        $res =  $db->find($collection_id);
        $res["outsourcing_pay_amount"] = $res["outsourcing_pay_amount"]?:0.00;
        $res["reimbursement_amount"] = $res["reimbursement_amount"]?:0.00;
        $res["business_pay_amount"] = $res["business_pay_amount"]?:0.00;
        return jsonSuccess($res);
    }
}