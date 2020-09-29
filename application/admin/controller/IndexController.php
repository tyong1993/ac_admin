<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/6/4
 * Time: 14:23
 */

namespace app\admin\controller;


use app\admin\model\BaseModel;
use app\admin\model\SystemAdminModel;
use app\admin\model\SystemNodeModel;
use think\Db;
use think\facade\App;

class IndexController extends BaseController
{
    protected $notNeedLoginFun=["login"];

    function index(){
        return $this->fetch();
    }

    function welcome(){
        $conn = mysqli_connect(
            config('database.hostname') . ":" . config('database.hostport'),
            config('database.username'),
            config('database.password'),
            config('database.database')
        );
        //汇总
        $summary_statistic = $this->welcomeSummary();
        //待办
        $todo = $this->welcomeTodo();
        $this->assign(
            [
                "tp_version"=>App::version(),
                "mysql_version"=>mysqli_get_server_info($conn),
                "summary_statistic"=>$summary_statistic,
                "todo"=>$todo
            ]
        );
        return $this->fetch();
    }

    /**
     * 首页汇总数据
     */
    private function welcomeSummary(){
        $select_by_year = $this->request->param("select_by_year");
        //默认查询当年数据
        if(empty($select_by_year)){
            $select_by_year = date("Y");
        }
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
        //奖金支出子查询
        $subsql_expend_reward = db("expend_reward")
            ->field('collection_id,sum(pay_amount) pay_amount')
            ->group('collection_id')
            ->buildSql();
        $db = db('sales_contract a');
        $db = $db->field("a.id,a.contract_name,a.contract_amount,sum(b.collection_amount) collection_amount,sum(if(c.colletion_status = 1,invoice_amount,0)) collected_amount,sum(d.pay_amount) outsourcing_pay_amount,sum(e.amount) reimbursement_amount,sum(f.pay_amount_true) business_pay_amount,sum(g.pay_amount) reward_pay_amount")
            ->leftJoin("sales_collection b","b.contract_id = a.id")
            ->leftJoin("invoice_records c","b.id = c.collection_id")
            ->leftJoin([$subsql_outsourcing_payment=>"d"],"b.id = d.collection_id")
            ->leftJoin([$subsql_expend_reimbursement=>"e"],"b.id = e.collection_id")
            ->leftJoin([$subsql_expend_business=>"f"],"b.id = f.collection_id")
            ->leftJoin([$subsql_expend_reward=>"g"],"b.id = g.collection_id")
            ->group('a.id')
        ;
        if(!empty($select_by_year)){
            $select_by_time=$select_by_year."-01-01 00:00:00";
            //年初时间戳
            $year_start=strtotime($select_by_time);
            //年末时间戳
            $year_end=strtotime("+1 year",$year_start);
            $db->where("a.sign_date","egt",$year_start);
            $db->where("a.sign_date","lt",$year_end);
        }
        //数据统计
        $sql = $db->buildSql();
        $summary_statistic = Db::table($sql." a")->field("sum(contract_amount) contract_amount,sum(collection_amount) collection_amount,sum(collected_amount) collected_amount,sum(outsourcing_pay_amount) outsourcing_pay_amount,sum(reimbursement_amount) reimbursement_amount,sum(business_pay_amount) business_pay_amount,sum(reward_pay_amount) reward_pay_amount")->find();
        foreach ($summary_statistic as $key=>$val){
            $summary_statistic[$key."_format"] = amount_format($val?:0);
        }
        return $summary_statistic;
    }

    /**
     * 首页待办数据
     */
    private function welcomeTodo(){
        $box = new \stdClass();
        //待验收
        $box->sales_need_check = db("sales_collection")->where(["status"=>0])->count();
        //可开票
        $box->sales_can_invoice = db("sales_collection")->where(["status"=>1])->count();
        //待开票
        $box->sales_wait_invoice = db("invoice_records")->where(["invoice_status"=>0])->count();
        //待收款
        $box->sales_wait_collection = db("invoice_records")->where(["colletion_status"=>0])->count();

        //待验收
        $box->out_need_check = db("outsourcing_payment")->where(["status"=>0])->count();
        //可付款
        $box->out_can_pay = db("outsourcing_payment")->where(["status"=>1])->count();
        //待收票
        $box->out_wait_invoice = db("payment_records")->where(["invoice_status"=>0])->count();
        //待付款
        $box->out_wait_pay = db("payment_records")->where(["pay_status"=>0])->count();

        $box->expend_reimbursement = db("expend_reimbursement")->where(["pay_status"=>0])->count();
        $box->expend_business = db("expend_business")->where(["pay_status"=>0])->count();
        $box->expend_reward = db("expend_reward")->where(["pay_status"=>0])->count();
        return json_decode(json_encode($box),true);
    }
    /**
     * 初始化数据
     */
    function getSystemInit(){
        $initData=[
            "homeInfo"=>[
                "title"=>"首页",
                "href"=>"admin/Index/welcome",
            ],
            "logoInfo"=>[
                "title"=>"爱车合同管理",
                "image"=>"/static/admin/images/logo.png",
                "href"=>"",
            ],
            "menuInfo"=>SystemNodeModel::getMenuList()
        ];
        return json($initData);
    }

    /**
     * 登录
     */
    function login(){
        if($this->request->isPost()){
            $username=$this->request->param("username");
            $password=$this->request->param("password");
            $admin = db("system_admin")->where("username","=",$username)->find();
            if(empty($admin)){
                return jsonFail("账号或密码错误");
            }
            if(!$admin["status"]){
                return jsonFail("账户已被禁用");
            }
            if(!checkPassword($password,$admin["password"],$admin["salt"])){
                return jsonFail("账号或密码错误");
            }
            session("admin_id",$admin["id"]);
            session("admin_username",$admin["username"]);
            //登录日志
            db("system_log_login")->insert(
                [
                    "admin_id"=>$admin["id"],
                    "name"=>$admin["name"],
                    "username"=>$admin["username"],
                    "ip"=>$this->request->ip(),
                    "create_time"=>time()
                ]
            );
            return jsonSuccess();
        }
        return $this->fetch();
    }

    /**
     * 登出
     */
    function logout(){
        SystemAdminModel::loginOut();
        return jsonSuccess();
    }

    /**
     * 清理服务端缓存
     */
    function cleanCache(){
        cache('cleanable_cache',[]);
        return jsonSuccess([],"服务端缓存已清除",1);
    }
}