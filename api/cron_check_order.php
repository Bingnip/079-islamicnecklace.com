<?php
/**
 * 订单异常检测
 * 于： 2016-12-08 15:10:12
 * 
 * @author    笑果(go_tit@163.com)
 * @copyright 2006-2016 Netflying.inc
 */

require dirname(__FILE__) . '/common.inc.php';

/**
 * 控制器
 */
class CronCheckOrderController extends SimpleController
{

    /**
     * 检测
     *
     * @return void
     */
    public function indexAction()
    {
        /**
         * 
         *款式:
         * FA68  1926  1979  1806
        INF13  1923  1745  1675  1654   1348   1333   1252
        INF14  1924  1329  1304
        FA37  1943  1942  1748  1614  1137
        MO07  738
         */
        // 产品的ID配置
        $goodsConfigs = [
            [1926, 1979, 1806], // FA68
            [1923, 1745, 1675, 1654, 1348, 1333, 1252], // INF13
            [1924, 1329, 1304], // INF14
            [1943, 1942, 1748, 1614, 1137], // FA37
            [738], // MO07
        ];
        
        $startTime = strtotime("2016-10-01 00:00:00");
        $endTime = strtotime("2016-10-30 23:59:59");

        foreach ($goodsConfigs as $goodsConfig) {
            $sql = "SELECT t1.o_id, o_add_time, o_pay_currency_code, o_pay_amt, count(od_id) c 
              FROM `t_order` t1 LEFT JOIN `t_order_detail` t2 ON(t1.o_id=t2.o_id) WHERE `o_deleted`=0 AND `o_pay_status`='Completed' AND o_add_time BETWEEN {$startTime} AND {$endTime} AND t2.g_id IN(" . implode(",", $goodsConfig) . ") GROUP BY `o_id`";

            $list = self::getDb()->getAll($sql);

            if ($list) {
                $result = [];
                foreach ($list as $item) {
                    $theDay = date('Y-m-d', $item['o_add_time']);
                    $theHour = date('H', $item['o_add_time']);
                    $theHour = floor($theHour / 3);
                    isset($result[$theDay]) || $result[$theDay] = [];
                    isset($result[$theDay][$theHour]) || $result[$theDay][$theHour] = 0;
                    $result[$theDay][$theHour] ++;
                }

                $ff = [];
                foreach ($result as $theDay => $datas) {
                    $min = min($datas);
                    $ff[] = $min;
                    echo "$theDay:" . $min . "\n";
                }
                
                echo "AVG:" . (array_sum($ff) / count($ff));
            }
        }
    }
}

$controller = new CronCheckOrderController();
SimpleDispatcher::dispatch($controller);