<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\model\BaseModel;
use app\admin\model\NewItemWarnModel;
use app\admin\model\NewItemWarnReadedModel;
use app\admin\villdate\ExpendReimbursementVilldate;
use think\Db;

/**
 * 报销支出管理
 */
class ExpendReimbursementController extends BaseController
{
    protected $table = "expend_reimbursement";
    protected $table_name = "报销支出";
    /**
     * 列表
     */
    function index(){
        NewItemWarnReadedModel::readItems(session("admin_id"),3);
        NewItemWarnReadedModel::readItems(session("admin_id"),7);
        $pay_status=$this->request->param("pay_status");
        $sales_contract_id=$this->request->param("sales_contract_id");
        $sales_collection_id=$this->request->param("sales_collection_id");
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $bx_people_id=$this->request->param("bx_people_id");
            $contract_name=$this->request->param("contract_name");
            $type=$this->request->param("type");
            $db=db('expend_reimbursement');
            $db = $db->alias("a")
                ->field("a.*,b.contract_amount,b.contract_name contract_name_c")
                ->leftJoin("sales_contract b","a.contract_id = b.id");
            if(!empty($bx_people_id)){
                $db->where("bx_people_id","eq",$bx_people_id);
            }
            if(!empty($contract_name)){
                $db->where("b.contract_name","like","%$contract_name%");
            }
            if(!empty($type)){
                $db->where("type","like","%$type%");
            }
            if(!empty($pay_status)){
                $db->where("pay_status","eq",$pay_status-1);
            }
            if(!empty($sales_contract_id)){
                $db->where("a.contract_id","eq",$sales_contract_id);
            }
            if(!empty($sales_collection_id)){
                $db->where("a.collection_id","eq",$sales_collection_id);
            }
            //拷贝查询对象
            $db_cope = unserialize(serialize($db));
            $res = $db->order("id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                $val["pay_status"] = $val["pay_status"]?"已付款":"<span style='color: red'>未付款</span>";
                $val["reimbursement_status"] = $val["reimbursement_status"]?"已报销":"<span style='color: red'>未报销</span>";
                $val["amount"] = amount_format($val["amount"]);
                $val["pay_time"] = $val["pay_time"]?date("Y-m-d",$val["pay_time"]):"---";
            }
            unset($val);
            //数据统计
            $statistic_subsql = $db_cope->buildSql();
            $res_statistic = Db::table($statistic_subsql." a")
                ->field("sum(amount) amount")
                ->find();
            if(!empty($res["data"])){
                $statistic = $res["data"][0];
                foreach ($res_statistic as $key=>$val){
                    $res_statistic[$key] = $val?:0;
                }
                foreach ($statistic as $key=>$v){
                    switch ($key){
                        case "id":$statistic[$key]="统计";break;
                        case "amount":$statistic[$key]="<strong>".amount_format($res_statistic["amount"])."</strong>";break;
                        default:$statistic[$key]="";
                    }
                }
                $res["data"][]=$statistic;
            }
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        $this->assign("bx_peoples",BaseModel::getAdmins());
        $this->assign("pay_status",$pay_status);
        $this->assign("sales_contract_id",$sales_contract_id);
        $this->assign("sales_collection_id",$sales_collection_id);
        return $this->fetch();
    }

    /**
     * 添加
     */
    function add(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,ExpendReimbursementVilldate::class);
            $param["create_time"] = time();
            $param["bx_people"] = db("system_admin")->where(["id"=>$param["bx_people_id"]])->value("name")?:"";
            $param["pay_time"] = !empty($param["pay_time"])?strtotime($param["pay_time"]):0;
            $id=db($this->table)->insert($param,false,true);
            if($id){
                self::actionLog(1,$this->table,$this->table_name,$id);
                NewItemWarnModel::addItem(3,$id);
            }
            return jsonSuccess();
        }
        //销售合同数据
        $res = db('sales_contract')->order("id desc")->select();
        $this->assign("sales_contracts",$res);
        //报销人数据
        $this->assign("bx_peoples",BaseModel::getAdmins());
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,ExpendReimbursementVilldate::class);
            $param["bx_people"] = db("system_admin")->where(["id"=>$param["bx_people_id"]])->value("name")?:"";
            $param["pay_time"] = !empty($param["pay_time"])?strtotime($param["pay_time"]):0;
            $row = db($this->table)->find($param["id"]);
            if(db($this->table)->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$row["id"],$row);
            }
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("expend_reimbursement")->find($id);
        $row["pay_time"] = !empty($row["pay_time"])?date("Y-m-d",$row["pay_time"]):"";
        $row["contract_amount"] = db('sales_contract')->where("id","eq",$row["contract_id"])->value("contract_amount");
        $this->assign("row",$row);
        //销售合同数据
        $res = db('sales_contract')->order("id desc")->select();
        $this->assign("sales_contracts",$res);
        //报销人数据
        $this->assign("bx_peoples",BaseModel::getAdmins());
        //收款期数数据
        $res = db('sales_collection')->where(["contract_id"=>$row["contract_id"]])->order("id desc")->select();
        $this->assign("sales_collections",$res);
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
}