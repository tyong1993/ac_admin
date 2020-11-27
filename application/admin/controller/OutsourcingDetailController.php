<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/8/26
 * Time: 9:47
 */

namespace app\admin\controller;
use think\Db;

/**
 * Class ProjectREController
 * @package app\admin\controller
 * 外包明细
 */
class OutsourcingDetailController extends BaseController
{
    /**
     * 列表
     */
    function index(){
        $status=$this->request->param("status");
        //默认查询当年数据
        $select_by_year = $this->request->param("select_by_year");
        if($select_by_year === null){
            $select_by_year = date("Y");
        }
        if($this->request->isAjax()){
            $limit=$this->request->param("limit");
            $supplier_name=$this->request->param("supplier_name");
            $contract_name=$this->request->param("contract_name");
            $g_c_id = $this->request->param("g_c_id");
            $status = $this->request->param("status");
            $invoice_status = $this->request->param("invoice_status");

            $db = db('outsourcing_contract a');
            $db = $db->field("a.contract_name,a.all_amount,a.supplier_name,a.group_company_name,a.sign_date,a.supplier_identifier,a.company_identifier,b.*,c.pay_status,c.invoice_status,c.invoice_time,c.pay_time")
                ->rightJoin("outsourcing_payment b","b.contract_id = a.id")
                ->leftJoin("payment_records c","b.id = c.payment_id")
            ;
            if(!empty($supplier_name)){
                $db->where("a.supplier_name","like","%$supplier_name%");
            }
            if(!empty($contract_name)){
                $db->where("a.contract_name","like","%$contract_name%");
            }
            if(!empty($status)){
                $db->where("b.status","eq",$status-1);
            }
            if(!empty($invoice_status)){
                $invoice_status = $invoice_status-1;
                if($invoice_status == 1){
                    $db->where("c.invoice_status","eq",1);
                }else{
                    $db->where("c.invoice_status is null or c.invoice_status = {$invoice_status}");
                }

            }
            if(!empty($select_by_year)){
                $select_by_time=$select_by_year."-01-01 00:00:00";
                //年初时间戳
                $year_start=strtotime($select_by_time);
                //年末时间戳
                $year_end=strtotime("+1 year",$year_start);
                $db->where("a.sign_date","egt",$year_start);
                $db->where("a.sign_date","lt",$year_end);
            }
            if(!empty($g_c_id)){
                $db->where("a.g_c_id","eq",$g_c_id);
            }
            //数据权限
//            $db = self::dataPower($db,"a.b_l_id");
            //拷贝查询对象
            $db_cope = unserialize(serialize($db));
            //数据分页查询,处理
            $res = $db->order("a.id desc,b.id asc")->paginate($limit)->toArray();
            $now_time = time();
            foreach ($res["data"] as &$val){
                $val["status_name"] = OutsourcingPaymentController::getStatusName($val["status"]);
                $val["status_name"] = $val["status_name"] == "已付款"?"已付款":"<span style='color: red'>".$val["status_name"]."</span>";
                $val["invoice_status_name"] = $val["invoice_status"]==1?"已收票":"<span style='color: red'>未收票</span>";
                $val["all_amount_format"] = amount_format($val["all_amount"]);
                $val["all_pay_amount_format"] = amount_format($val["all_pay_amount"]);
                $val["sign_date_format"] = $val["sign_date"]?date("Y-m-d",$val["sign_date"]):"---";
                $val["invoice_time_format"] = $val["invoice_time"]?date("Y-m-d",$val["invoice_time"]):"---";
                $val["pay_time_format"] = $val["pay_time"]?date("Y-m-d",$val["pay_time"]):"---";
            }
            unset($val);
            //数据统计
            $sql = $db_cope->buildSql();
            $sql = Db::table($sql." a")
                ->field("all_amount,sum(all_pay_amount) all_pay_amount")
                ->group("a.contract_id")
                ->buildSql();
            $res_statistic = Db::table($sql." b")
                ->field("sum(all_amount) all_amount,sum(all_pay_amount) all_pay_amount")
                ->find();
            if(!empty($res["data"])){
                $statistic = $res["data"][0];
                foreach ($res_statistic as $key=>$val){
                    $res_statistic[$key] = $val?:0;
                }
                foreach ($statistic as $key=>$v){
                    switch ($key){
                        case "id":$statistic[$key]="统计";break;
                        case "all_amount_format":$statistic[$key]="<strong>".amount_format($res_statistic["all_amount"])."</strong>";break;
                        case "all_pay_amount_format":$statistic[$key]="<strong>".amount_format($res_statistic["all_pay_amount"])."</strong>";break;
                        default:$statistic[$key]="";
                    }
                }
                $res["data"][]=$statistic;
            }
            return json(["code"=>0,"msg"=>"success","count"=>$res["total"],"data"=>$res["data"]]);
        }
        $this->assign("select_by_year",$select_by_year);
        //签约单位数据
        $res = db('group_company')->select();
        $this->assign("group_companys",$res);
        $this->assign("status",$status);
        return $this->fetch();
    }

    /**
     * 添加
     */
    function add(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            $param["create_time"] = time();
            db('customer_supplier')->insert($param);
            return jsonSuccess();
        }
        return $this->fetch();
    }
    /**
     * 编辑
     */
    function edit(){
        if($this->request->isAjax()){
            $param=$this->request->post();
            db('customer_supplier')->update($param);
            return jsonSuccess();
        }
        $id=$this->request->param('id');
        $row=db("customer_supplier")->find($id);
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
        db('customer_supplier')->where("id","in",$id_arr)->delete();
        return jsonSuccess();
    }
}