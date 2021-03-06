<?php

namespace service;

use think\Db;
use think\db\Query;

/**
 * 基础数据服务
 * Class DataService
 * @package service
 * @author Anyon 
 * @date 2017/03/22 15:32
 */
class DbService
{

    /**
     * 查询所有数据
     * @param String $table     查询的表/对象
     * @param Array $where      查询条件
     * @param Array $order      排序条件
     * @param int $page         页数
     * @param int $limit        每页显示条数
     * @param Array $field      查询字段
     * @param bool $isWhere     是否构造子查询
     * @return Array/String     返回查询结果集/子查询
     */
    public static function queryALL($dbQuery, $where = [], $order = [], $page = 0, $limit = 0, $field = [], $isWhere = true, $orderRaw = '')
    {
        $db = is_string($dbQuery) ? Db::table($dbQuery) : $dbQuery;
        if ($where) {
            $db->where($where);
        }
        if ($order) {
            $db->order($order);
        }
        if ($page) {
            $db->page($page);
        }
        if ($limit) {
            $db->limit($limit);
        }
        if ($field) {
            $db->field($field);
        }
        if ($orderRaw) {
            $db->orderRaw($orderRaw);
        }
        if ($isWhere) {
            $lists = $db->select();
            return $lists;
        } else {
            $buildSql = $db->buildSql();
            return $buildSql;
        }
    }

    /**
     * 查询单个数据
     * @param String $table     查询的表/对象
     * @param Array $where      查询条件
     * @param Array $order      排序条件
     * @param Array $field      查询字段
     * @param bool $isWhere     是否构造子查询
     * @return Array/String     返回查询结果集/子查询
     */
    public static function queryOne($dbQuery, $where = [], $order = [], $field = [], $isWhere = true)
    {
        $db = is_string($dbQuery) ? Db::table($dbQuery) : $dbQuery;
        if ($where) {
            $db->where($where);
        }
        if ($order) {
            $db->order($order);
        }
        if ($field) {
            $db->field($field);
        }
        if ($isWhere) {
            $list = $db->find();
            return $list;
        } else {
            $buildSql = $db->buildSql();
            return $buildSql;
        }
    }

    /**
     * 新增数据
     * @param String $table     插入的表
     * @param Array $data       插入的数据
     * @return int  $code       返回自增主键
     */
    public static function save($table = '', $data = [], $limit = false)
    {
        $db = Db::table($table);
        if ($limit) {
            $code = $db->strict(false)->insertAll($data);
        } else {
            $code = $db->strict(false)->insertGetId($data);
        }

        return $code;
    }

    /**
     * 更新数据
     * @param String $table     更新的表
     * @param Array $data       更新的数据
     * @param Array $where      更新条件
     * @return int  $code       返回
     */
    public static function update($table = '', $data = [], $where = [])
    {
        $db = Db::table($table);
        $db->where($where);
        $code = $db->update($data);
        return $code;
    }

    /**
     * 删除数据
     * @param String $table     删除的表
     * @param Array $where      删除条件
     * @return int  $code       返回
     */
    public static function del($table = '', $where = [])
    {
        $db = Db::table($table);
        $db->where($where);
        $code = $db->delete();
        return $code;
    }

    /**
     * 保存操作日志
     * @param string $table             表名
     * @param string(json) $before     操作前数据
     * @param string(json) $after      操作后数据
     * @param string(json) $where      操作条件
     * @param string $content           描述
     * @return int $code                日志ID
     */
    public static function save_log($table = '', $before = '', $after = '', $where = '', $content = '', $change_table = '')
    {
        $data['ip'] = request()->ip();
        $data['node'] = strtolower(join('/', [request()->module(), request()->controller(), request()->action()]));
        $data['username'] = session('user.username') . '';
        $data['u_id'] = session('user.id') . '';
        $data['agent'] = request()->server('HTTP_USER_AGENT');
        $data['create_time'] = time();
        $data['before_param'] = $before;
        $data['after_param'] = $after;
        $data['where_param'] = $where;
        $data['content'] = $content;
        $data['table_name'] = $change_table;
        $db = Db::table($table);
        $code = $db->strict(false)->insertGetId($data);
        return $code;
    }
}
