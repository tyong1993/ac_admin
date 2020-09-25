<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\villdate\ExpendReimbursementVilldate;

/**
 * 报销支出管理
 */
class ExpendReimbursementController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $bx_people=$this->request->param("bx_people");
            $contract_name=$this->request->param("contract_name");
            $type=$this->request->param("type");
            $db=db('expend_reimbursement');
            if(!empty($bx_people)){
                $db->where("bx_people","like","%$bx_people%");
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
            db('expend_reimbursement')->insert($param);
            return jsonSuccess();
        }
        //销售合同数据
        $res = db('sales_contract')->order("id desc")->select();
        $this->assign("sales_contracts",$res);
        //报销人数据
        $res = db('system_admin')->where("status","eq",1)->where("id","neq",1)->select();
        $this->assign("bx_peoples",$res);
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
            db('expend_reimbursement')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("expend_reimbursement")->find($id);
        $this->assign("row",$row);
        //销售合同数据
        $res = db('sales_contract')->order("id desc")->select();
        $this->assign("sales_contracts",$res);
        //报销人数据
        $res = db('system_admin')->where("status","eq",1)->where("id","neq",1)->select();
        $this->assign("bx_peoples",$res);
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
        db('expend_reimbursement')->where("id","in",$id_arr)->delete();
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