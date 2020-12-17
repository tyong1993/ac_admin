<?php
/**
 * Created by PhpStorm.
 * User: tyong
 * Date: 2020/6/7
 * Time: 11:23
 */

namespace app\admin\model;

/**
 * 新项目提醒已读
 */
class NewItemWarnReadedModel extends BaseModel
{
    //设置提醒项目为已读
    public static function readItems($admin_id,$type){
        $warm_ids = db("new_item_warn a")
            ->leftJoin("new_item_warn_readed b","a.id = b.warn_id and b.admin_id = ".$admin_id)
            ->where("a.type","eq",$type)
            ->where("warn_id","null")
            ->column("a.id");
        $data = [];
        foreach ($warm_ids as $id){
            $temp["warn_id"]=$id;
            $temp["admin_id"]=$admin_id;
            $data[] = $temp;
        }
        self::insertAll($data);
    }
}