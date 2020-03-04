<?php
/**
 * 对于pending单的转存，相当于清除无用订单
 * 于： 2016-12-16 10:48:56
 * 
 * @author    笑果(go_tit@163.com)
 * @copyright 2006-2016 Netflying.inc
 */

require dirname(__FILE__) . '/common.inc.php';

error_reporting(7);
ini_set('display_errors', 1);

/**
 * 控制器
 */
class CronDeleteOrderController extends SimpleController
{

    /**
     * 检测
     *
     * @return void
     */
    public function indexAction()
    {
        $db = self::getDb();
        
        // 3个月前的pending订单，进行转
        $startTime = strtotime('-90 days');
        
        $sql = "SELECT * FROM `t_order` 
        WHERE `need_sync`=0 AND `o_add_time`<={$startTime} AND `o_pay_status`='Pending' AND `o_status`='processing' ORDER BY `o_id` LIMIT 50";
        $list = $db->getAll($sql);
        $result = [];
        if ($list) {
            foreach ($list as $item) {
                // 添加到垃圾桶订单中
                $oid = $item['o_id'];
                // var_dump($item);exit;
                $db->autoExecute('t_order_trash', addslashes_deep($item));
                if ($db->insert_id()) {
                    // 查找下所有的详情，然后添加
                    $sql = "SELECT * FROM `t_order_detail` WHERE `o_id`=" . $oid;
                    $tmpList = $db->getAll($sql);
                    if ($tmpList) {
                        $tmpDetailIds = [];
                        foreach ($tmpList as $detail) {
                            $tmpDetailIds[] = $detail['od_id'];
                            $db->autoExecute('t_order_detail_trash', addslashes_deep($detail));
                            
                            // 循环下attr
                            $sql = "SELECT * FROM `t_order_detail_attr` WHERE `od_id`=" . $detail['od_id'];
                            $ddList = $db->getAll($sql);
                            if ($ddList) {
                                $tmpIds = array();
                                foreach ($ddList as $attr) {
                                    $tmpIds[] = $attr['oda_id'];
                                    $db->autoExecute('t_order_detail_attr_trash', addslashes_deep($attr));
                                }
                                
                                // 清除
                                $sql = "DELETE FROM `t_order_detail_attr` WHERE `oda_id` IN(" . implode(',', $tmpIds) . ')';
                                $db->query($sql);
                            }
                        }

                        // 执行删除
                        $sql = "DELETE FROM `t_order_detail` WHERE `od_id` IN(" . implode(',', $tmpDetailIds) . ')';
                        $db->query($sql);
                    }

                    // 删除订单信息
                    $db->query("DELETE FROM `t_order` WHERE `o_id`=" . $oid);

                    $result[] = $oid;
                }
            }
        }
        
        $json = JsonResponse::instance();
        $json->setResult($result);
        $json->response();
    }
}

$controller = new CronDeleteOrderController();
SimpleDispatcher::dispatch($controller);