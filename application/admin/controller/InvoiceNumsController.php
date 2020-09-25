<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use app\admin\villdate\InvoiceNumsVilldate;

/**
 * 发票号
 */
class InvoiceNumsController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        if($this->request->isAjax()){
            $i_r_id = $this->request->param("i_r_id");
            $limit=$this->request->param("limit");
            $num=$this->request->param("num");
            $db=db('invoice_nums');
            $db->where("i_r_id","=",$i_r_id);
            if(!empty($num)){
                $db->where("num","like","%$num%");
            }
            $res = $db->paginate($limit)->toArray();
            foreach ($res["data"] as &$val){
                $val["create_time"] = date("Y-m-d H:i",$val["create_time"]);
                $val["status"] = $val["status"]?"正常":"已作废";
            }
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        $i_r_id = $this->request->param("i_r_id");
        $this->assign("i_r_id",$i_r_id);
        return $this->fetch();
    }

    /**
     * 添加
     */
    function add(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,InvoiceNumsVilldate::class);
            //发票号是否已存在
            if(db('invoice_nums')->where(["num"=>$param["num"]])->count()){
                return jsonFail("发票号已存在");
            }
            $param["create_time"] = time();
            db('invoice_nums')->insert($param);
            return jsonSuccess();
        }
        $i_r_id = $this->request->param("i_r_id");
        $this->assign("i_r_id",$i_r_id);
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $this->validate($param,InvoiceNumsVilldate::class);
            //发票号是否已存在
            if(db('invoice_nums')->where(["num"=>$param["num"]])->where("id","neq",$param["id"])->count()){
                return jsonFail("发票号已存在");
            }
            db('invoice_nums')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("invoice_nums")->find($id);
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
        db('invoice_nums')->where("id","in",$id_arr)->delete();
        return jsonSuccess();
    }
}