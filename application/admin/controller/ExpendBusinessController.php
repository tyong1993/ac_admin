<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\model\NewItemWarnModel;
use app\admin\model\NewItemWarnReadedModel;
use app\admin\villdate\ExpendBusinessVilldate;
use think\Db;

/**
 * 商务支出管理
 */
class ExpendBusinessController extends BaseController
{
    protected $table = "expend_business";
    protected $table_name = "商务支出";
    /**
     * 列表
     */
    function index(){
        NewItemWarnReadedModel::readItems(session("admin_id"),4);
        NewItemWarnReadedModel::readItems(session("admin_id"),8);
        $pay_status=$this->request->param("pay_status");
        $colletion_status=$this->request->param("colletion_status");
        $sales_contract_id=$this->request->param("sales_contract_id");
        $sales_collection_id=$this->request->param("sales_collection_id");
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $page=$this->request->param("page")?:1;
            $business_contact=$this->request->param("business_contact");
            $contract_name=$this->request->param("contract_name");
            $db=db('expend_business');
            $db = $db->alias("a")
                ->field("a.*,IFNULL(b.colletion_status,0) colletion_status,c.contract_amount,c.contract_name contract_name_c")
                ->leftJoin("invoice_records b","a.collection_id = b.collection_id")
                ->leftJoin("sales_contract c","a.contract_id = c.id");
            if(!empty($business_contact)){
                $db->where("a.business_contact","like","%$business_contact%");
            }
            if(!empty($contract_name)){
                $db->where("c.contract_name","like","%$contract_name%");
            }
            if(!empty($pay_status)){
                $db->where("pay_status","eq",$pay_status-1);
            }
            if(!empty($colletion_status)){
                $db->having("colletion_status = $colletion_status-1");
            }
            if(!empty($sales_contract_id)){
                $db->where("a.contract_id","eq",$sales_contract_id);
            }
            if(!empty($sales_collection_id)){
                $db->where("a.collection_id","eq",$sales_collection_id);
            }
            //拷贝查询对象
            $db_cope = unserialize(serialize($db));
            $res = $db->order("id desc")->page($page,$limit)->select();
            foreach ($res as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                $val["tax_rate"] = $val["is_need_tax"]?$val["tax_rate"]:"---";
                $val["pay_status"] = $val["pay_status"]?"已付款":"<span style='color: red'>未付款</span>";
                $val["colletion_status"] = $val["colletion_status"]?"已收款":"<span style='color: red'>未收款</span>";
                $val["collection_amount"] = amount_format($val["collection_amount"]);
                $val["pay_amount_need"] = amount_format($val["pay_amount_need"]);
                $val["pay_amount_true"] = amount_format($val["pay_amount_true"]);
                $val["pay_time"] = $val["pay_time"]?date("Y-m-d",$val["pay_time"]):"---";
            }
            unset($val);
            //数据统计
            $statistic_subsql = $db_cope->buildSql();
            $res_statistic = Db::table($statistic_subsql." a")
                ->field("count(id) count,sum(pay_amount_true) pay_amount_true")
                ->find();
            if(!empty($res)){
                $statistic = $res[0];
                foreach ($res_statistic as $key=>$val){
                    $res_statistic[$key] = $val?:0;
                }
                foreach ($statistic as $key=>$v){
                    switch ($key){
                        case "id":$statistic[$key]="统计";break;
                        case "pay_amount_true":$statistic[$key]="<strong>".amount_format($res_statistic["pay_amount_true"])."</strong>";break;
                        default:$statistic[$key]="";
                    }
                }
                $res[]=$statistic;
            }
            return json(["code"=>0,"msg"=>"success","count"=>isset($res_statistic["count"])?$res_statistic["count"]:0,"data"=>$res]);
        }
        $this->assign("pay_status",$pay_status);
        $this->assign("colletion_status",$colletion_status);
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
            $this->validate($param,ExpendBusinessVilldate::class);
            $param["create_time"] = time();
            $param["pay_time"] = !empty($param["pay_time"])?strtotime($param["pay_time"]):0;
            $id=db($this->table)->insert($param,false,true);
            if($id){
                self::actionLog(1,$this->table,$this->table_name,$id);
                NewItemWarnModel::addItem(4,$id);
            }
            return jsonSuccess();
        }
        //销售合同数据
        $res = db('sales_contract')->order("id desc")->select();
        $this->assign("sales_contracts",$res);
        //商务联系人数据
        $business_contacts = db("contacts_people")->column("name","id");
        $this->assign("business_contacts",$business_contacts);
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,ExpendBusinessVilldate::class);
            $param["pay_time"] = !empty($param["pay_time"])?strtotime($param["pay_time"]):0;
            $row = db($this->table)->find($param["id"]);
            if(db($this->table)->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$row["id"],$row);
            }
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("expend_business")->find($id);
        $row["pay_time"] = !empty($row["pay_time"])?date("Y-m-d",$row["pay_time"]):"";
        $row["contract_amount"] = db('sales_contract')->where("id","eq",$row["contract_id"])->value("contract_amount");
        $this->assign("row",$row);
        //销售合同数据
        $res = db('sales_contract')->order("id desc")->select();
        $this->assign("sales_contracts",$res);
        //收款期数数据
        $res = db('sales_collection')->where(["contract_id"=>$row["contract_id"]])->order("id desc")->select();
        $this->assign("sales_collections",$res);
        //该期收款情况
        $res = db('sales_collection a')->field("a.*,b.colletion_status")->leftJoin("invoice_records b","a.id = b.collection_id")->where("a.id","eq",$row["collection_id"])->find();
        $this->assign("now_periods",$res);
        //商务联系人数据
        $business_contacts = db("contacts_people")->column("name","id");
        $this->assign("business_contacts",$business_contacts);
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