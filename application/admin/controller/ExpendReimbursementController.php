<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\model\BaseModel;
use app\admin\villdate\ExpendReimbursementVilldate;

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
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $bx_people_id=$this->request->param("bx_people_id");
            $contract_name=$this->request->param("contract_name");
            $type=$this->request->param("type");
            $db=db('expend_reimbursement');
            if(!empty($bx_people_id)){
                $db->where("bx_people_id","eq",$bx_people_id);
            }
            if(!empty($contract_name)){
                $db->where("contract_name","like","%$contract_name%");
            }
            if(!empty($type)){
                $db->where("type","like","%$type%");
            }
            $res = $db->order("id desc")->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                $val["pay_status"] = $val["pay_status"]?"已付款":"未付款";
                $val["amount"] = amount_format($val["amount"]);
            }
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        $this->assign("bx_peoples",BaseModel::getAdmins());
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
            $id=db($this->table)->insert($param,false,true);
            if($id){
                self::actionLog(1,$this->table,$this->table_name,$id);
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
            $row = db($this->table)->find($param["id"]);
            if(db($this->table)->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$row["id"],$row);
            }
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("expend_reimbursement")->find($id);
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
            $templete .= '<option value="'.$val['id'].'|-|'.$val['periods'].'">'.$val['periods'].'</option>';
        }
        return jsonSuccess($templete);
    }
}