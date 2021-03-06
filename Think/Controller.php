<?php

namespace Think;

/**
 * ThinkPHP 控制器基类 抽象类
 */
abstract class Controller
{

    /**
     * 视图实例对象
     *
     * @var view
     */
    protected $view = null;

    /**
     * 控制器参数
     *
     * @var $config
     */
    protected $config = [];

    /**
     * 取得模板对象实例
     */
    public function __construct()
    {
        Hook::listen('action_begin', $this->config);
        //实例化视图类
        $this->view = new View;
        //控制器初始化
        if (method_exists($this, '__init')) {
            $this->__init();
        }
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value)
    {
        $this->assign($name, $value);
    }

    /**
     * 模板变量赋值
     *
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     *
     * @return $this
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);

        return $this;
    }

    /**
     * 取得模板显示变量的值
     *
     * @param string $name 模板显示变量
     *
     * @return mixed
     */
    public function get($name = '')
    {
        return $this->view->get($name);
    }

    /**
     * 检测模板变量的值
     *
     * @param string $name 名称
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->get($name);
    }

    /**
     * 魔术方法 有不存在的操作的时候执行
     *
     * @param string $method 方法名
     * @param array $args 参数
     *
     * @return mixed
     * @throws BaseException
     */
    public function __call($method, $args)
    {
        if (0 === strcasecmp($method, ACTION_NAME.C('ACTION_SUFFIX'))) {
            if (method_exists($this, '_empty')) {
                // 如果定义了_empty操作 则调用
                $this->_empty($method, $args);
            } elseif (file_exists_case($this->view->parseTemplate())) {
                // 检查是否存在默认模版 如果有直接输出模版
                $this->display();
            } else {
                E(L('_ERROR_ACTION_').':'.ACTION_NAME);
            }
        } else {
            E(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));

            return;
        }
    }

    /**
     * 模板显示 调用内置的模板引擎显示方法，
     *
     * @param string $templateFile 指定要调用的模板文件
     *                             默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     * @param string $content 输出内容
     * @param string $prefix 模板缓存前缀
     *
     * @return void
     * @throws BaseException
     */
    protected function display(
        $templateFile = '',
        $charset = '',
        $contentType = '',
        $content = '',
        $prefix = ''
    ) {
        $this->view->display(
            $templateFile,
            $charset,
            $contentType,
            $content,
            $prefix
        );
    }

    /**
     * 析构方法
     *
     */
    public function __destruct()
    {
        // 执行后续操作
        Hook::listen('action_end');
    }

    /**
     * 输出内容文本可以包括Html 并支持内容解析
     *
     * @param string $content 输出内容
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     * @param string $prefix 模板缓存前缀
     *
     * @return mixed
     * @throws BaseException
     */
    protected function show(
        $content,
        $charset = '',
        $contentType = '',
        $prefix = ''
    ) {
        $this->view->display('', $charset, $contentType, $content, $prefix);
    }

    /**
     *  创建静态页面
     *
     * @param string $htmlfile 生成的静态文件名称
     * @param string $htmlpath 生成的静态文件路径
     * @param string $templateFile 指定要调用的模板文件
     *                             默认为空 由系统自动定位模板文件
     *
     * @return string
     * @throws BaseException
     */
    protected function buildHtml(
        $htmlfile = '',
        $htmlpath = '',
        $templateFile = ''
    ) {
        $content = $this->fetch($templateFile);
        $htmlpath = !empty($htmlpath) ? $htmlpath : HTML_PATH;
        $htmlfile = $htmlpath.$htmlfile.C('HTML_FILE_SUFFIX');
        Storage::put($htmlfile, $content, 'html');

        return $content;
    }

    /**
     *  获取输出页面内容
     * 调用内置的模板引擎fetch方法，
     *
     * @param string $templateFile 指定要调用的模板文件
     *                             默认为空 由系统自动定位模板文件
     * @param string $content 模板输出内容
     * @param string $prefix 模板缓存前缀*
     *
     * @return string
     * @throws BaseException
     */
    protected function fetch($templateFile = '', $content = '', $prefix = '')
    {
        return $this->view->fetch($templateFile, $content, $prefix);
    }

    /**
     * 模板主题设置
     *
     * @param string $theme 模版主题
     *
     * @return $this
     */
    protected function theme($theme)
    {
        $this->view->theme($theme);

        return $this;
    }

    /**
     * 操作错误跳转的快捷方法
     *
     *
     * @param string $message 错误信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     *
     * @return void
     * @throws BaseException
     */
    protected function error($message = '', $jumpUrl = '', $ajax = false)
    {
        $this->dispatchJump($message, 0, $jumpUrl, $ajax);
    }

    /**
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     *
     * @param string $message 提示信息
     * @param int $status 状态
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @throws BaseException
     */
    private function dispatchJump(
        $message,
        $status = 1,
        $jumpUrl = '',
        $ajax = false
    ) {
        // AJAX提交
        if (true === $ajax || IS_AJAX) {
            $data = is_array($ajax) ? $ajax : [];
            $data['info'] = $message;
            $data['status'] = $status;
            $data['url'] = $jumpUrl;
            $this->ajaxReturn($data);
        }
        if (is_int($ajax)) {
            $this->assign('waitSecond', $ajax);
        }
        if (!empty($jumpUrl)) {
            $this->assign('jumpUrl', $jumpUrl);
        }
        // 提示标题
        $this->assign(
            'msgTitle',
            $status ? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_')
        );
        //如果设置了关闭窗口，则提示完毕后自动关闭窗口
        if ($this->get('closeWin')) {
            $this->assign('jumpUrl', 'javascript:window.close();');
        }
        // 状态
        $this->assign('status', $status);
        //保证输出不受静态缓存影响
        C('HTML_CACHE_ON', false);
        if ($status) { //发送成功信息
            // 提示信息
            $this->assign('message', $message);
            // 成功操作后默认停留1秒
            if (!isset($this->waitSecond)) {
                $this->assign('waitSecond', '1');
            }
            // 默认操作成功自动返回操作前页面
            if (!isset($this->jumpUrl)) {
                $this->assign("jumpUrl", $_SERVER["HTTP_REFERER"]);
            }
            $this->display(C('TMPL_ACTION_SUCCESS'));
        } else {
            // 提示信息
            $this->assign('error', $message);
            //发生错误时候默认停留3秒
            if (!isset($this->waitSecond)) {
                $this->assign('waitSecond', '3');
            }
            // 默认发生错误的话自动返回上页
            if (!isset($this->jumpUrl)) {
                $this->assign('jumpUrl', "javascript:history.back(-1);");
            }
            $this->display(C('TMPL_ACTION_ERROR'));
            // 中止执行  避免出错后继续执行
            exit;
        }
    }

    /**
     * Ajax方式返回数据到客户端
     *
     * @param mixed $data 要返回的数据
     * @param String $type AJAX返回数据格式
     * @param int $json_option 传递给json_encode的option参数
     */
    protected function ajaxReturn($data, $type = '', $json_option = 0)
    {
        if (empty($type)) {
            $type = C('DEFAULT_AJAX_RETURN');
        }
        switch (strtoupper($type)) {
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode($data, $json_option));
            case 'XML'  :
                // 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $handler = isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C(
                    'VAR_JSONP_HANDLER'
                )] : C('DEFAULT_JSONP_HANDLER');
                exit($handler.'('.json_encode($data, $json_option).');');
            case 'EVAL' :
                // 返回可执行的js脚本
                header('Content-Type:text/html; charset=utf-8');
                exit($data);
            default     :
                // 用于扩展其他返回格式数据
                Hook::listen('ajax_return', $data);
        }
    }

    /**
     * 操作成功跳转的快捷方法
     *
     * @param string $message 提示信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @throws BaseException
     */
    protected function success($message = '', $jumpUrl = '', $ajax = false)
    {
        $this->dispatchJump($message, 1, $jumpUrl, $ajax);
    }

    /**
     * Action跳转(URL重定向） 支持指定模块和延时跳转
     *
     * @param string $url 跳转的URL表达式
     * @param array $params 其它URL参数
     * @param int $delay 延时跳转的时间 单位为秒
     * @param string $msg 跳转提示信息
     */
    protected function redirect($url, $params = [], $delay = 0, $msg = '')
    {
        $url = U($url, $params);
        redirect($url, $delay, $msg);
    }
}
