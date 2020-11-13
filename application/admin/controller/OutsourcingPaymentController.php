<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\model\SystemAdminModel;
use app\admin\villdate\OutsourcingPaymentVilldate;
use think\Db;

/**
 * 外包合同付款计划
 */
class OutsourcingPaymentController extends BaseController
{
    protected $table = "outsourcing_payment";
    protected $table_name = "付款计划";
    /**
     * 列表
     */
    function index(){
        $contract_id = $this->request->param("id");
        $db=db('outsourcing_payment');
        $db->where("contract_id","=",$contract_id);
        $res = $db->select();
        //合同税率
        $row = db('outsourcing_contract')->find($contract_id);
        $tax_rate = $row["is_contain_tax"] == 1?"---":$row["tax_rate"];
        //收款金额合计,税额,合同总金额,合同ID
        $others=[
            "skzjehj"=>0.00,
            "htzje"=>$row["all_amount"],
            "htid"=>$row["id"],
        ];
        foreach ($res as &$val){
            $val["tax_rate"] = $tax_rate;
            $val["check_time"] = $val["check_time"]?date("Y-m-d",$val["check_time"]):"---";
            $val["status_name"] = $this->getStatusName($val["status"]);
            $val["pay_amount_format"] = amount_format($val["pay_amount"]);
            $val["all_pay_amount_format"] = amount_format($val["all_pay_amount"]);
            $val["unpay_amount"] = amount_format($val["all_pay_amount"]-$val["payed"]);
            $others["skzjehj"] += $val["all_pay_amount"];
        }
        $others["skzjehj"] = (string)$others["skzjehj"];
        $others["htzje_format"] = amount_format($others["htzje"]);
        $others["skzjehj_format"] = amount_format($others["skzjehj"]);
        $this->assign("res",$res);
        $this->assign("others",$others);
        return $this->fetch();
    }

    /**
     * 添加
     */
    function add(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,OutsourcingPaymentVilldate::class);
            $param["sales_periods"] = db('sales_collection')->where("id","eq",$param["sales_collection_id"])->value("periods");
            $id=db($this->table)->strict(false)->insert($param,false,true);
            if($id){
                self::actionLog(1,$this->table,$this->table_name,$id);
            }
            return jsonSuccess();
        }
        $contract_id = $this->request->param("contract_id");
        $this->assign("contract_id",$contract_id);
        //销售合同收款期数
        $outsourcing_contract = db("outsourcing_contract")->find($contract_id);
        $sales_collections = db("sales_collection")->where(["contract_id"=>$outsourcing_contract["sales_contract_id"]])->select();
        $this->assign("sales_collections",$sales_collections);
        //期数预测
        $before = db('outsourcing_payment')->where("contract_id","=",$contract_id)->order("id desc")->find();
        if(empty($before)){
            $maybe = "第一期";
        }else{
            $maybe = SalesCollectionController::getPeriodsForSelect($before["periods"]);
        }
        //税率
        $tax_rate = $outsourcing_contract["is_contain_tax"]?"---":$outsourcing_contract["tax_rate"];
        $this->assign("maybe",$maybe);
        $this->assign("tax_rate",$tax_rate);
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,OutsourcingPaymentVilldate::class);
            $param["sales_periods"] = db('sales_collection')->where("id","eq",$param["sales_collection_id"])->value("periods");
            $param["check_time"] = !empty($param["check_time"])?strtotime($param["check_time"]):0;
            $param["pay_time"] = !empty($param["pay_time"])?strtotime($param["pay_time"]):0;
            $row = db($this->table)->find($param["id"]);
            if(db($this->table)->strict(false)->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$row["id"],$row);
            }
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("outsourcing_payment")->find($id);
        $row["check_time"] = !empty($row["check_time"])?date("Y-m-d",$row["check_time"]):"";
        $row["pay_time"] = !empty($row["pay_time"])?date("Y-m-d",$row["pay_time"]):"";
        $this->assign("row",$row);
        //销售合同收款期数
        $outsourcing_contract = db("outsourcing_contract")->find($row["contract_id"]);
        $sales_collections = db("sales_collection")->where(["contract_id"=>$outsourcing_contract["sales_contract_id"]])->select();
        $this->assign("sales_collections",$sales_collections);
        //税率
        $tax_rate = $outsourcing_contract["is_contain_tax"]?"---":$outsourcing_contract["tax_rate"];
        $this->assign("tax_rate",$tax_rate);
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
     * 待验收
     */
    function status0(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            //可开票
            $param["status"] = 1;
            $param["check_time"] = time();
            if(db('outsourcing_payment')->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$param["id"],null,"验收");
            }
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("outsourcing_payment")->find($id);
        $this->assign("row",$row);
        return $this->fetch();
    }
    /**
     * 可开票
     */
    function status1(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            if(empty($param["pay_amount"])){
                return jsonFail("请填写付款金额");
            }
            Db::startTrans();
            //申请付款
            $outsourcing_payment["id"] = $param["id"];
            $outsourcing_payment["status"] = 2;
            $outsourcing_payment["payed"] = $param["pay_amount"];
            $res1 = Db::name('outsourcing_payment')->update($outsourcing_payment);
            //添加待付款记录
            $admin = self::$login_admin;
            $data["contract_id"] = $param["contract_id"];
            $data["payment_id"] = $param["id"];
            $data["contract_name"] = $param["contract_name"];
            $data["pay_amount"] = $param["pay_amount"];
            $data["surplus_amount"] = $param["all_pay_amount"]-$param["pay_amount"];
            $data["periods"] = $param["periods"];
            $data["sq_people"] = $admin["name"];
            $data["payee"] = $param["payee"];
            $data["bank_name"] = $param["bank_name"];
            $data["bank_account"] = $param["bank_account"];
            $data["branch"] = $param["branch"];
            $data["create_time"] = time();
            $res2 = Db::name("payment_records")->insert($data);
            if(!$res1 || !$res2){
                Db::rollback();
                return jsonFail("申请付款失败");
            }
            self::actionLog(2,$this->table,$this->table_name,$param["id"],null,"申请付款");
            Db::commit();
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("outsourcing_payment")->find($id);
        //收款账号信息
        $contract = db('outsourcing_contract')->find($row["contract_id"]);
        $accounts = db('supplier_accounts')->where(["c_s_id"=>$contract["c_s_id"]])->select();
        $this->assign("row",$row);
        $this->assign("accounts",$accounts);
        $this->assign("contract",$contract);
        return $this->fetch();
    }

    /**
     * 获取收款计划状态名称
     */
    private function getStatusName($status){
        switch ($status){
            case 0:return "待验收";
            case 1:return "可付款";
            case 2:return "待付款";
            case 3:return "已付款";
            default:return "---";
        }
    }

}