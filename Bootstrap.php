<?php

//----------------------------------
// ThinkPHP公共入口文件
//----------------------------------

// 记录开始运行时间
$GLOBALS['_beginTime'] = microtime(true);
// 记录内存初始使用
define('MEMORY_LIMIT_ON', function_exists('memory_get_usage'));
if (MEMORY_LIMIT_ON) {
    $GLOBALS['_startUseMems'] = memory_get_usage();
}

// 版本信息
const THINK_VERSION = '3.2.3';

// URL 模式定义
//普通模式
const URL_COMMON = 0;
//PATHINFO模式
const URL_PATHINFO = 1;
//REWRITE模式
const URL_REWRITE = 2;
// 兼容模式
const URL_COMPAT = 3;

// 类文件后缀
const EXT = '.php';

// 系统常量定义
defined('THINK_PATH') || define('THINK_PATH', __DIR__.'/');
defined('APP_PATH')
|| define(
    'APP_PATH',
    dirname($_SERVER['SCRIPT_FILENAME']).'/'
);
// 应用状态 加载对应的配置文件
defined('APP_STATUS') || define('APP_STATUS', '');
// 是否调试模式
defined('APP_DEBUG') || define('APP_DEBUG', false);

// 自动识别SAE环境
if (function_exists('saeAutoLoader')) {
    defined('APP_MODE') || define('APP_MODE', 'sae');
    defined('STORAGE_TYPE') || define('STORAGE_TYPE', 'Sae');
} else {
    // 应用模式 默认为普通模式
    defined('APP_MODE') || define('APP_MODE', 'common');
    // 存储类型 默认为File
    defined('STORAGE_TYPE')
    || define(
        'STORAGE_TYPE',
        'File'
    );
}

// 系统运行时目录
defined('RUNTIME_PATH')
|| define(
    'RUNTIME_PATH',
    APP_PATH.'Runtime/'
);
// 系统核心类库目录
defined('LIB_PATH')
|| define(
    'LIB_PATH',
    realpath(THINK_PATH.'Library').'/'
);
//defined('CORE_PATH') || define('CORE_PATH', LIB_PATH.'Think/'); // Think类库目录
// 行为类库目录
defined('BEHAVIOR_PATH')
|| define(
    'BEHAVIOR_PATH',
    LIB_PATH.'Behavior/'
);
// 系统应用模式目录
defined('MODE_PATH') || define('MODE_PATH', THINK_PATH.'Mode/');
// 第三方类库目录
defined('VENDOR_PATH') || define('VENDOR_PATH', LIB_PATH.'Vendor/');
// 应用公共目录
defined('COMMON_PATH') || define('COMMON_PATH', APP_PATH.'Common/');
// 应用配置目录
defined('CONF_PATH') || define('CONF_PATH', COMMON_PATH.'Conf/');
// 应用语言目录
defined('LANG_PATH') || define('LANG_PATH', COMMON_PATH.'Lang/');
// 应用静态目录
defined('HTML_PATH') || define('HTML_PATH', APP_PATH.'Html/');
// 应用日志目录
defined('LOG_PATH') || define('LOG_PATH', RUNTIME_PATH.'Logs/');
// 应用缓存目录
defined('TEMP_PATH') || define('TEMP_PATH', RUNTIME_PATH.'Temp/');
// 应用数据目录
defined('DATA_PATH') || define('DATA_PATH', RUNTIME_PATH.'Data/');
// 应用模板缓存目录
defined('CACHE_PATH')
|| define(
    'CACHE_PATH',
    RUNTIME_PATH.'Cache/'
);
// 配置文件后缀
defined('CONF_EXT') || define('CONF_EXT', '.php');
// 配置文件解析方法
defined('CONF_PARSE') || define('CONF_PARSE', '');
defined('ADDON_PATH') || define('ADDON_PATH', APP_PATH.'Addon');

// 系统信息
if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    ini_set('magic_quotes_runtime', 0);
    define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc() ? true : false);
} else {
    define('MAGIC_QUOTES_GPC', false);
}
define(
    'IS_CGI',
    (0 === strpos(PHP_SAPI, 'cgi') || false !== strpos(PHP_SAPI, 'fcgi')) ? 1
        : 0
);
define('IS_WIN', strstr(PHP_OS, 'WIN') ? 1 : 0);
define('IS_CLI', PHP_SAPI == 'cli' ? 1 : 0);

if (!IS_CLI) {
    // 当前文件名
    if (!defined('_PHP_FILE_')) {
        if (IS_CGI) {
            //CGI/FASTCGI模式下
            $_temp = explode('.php', $_SERVER['PHP_SELF']);
            define(
                '_PHP_FILE_',
                rtrim(
                    str_replace($_SERVER['HTTP_HOST'], '', $_temp[0].'.php'),
                    '/'
                )
            );
        } else {
            define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'], '/'));
        }
    }
    if (!defined('__ROOT__')) {
        $_root = rtrim(dirname(_PHP_FILE_), '/');
        define('__ROOT__', (($_root == '/' || $_root == '\\') ? '' : $_root));
    }
}

// 应用初始化
Think\Think::start();