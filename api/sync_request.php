<?php
/**
 * 测试请求的
 * 于： 2016-3-15 上午9:57:49
 * 
 * @author    笑果(go_tit@163.com)
 * @copyright 2006-2016 IGG.INC
 */

define('DEBUG', TRUE);
require dirname(__FILE__) . '/common.inc.php';

/**
 * 控制器
 */
class SyncRequestController extends SimpleController
{

    /**
     * 同步动作
     *
     * @return void
     */
    public function indexAction()
    {
        $result = 0;
        do {
            $data = array(
                'created_at' => time(),
                'ip' => CUSTOMER_IP,
            );
            
            $data = addslashes_deep($data);
            $db = self::getDb();
            $db->autoExecute('request_test_log', $data);
            $result = $db->insert_id();
        } while (0);
        
        echo json_encode($result);
    }
}

$controller = new SyncRequestController();
SimpleDispatcher::dispatch($controller);