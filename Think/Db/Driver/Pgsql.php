<?php

namespace Think\Db\Driver;

use Think\Db\Driver;

/**
 * Pgsql数据库驱动
 */
class Pgsql extends Driver
{

    /**
     * 取得数据表的字段信息
     *
     * @param $tableName
     * @return array
     */
    public function getFields($tableName)
    {
        list($tableName) = explode(' ', $tableName);
        $result = $this->query(
            'select fields_name as "field",fields_type as "type",fields_not_null as "null",fields_key_name as "key",fields_default as "default",fields_default as "extra" from table_msg('
            .$tableName.');'
        );
        $info = [];
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['field']] = [
                    'name'    => $val['field'],
                    'type'    => $val['type'],
                    'notnull' => (bool)($val['null'] === ''),
                    // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
                    'autoinc' => (strtolower($val['extra'])
                        == 'auto_increment'),
                ];
            }
        }

        return $info;
    }

    /**
     * 取得数据库的表信息
     *
     * @param string $dbName
     * @return array
     */
    public function getTables($dbName = '')
    {
        $result = $this->query(
            "select tablename as Tables_in_test from pg_tables where  schemaname ='public'"
        );
        $info = [];
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }

        return $info;
    }

    /**
     * limit分析
     *
     *
     * @param mixed $limit
     *
     * @return string
     */
    public function parseLimit($limit)
    {
        $limitStr = '';
        if (!empty($limit)) {
            $limit = explode(',', $limit);
            if (count($limit) > 1) {
                $limitStr .= ' LIMIT '.$limit[1].' OFFSET '.$limit[0].' ';
            } else {
                $limitStr .= ' LIMIT '.$limit[0].' ';
            }
        }

        return $limitStr;
    }

    /**
     * 解析pdo连接的dsn信息
     *
     *
     * @param array $config 连接信息
     *
     * @return string
     */
    protected function parseDsn($config)
    {
        $dsn = 'pgsql:dbname='.$config['database'].';host='.$config['hostname'];
        if (!empty($config['hostport'])) {
            $dsn .= ';port='.$config['hostport'];
        }

        return $dsn;
    }

}
