<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\villdate\CustomersInvoiceVilldate;

/**
 * 客户开票
 */
class CustomersInvoiceController extends BaseController
{
    protected $table = "customers_invoice";
    protected $table_name = "客户/供应商发票信息";
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $c_s_id = $this->request->param("c_s_id");
            $limit=$this->request->param("limit");
            $title=$this->request->param("title");
            $db=db($this->table);
            $db->where("c_s_id","=",$c_s_id);
            if(!empty($title)){
                $db->where("title","like","%$title%");
            }
            $res = $db->paginate($limit)->toArray();
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        $id = $this->request->param("id");
        $this->assign("c_s_id",$id);
        return $this->fetch();
    }

    /**
     * 添加
     */
    function add(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,CustomersInvoiceVilldate::class);
            $id=db($this->table)->insert($param,false,true);
            if($id){
                self::actionLog(1,$this->table,$this->table_name,$id);
            }
            return jsonSuccess();
        }
        $c_s_id = $this->request->param("c_s_id");
        $this->assign("c_s_id",$c_s_id);
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,CustomersInvoiceVilldate::class);
            $row = db($this->table)->find($param["id"]);
            if(db($this->table)->update($param)){
                self::actionLog(2,$this->table,$this->table_name,$row["id"],$row);
            }
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("customers_invoice")->find($id);
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
        foreach ($id_arr as $id){
            $row = db($this->table)->find($id);
            if(!empty($row)){
                self::actionLog(3,$this->table,$this->table_name,$id,$row);
                db($this->table)->where("id","eq",$id)->delete();
            }
        }
        return jsonSuccess();
    }
}