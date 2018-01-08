<?php


namespace Think;

/**
 * Class Storage
 *
 * @package Think
 *          文件存储类
 */
class Storage
{

    /**
     * 操作句柄
     *
     * @var string
     * @access protected
     */
    protected static $handler;

    /**
     * 连接分布式文件系统
     *
     *
     * @param string $type    文件类型
     * @param array  $options 配置数组
     *
     * @return void
     */
    public static function connect($type = 'File', $options = [])
    {
        $class = 'Think\\Storage\\Driver\\'.ucwords($type);
        self::$handler = new $class($options);
    }

    public static function __callstatic($method, $args)
    {
        //调用缓存驱动的方法
        if (method_exists(self::$handler, $method)) {
            return call_user_func_array([self::$handler, $method], $args);
        }
    }
}
