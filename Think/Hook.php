<?php

namespace Think;

/**
 * ThinkPHP系统钩子实现
 */
final class Hook
{

    private static $tags = [];

    /**
     * 动态添加插件到某个标签
     *
     * @param string $tag 标签名称
     * @param mixed $name 插件名称
     *
     * @return void
     */
    public static function add($tag, $name)
    {
        if (!isset(self::$tags[$tag])) {
            self::$tags[$tag] = [];
        }
        if (is_array($name)) {
            self::$tags[$tag] = array_merge(self::$tags[$tag], $name);
        } else {
            self::$tags[$tag][] = $name;
        }
    }

    /**
     * 批量导入插件
     *
     * @param array $data 插件信息
     * @param bool $recursive 是否递归合并
     *
     * @return void
     */
    public static function import(array $data, $recursive = true)
    {
        // 覆盖导入
        if (!$recursive) {
            self::$tags = array_merge(self::$tags, $data);
            // 合并导入
        } else {
            foreach ($data as $tag => $val) {
                if (!isset(self::$tags[$tag])) {
                    self::$tags[$tag] = [];
                }
                if (!empty($val['_overlay'])) {
                    // 可以针对某个标签指定覆盖模式
                    unset($val['_overlay']);
                    self::$tags[$tag] = $val;
                } else {
                    // 合并模式
                    self::$tags[$tag] = array_merge(self::$tags[$tag], $val);
                }
            }
        }
    }

    /**
     * 获取插件信息
     *
     * @param string $tag 插件位置 留空获取全部
     *
     * @return array
     */
    public static function get($tag = '')
    {
        if (empty($tag)) {
            // 获取全部的插件信息
            return self::$tags;
        } else {
            return self::$tags[$tag];
        }
    }

    /**
     * 监听标签的插件
     *
     * @param string $tag 标签名称
     * @param mixed $params 传入参数
     *
     * @return void
     */
    public static function listen($tag, &$params = null)
    {
        if (isset(self::$tags[$tag])) {
            if (APP_DEBUG) {
                G($tag.'Start');
                trace('[ '.$tag.' ] --START--', '', 'INFO');
            }
            foreach (self::$tags[$tag] as $name) {
                APP_DEBUG && G($name.'_start');
                $result = self::exec($name, $tag, $params);
                if (APP_DEBUG) {
                    G($name.'_end');
                    trace(
                        'Run '.$name.' [ RunTime:'.G(
                            $name.'_start',
                            $name.'_end',
                            6
                        ).'s ]',
                        '',
                        'INFO'
                    );
                }
                if (false === $result) {
                    // 如果返回false 则中断插件执行
                    return;
                }
            }
            // 记录行为的执行日志
            if (APP_DEBUG) {
                trace(
                    '[ '.$tag.' ] --END-- [ RunTime:'.G(
                        $tag.'Start',
                        $tag.'End',
                        6
                    ).'s ]',
                    '',
                    'INFO'
                );
            }
        }

        return;
    }

    /**
     * 执行某个插件
     *
     * @param string $name 插件名称
     * @param string $tag 方法名（标签名）
     * @param mixed $params 传入的参数
     * @return mixed
     */
    public static function exec($name, $tag, &$params = null)
    {
        if ('Behavior' == substr($name, -8)) {
            // 行为扩展必须用run入口方法
            $tag = 'run';
        }
        $addon = new $name();

        return $addon->$tag($params);
    }
}
