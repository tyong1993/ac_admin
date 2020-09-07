<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\villdate\SupplierAccountsVilldate;

/**
 * 供应商账户
 */
class SupplierAccountsController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $c_s_id = $this->request->param("c_s_id");
            $limit=$this->request->param("limit");
            $payee=$this->request->param("payee");
            $db=db('supplier_accounts');
            $db->where("c_s_id","=",$c_s_id);
            if(!empty($payee)){
                $db->where("payee","like","%$payee%");
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
            $this->validate($param,SupplierAccountsVilldate::class);
            db('supplier_accounts')->insert($param);
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
            $this->validate($param,SupplierAccountsVilldate::class);
            db('supplier_accounts')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("supplier_accounts")->find($id);
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
        db('supplier_accounts')->where("id","in",$id_arr)->delete();
        return jsonSuccess();
    }
}