<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\model\BaseModel;
use app\admin\villdate\ExpendRewardVilldate;

/**
 * 奖金支出管理
 */
class ExpendRewardController extends BaseController
{
    protected $table = "expend_reward";
    protected $table_name = "奖金支出";
    /**
     * 列表
     */
    function index(){
        $pay_status=$this->request->param("pay_status");
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $receiver_id=$this->request->param("receiver_id");
            $contract_name=$this->request->param("contract_name");
            $db=db('expend_reward');
            $db = $db->alias("a")
                ->field("a.*,IFNULL(b.colletion_status,0) colletion_status,c.contract_name contract_name")
                ->leftJoin("invoice_records b","a.collection_id = b.collection_id")
                ->leftJoin("sales_contract c","a.contract_id = c.id");
            if(!empty($receiver_id)){
                $db->where("receiver_id","eq",$receiver_id);
            }
            if(!empty($contract_name)){
                $db->where("c.contract_name","like","%$contract_name%");
            }
            if(!empty($pay_status)){
                $db->where("pay_status","eq",$pay_status-1);
            }
            $res = $db->order("id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                $val["pay_status"] = $val["pay_status"]?"已付款":"<span style='color: red'>未付款</span>";
                $val["colletion_status"] = $val["colletion_status"]?"已收款":"<span style='color: red'>未收款</span>";
                $val["contract_amount"] = amount_format($val["contract_amount"]);
                $val["collection_amount"] = amount_format($val["collection_amount"]);
                $val["deduct_reimbursement"] = amount_format($val["deduct_reimbursement"]);
                $val["deduct_outsource"] = amount_format($val["deduct_outsource"]);
                $val["deduct_business"] = amount_format($val["deduct_business"]);
                $val["surplus_amount"] = amount_format($val["surplus_amount"]);
                $val["pay_amount"] = amount_format($val["pay_amount"]);
                $val["pay_time"] = $val["pay_time"]?date("Y-m-d",$val["pay_time"]):"---";
            }
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        $this->assign("receivers",BaseModel::getAdmins());
        $this->assign("pay_status",$pay_status);
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
            $param["pay_time"] = !empty($param["pay_time"])?strtotime($param["pay_time"]):0;
            $param["receiver"] = db("system_admin")->where(["id"=>$param["receiver_id"]])->value("name")?:"";
            $id=db($this->table)->insert($param,false,true);
            if($id){
                self::actionLog(1,$this->table,$this->table_name,$id);
            }
            return jsonSuccess();
        }
        //销售合同数据
        $res = db('sales_contract')->order("id desc")->select();
        $this->assign("sales_contracts",$res);
        //奖金领取人数据
        $this->assign("receivers",BaseModel::getAdmins());
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
            $param["pay_time"] = !empty($param["pay_time"])?strtotime($param["pay_time"]):0;
            $row = db($this->table)->find($param["id"]);
            if(db($this->table)->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$row["id"],$row);
            }
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("expend_reward")->find($id);
        $row["pay_time"] = !empty($row["pay_time"])?date("Y-m-d",$row["pay_time"]):"";
        $this->assign("row",$row);
        //销售合同数据
        $res = db('sales_contract')->order("id desc")->select();
        $this->assign("sales_contracts",$res);
        //奖金领取人数据
        $this->assign("receivers",BaseModel::getAdmins());
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
        foreach ($id_arr as $id){
            $row = db($this->table)->find($id);
            if(!empty($row)){
                self::actionLog(3,$this->table,$this->table_name,$id,$row);
                db($this->table)->where("id","eq",$id)->delete();
            }
        }
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