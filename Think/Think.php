<?php

namespace Think;

/**
 * ThinkPHP 引导类
 */
final class Think
{
    const VERSION = '3.2.3';

    // 实例化对象
    private static $_instance = [];

    /**
     * 应用程序初始化
     *
     * @return void
     */
    public static function start()
    {
        // 设定错误和异常处理
        register_shutdown_function('Think\Think::fatalError');
        set_error_handler('Think\Think::appError');
        set_exception_handler('Think\Think::appException');

        // 初始化文件存储方式
        Storage::connect(STORAGE_TYPE);

        $runtimefile = RUNTIME_PATH.APP_MODE.'~runtime.php';
        if (!APP_DEBUG && Storage::has($runtimefile)) {
            Storage::load($runtimefile);
        } else {
            if (Storage::has($runtimefile)) {
                Storage::unlink($runtimefile);
            }
            $content = '';
            // 读取应用模式
            $mode = include is_file(CONF_PATH.'core.php') ? CONF_PATH.'core.php'
                : MODE_PATH.APP_MODE.'.php';
            // 加载核心文件
            foreach ($mode['core'] as $file) {
                if (is_file($file)) {
                    include $file;
                    if (!APP_DEBUG) {
                        $content .= compile($file);
                    }
                }
            }

            // 加载应用模式配置文件
            foreach ($mode['config'] as $key => $file) {
                is_numeric($key)
                    ? C(load_config($file))
                    : C(
                    $key,
                    load_config($file)
                );
            }

            // 读取当前应用模式对应的配置文件
            if ('common' != APP_MODE
                && is_file(
                    CONF_PATH.'config_'.APP_MODE.CONF_EXT
                )) {
                C(load_config(CONF_PATH.'config_'.APP_MODE.CONF_EXT));
            }

            // 加载模式行为定义
            if (isset($mode['tags'])) {
                Hook::import(
                    is_array($mode['tags']) ? $mode['tags']
                        : include $mode['tags']
                );
            }

            // 加载应用行为定义
            if (is_file(CONF_PATH.'tags.php')) // 允许应用增加开发模式配置定义
            {
                Hook::import(include CONF_PATH.'tags.php');
            }

            // 加载框架底层语言包
            L(include THINK_PATH.'Lang/'.strtolower(C('DEFAULT_LANG')).'.php');

        }

        // 读取当前应用状态对应的配置文件
        if (APP_STATUS && is_file(CONF_PATH.APP_STATUS.CONF_EXT)) {
            C(include CONF_PATH.APP_STATUS.CONF_EXT);
        }

        // 设置系统时区
        date_default_timezone_set(C('DEFAULT_TIMEZONE'));

        // 检查应用目录结构 如果不存在则自动创建
        if (C('CHECK_APP_DIR')) {
            $module = defined('BIND_MODULE')
                ? BIND_MODULE
                : C(
                    'DEFAULT_MODULE'
                );
            if (!is_dir(APP_PATH.$module) || !is_dir(LOG_PATH)) {
                // 检测应用目录结构
                Build::checkDir($module);
            }
        }

        // 记录加载文件时间
        G('loadTime');
    }

    /**
     * 取得对象实例 支持调用类的静态方法
     * @deprecated
     * @param string $class 对象类名
     * @param string $method 类的静态方法名
     *
     * @return object
     */
    public static function instance($class, $method = '')
    {
        $identify = $class.$method;
        if (!isset(self::$_instance[$identify])) {
            if (class_exists($class)) {
                $o = new $class();
                if (!empty($method) && method_exists($o, $method)) {
                    self::$_instance[$identify] = call_user_func(
                        [&$o, $method]
                    );
                } else {
                    self::$_instance[$identify] = $o;
                }
            } else {
                self::halt(L('_CLASS_NOT_EXIST_').':'.$class);
            }
        }

        return self::$_instance[$identify];
    }

