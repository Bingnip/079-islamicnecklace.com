<?php
/**
 * API模块公共文件
 */
define('IS_INC', TRUE);
require dirname(dirname(__FILE__)) . '/common/common.inc.php';
dsadadsadsadsadadadas
// 注册下autoloader
spl_autoload_register(array('Koba', 'autoLoader'));

//验证码
define('SECURITY_KEY', 'sdfsd34kkk*&SD&F..');

// 定时程序需要返回的成功开头
define('CRON_PROG_SUCCESS', '__program_run_succeed__');

/**
 * 简单控制器基类
 */
class SimpleController
{
    /**
     * DB实例
     *
     * @var cls_mysql
     */
    private static $db = NULL;

    /**
     * 获取DB实例
     *
     * @return cls_mysql
     */
    public static function getDb()
    {
        if (is_null(self::$db)) {
            self::$db = Koba::getDb();
        }

        return self::$db;
    }
    
    public static function dispatch()
    {
        $action = isset($_GET['action']) && $_GET['action'] ? trim($_GET['action']) : 'index';

        $action = str_replace('_', '-', $action);
        // 处理下这个,将-号后的字母大写
        $i = 0;
        while (($pos = stripos($action, '-')) !== FALSE) {
            $leftStr = substr($action, 0, $pos);
            $rightStr = substr($action, $pos + 1, str_len($action) - $pos);
            if ($rightStr != '') {
                $action = $leftStr . ucfirst(strtolower($rightStr));
            }
            if ($i++ > 20) {
                break;
            }
        }

        $method = $action . 'Action';
        $controller = new static();
        if (method_exists($controller, $method)) {
            call_user_func([$controller, $method]);
        } else {
            throw new Exception('不存在的动作' . $method, 10001);
        }
    }
}

/**
 * 简单分发器
 */
class SimpleDispatcher
{
    private static $defaultAction = 'index';

    public static function dispatch(SimpleController $controller, $action = '')
    {
        if (!$action && isset($_REQUEST['action'])) {
            $action = $_REQUEST['action'];
        }

        if (!$action) {
            $action = self::$defaultAction;
        }

        $action = str_replace('_', '-', $action);
        // 处理下这个,将-号后的字母大写
        $i = 0;
        while (($pos = stripos($action, '-')) !== FALSE) {
            $leftStr = substr($action, 0, $pos);
            $rightStr = substr($action, $pos + 1, str_len($action) - $pos);
            if ($rightStr != '') {
                $action = $leftStr . ucfirst(strtolower($rightStr));
            }
            if ($i++ > 20) {
                break;
            }
        }

        $method = $action . 'Action';
        if (method_exists($controller, $method)) {
            $controller->$method();
        } else {
            throw new Exception('不存在的动作' . $method, 10001);
        }
    }
}