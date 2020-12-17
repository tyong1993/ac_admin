<?php
/**
 * Created by PhpStorm.
 * User: tyong
 * Date: 2020/6/7
 * Time: 11:23
 */

namespace app\admin\model;

/**
 * 新项目提醒
 */
class NewItemWarnModel extends BaseModel
{
    //新增一个提醒
    public static function addItem($type,$obj_id){
        $data = [
            "type"=>$type,
            "obj_id"=>$obj_id,
            "status"=>1,
            "create_time"=>time(),
        ];
        self::insert($data);
    }
    //取消一个提醒
    public static function cancelItem($type,$obj_id){
        self::where(["type"=>$type,"obj_id"=>$obj_id])->update(["status"=>0]);
    }
    //获取提醒项目
    public static function getNewItemWarns($admin_id){
        $res = db("new_item_warn a")
            ->field("type,count(*) count")
            ->leftJoin("new_item_warn_readed b","a.id = b.warn_id and b.admin_id = ".$admin_id)
            ->where("warn_id","null")
            ->where("status","eq","1")
            ->group("type")
            ->select();
        $data = [
            "type_1"=>0,
            "type_2"=>0,
            "type_3"=>0,
            "type_4"=>0,
            "type_5"=>0,
            "type_6"=>0,
            "type_7"=>0,
            "type_8"=>0,
            "type_9"=>0,
            "type_10"=>0,
            "type_11"=>0,
            "type_12"=>0,
            "type_13"=>0,
            "type_14"=>0,
            "type_15"=>0,
        ];
        foreach ($res as $val){
            $data["type_".$val["type"]] = $val["count"];
        }
        return $data;
    }
}