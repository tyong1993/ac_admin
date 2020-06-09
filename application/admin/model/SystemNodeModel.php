<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/6/5
 * Time: 14:29
 */

namespace app\admin\model;


class SystemNodeModel extends BaseModel
{
    /**
     * 获取菜单列表
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getMenuList(){
        $menuList = self::field('id,pid,title,icon,auth_tag,target')
            ->where('status', 'eq',1)
            ->where('is_menu', 'eq',1)
            ->order('sort', 'desc')
            ->select();
        $menuList = self::buildMenuChild(0, $menuList);
        return $menuList;
    }
    /**
     * 递归获取子菜单
     * @param $pid
     * @param $menuList
     * @return array
     */
    protected static function buildMenuChild($pid, $menuList){
        $treeList = [];
        foreach ($menuList as $v) {
            if ($pid == $v['pid']) {
                $node = $v;
                $child = self::buildMenuChild($v['id'], $menuList);
                if (!empty($child)) {
                    $node['child'] = $child;
                    $node["href"] = "";
                }else{
                    $node["href"] = "admin/".$node["auth_tag"];
                }
                // 用户权限判断
                if(!checkAuth($node["auth_tag"])){
                    continue;
                }
                $treeList[] = $node;
            }
        }
        return $treeList;
    }
    /**
     * 获取下拉框列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getSelectList()
    {
        $list = self::field('id,pid,title')
            ->where("status","=",1)
            ->select()
            ->toArray();
        $pidMenuList = self::buildPidMenu(0, $list);
        $pidMenuList = array_merge([[
            'id'    => 0,
            'pid'   => 0,
            'title' => '顶级节点',
        ]], $pidMenuList);
        return $pidMenuList;
    }

    protected static function buildPidMenu($pid, $list, $level = 0)
    {
        $newList = [];
        foreach ($list as $vo) {
            if ($vo['pid'] == $pid) {
                $level++;
                foreach ($newList as $v) {
                    if ($vo['pid'] == $v['pid'] && isset($v['level'])) {
                        $level = $v['level'];
                        break;
                    }
                }
                $vo['level'] = $level;
                if ($level > 1) {
                    $repeatString = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                    $markString   = str_repeat("{$repeatString}├{$repeatString}", $level - 1);
                    $vo['title']  = $markString . $vo['title'];
                }
                $newList[] = $vo;
                $childList = self::buildPidMenu($vo['id'], $list, $level);
                !empty($childList) && $newList = array_merge($newList, $childList);
            }
        }
        return $newList;
    }

    /**
     * 获取系统所有的权限节点标识
     */
    public static function getAllAuthTags(){
        $allAuthTags=cleanableCache("all_auth_tags");
        if($allAuthTags === null){
            $res=self::where("status","=",1)->column("auth_tag","id");
            $allAuthTags=$res;
            cleanableCache("all_auth_tags",$allAuthTags);
        }
        return $allAuthTags;
    }

    /**
     * 获取管理员的权限节点标识
     */
    public static function getAdminAuthTags(){
        $adminAuthTags=cleanableCache("admin_auth_tags");
        if($adminAuthTags === null){
            $admin = SystemAdminModel::find(session("admin_id"));
            if(empty($admin["role_ids"])){
                $adminAuthTags=[];
            }else{
                $node_ids=db("system_role_node")->where("role_id","in",$admin["role_ids"])->column("node_id");
                if(empty($node_ids)){
                    $adminAuthTags=[];
                }else{
                    $res=self::where("id","in",$node_ids)->where("status","=",1)->column("auth_tag","id");
                    $adminAuthTags=$res;
                }
            }
            cleanableCache("admin_auth_tags",$adminAuthTags);
        }
        return $adminAuthTags;
    }
    /**
     * 获取授权列表
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected static $role_nodes=[];
    public static function getAuthorizeList($role_id){
        $authList = self::field('id,pid,title')
            ->where('status', 'eq',1)
            ->order('sort', 'desc')
            ->select();
        self::$role_nodes=db("system_role_node")->where("role_id","=",$role_id)->column("node_id");
        $authList = self::buildAuthorizeChild(0, $authList);
        return $authList;
    }

    /**
     * 递归获取子节点
     * @param $pid
     * @param $authList
     * @return array
     */
    protected static function buildAuthorizeChild($pid, $authList){
        $treeList = [];
        foreach ($authList as $v) {
            if ($pid == $v['pid']) {
                $node = $v;
                $child = self::buildAuthorizeChild($v['id'], $authList);
                if (!empty($child)) {
                    $node['children'] = $child;
                }
                if(empty($node['children']) && in_array($node["id"],self::$role_nodes)){
                    $node["checked"] = true;
                }else{
                    $node["checked"] = false;
                }
                $node["spread"] = true;
                $treeList[] = $node;
            }
        }
        return $treeList;
    }
}