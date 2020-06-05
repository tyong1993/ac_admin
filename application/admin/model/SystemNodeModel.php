<?php
/**
 * Created by PhpStorm.
 * User: HW
 * Date: 2020/6/5
 * Time: 14:29
 */

namespace app\admin\model;


use think\Model;

class SystemNodeModel extends Model
{
    /**
     * 获取菜单列表
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getMenuList(){
        $menuList = self::field('node_id id,pid,title,icon,auth_tag href,target')
            ->where('status', 'eq',1)
            ->where('is_menu', 'eq',1)
            ->order('sort', 'desc')
            ->select();
        $menuList = self::buildMenuChild(0, $menuList);
        return $menuList;
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
        $list = self::field('node_id id,pid,title')
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
                }
                // todo 后续此处加上用户的权限判断
                $treeList[] = $node;
            }
        }
        return $treeList;
    }
}