    /**
     * 错误输出
     *
     * @param mixed $error 错误
     *
     * @return void
     */
    public static function halt($error)
    {
        $e = [];
        if (APP_DEBUG || IS_CLI) {
            //调试模式下输出错误信息
            if (!is_array($error)) {
                $trace = debug_backtrace();
                $e['message'] = $error;
                $e['file'] = $trace[0]['file'];
                $e['line'] = $trace[0]['line'];
                ob_start();
                debug_print_backtrace();
                $e['trace'] = ob_get_clean();
            } else {
                $e = $error;
            }
            if (IS_CLI) {
                exit(
                    iconv('UTF-8', 'gbk', $e['message']).PHP_EOL.'FILE: '
                    .$e['file'].'('.$e['line'].')'.PHP_EOL.$e['trace']
                );
            }
        } else {
            //否则定向到错误页面
            $error_page = C('ERROR_PAGE');
            if (!empty($error_page)) {
                redirect($error_page);
            } else {
                $message = is_array($error) ? $error['message'] : $error;
                $e['message'] = C('SHOW_ERROR_MSG')
                    ? $message
                    : C(
                        'ERROR_MESSAGE'
                    );
            }
        }
        // 包含异常页面模板
        $exceptionFile = C(
            'TMPL_EXCEPTION_FILE',
            null,
            THINK_PATH.'Tpl/think_exception.tpl'
        );
        include $exceptionFile;
        exit;
    }

    /**
     * 自定义异常处理
     *
     *
     * @param mixed $e 异常对象
     */
    public static function appException($e)
    {
        $error = [];
        $error['message'] = $e->getMessage();
        $trace = $e->getTrace();
        if ('E' == $trace[0]['function']) {
            $error['file'] = $trace[0]['file'];
            $error['line'] = $trace[0]['line'];
        } else {
            $error['file'] = $e->getFile();
            $error['line'] = $e->getLine();
        }
        $error['trace'] = $e->getTraceAsString();
        Log::record($error['message'], Log::ERR);
        // 发送404信息
        header('HTTP/1.1 404 Not Found');
        header('Status:404 Not Found');
        self::halt($error);
    }

    // 致命错误捕获

    /**
     * 自定义错误处理
     *
     *
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     *
     * @return void
     */
    public static function appError($errno, $errstr, $errfile, $errline)
    {
        switch ($errno) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                ob_end_clean();
                $errorStr = "$errstr ".$errfile." 第 $errline 行.";
                if (C('LOG_RECORD')) {
                    Log::write("[$errno] ".$errorStr, Log::ERR);
                }
                self::halt($errorStr);
                break;
            default:
                $errorStr = "[$errno] $errstr ".$errfile." 第 $errline 行.";
                self::trace($errorStr, '', 'NOTIC');
                break;
        }
    }

    /**
     * 添加和获取页面Trace记录
     *
     * @param string $value 变量
     * @param string $label 标签
     * @param string $level 日志级别(或者页面Trace的选项卡)
     * @param bool $record 是否记录日志
     *
     * @return void|array
     */
    public static function trace(
        $value = '[think]',
        $label = '',
        $level = 'DEBUG',
        $record = false
    ) {
        static $_trace = [];
        // 获取trace信息
        if ('[think]' === $value) {
            return $_trace;
        } else {
            $info = ($label ? $label.':' : '').print_r($value, true);
            $level = strtoupper($level);

            if ((defined('IS_AJAX') && IS_AJAX) || !C('SHOW_PAGE_TRACE')
                || $record) {
                Log::record($info, $level, $record);
            } else {
                if (!isset($_trace[$level])
                    || count($_trace[$level]) > C(
                        'TRACE_MAX_RECORD'
                    )) {
                    $_trace[$level] = [];
                }
                $_trace[$level][] = $info;
            }
        }
    }

    public static function fatalError()
    {
        Log::save();
        if ($e = error_get_last()) {
            switch ($e['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    ob_end_clean();
                    self::halt($e);
                    break;
            }
        }
    }
}
