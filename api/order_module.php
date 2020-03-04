<?php
/**
 * 订单模块
 */

use helpers\SystemMailSender;
use helpers\CurrencyHelper;

define('DEBUG', true);
require dirname(__FILE__) . '/common.inc.php';

// $list = ThisModule::getOrderDetail(1000000474, TRUE);
// var_dump($list);exit;

class OrderModuleController extends SimpleController
{
    const STATUS_ORIGINAL = 'processing';
    
    public function __construct()
    {
        $json = JsonResponse::instance();
        
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $json->setError('只允许POST', 1000, true);
            exit;
        }
        
        $post = $_POST;
        unset($post['sign']);
        $queryString = http_build_query($post, NULL, '&');
        
        if ($_POST['sign'] != md5(SECURITY_KEY . $queryString)) {
            $json->setError('验证失败', 1, true);
            exit;
        }
        
        // 将请求日志记录到日志库中
        $theAction = isset($_POST['action']) ? $_POST['action'] : 'index';
        
        $this->addRequestLog($theAction, $_POST);
    }
    
    /**
     * 添加请求日志
     *
     * @param string $action 动作
     * @param array  $data   数据
     * @return number 日志ID
     */
    private function addRequestLog($action, array $data)
    {
        $insertData = [
            'action'   => $action,
            'data'     => json_encode($data),
            'add_time' => time(),
            'ip'       => CUSTOMER_IP,
        ];
        
        $db = self::getDb();
        
        $db->autoExecute('api_order_module_request_log', addslashes_deep($insertData));
        
        return $db->insert_id();
    }
    
    /**
     * 更新订单的物流号以及状态
     */
    public function updateOrderShipInfoAction()
    {
        //什么状态下才去更新订单状态
        $arrOStatus = [
            'processing',
            'payed',
        ];
        
        do {
            $db   = self::getDb();
            $json = JsonResponse::instance();
            
            $targetStatus = 'signed'; //目标状态
            //
            $oid        = intval($_POST['oid']);
            $shipNumber = $_POST['ship_number'];
            $shipName   = $_POST['ship_name'];
            $postData   = isset($_POST['data']) ? json_decode($_POST['data'], true) : [];
            if (!is_array($postData)) {
                $postData = [];
            }
            
            $sql  = 'SELECT * FROM `t_order` WHERE `o_deleted`=0 AND `o_id`=' . $oid;
            $info = $db->getRow($sql);
            
            if (!$info) {
                $json->setError('找不到该订单：' . $oid);
                break;
            }
            
            $arrUpdate = [];
            if ($shipName) {
                $arrUpdate['ship_name'] = $shipName;
            }
            
            if ($shipNumber) {
                $arrUpdate['ship_number'] = $shipNumber;
            }
            
            //
            $statusUpdated = false;
            if (in_array($info['o_status'], $arrOStatus)) {
                $arrUpdate['o_status'] = $targetStatus;
                $statusUpdated         = true;
            }
            
            //如果有更新，就去更新了
            $result = [
                'success'       => 1,
                'msg'           => '',
                'target_status' => $info['o_status'],
            ];
            
            if ($arrUpdate) {
                $arrUpdate['o_opt_time'] = time();
                $db->autoExecute('t_order', $arrUpdate, 'UPDATE', 'o_id=' . $oid . '');
                if (!$db->affected_rows()) {
                    $json->setError('更新失败！');
                    break;
                }
                
                //如果成功，添加下备注
                Func::addOrderMemo($oid, '同步运单信息：' . $shipNumber, $statusUpdated ? $info['o_status'] : NULL, $statusUpdated ? $targetStatus : NULL, '程序自动');
                //如果有传递运单号过来
                if (isset($arrUpdate['ship_number'])) {
                    // 需要先判断下是不是需要发送
                    if (!$info['ship_number'] || $info['ship_number'] == $arrUpdate['ship_number']) {
                        //原来的订单号不为空，或者与新运单号不一致的话，需要先检测下是否已经发过
                        $ifCanSend = CommonLogHelper::getIfExist('sync_order_ship_info', $oid) ? false : true;
                    } else {
                        $ifCanSend = true;
                    }
                    
                    if ($ifCanSend) {
                        $arrVarValue = [
                            'orderId'    => $oid,
                            'shipNumber' => $arrUpdate['ship_number'],
                            'shipName'   => $arrUpdate['ship_name'] ? $arrUpdate['ship_name'] : $info['ship_name'],
                        ];
                        
                        $theEmail = $info['o_email'];
                        $name     = $info['o_first_name'] . ' ' . $info['o_last_name'];
                        $shipName = $arrUpdate['ship_name'] ? $arrUpdate['ship_name'] : $info['ship_name'];
    
                        // 额外的字段信息
                        $arrExtra = [];
                        // 如果有传递地址，就用这个地址
                        if (isset($postData['address']) && $postData['address']) {
                            $arrExtra['shippingAddress'] = $postData['address'];
                        }
                        
                        // 发送ship 邮件
                        if ($logId = SystemMailSender::sendShippingMail($theEmail, $name, $oid, $arrUpdate['ship_number'], $shipName, $arrExtra)) {
                            //添加普通日志
                            CommonLogHelper::add('sync_order_ship_info', $oid, $logId);
                            //添加理备注日志
                            Func::addOrderMemo($oid, '发送发货邮件!邮件队列：' . $logId, $statusUpdated ? $targetStatus : NULL, $statusUpdated ? 'ship_signed' : 'NULL', '程序自动');
                            //更新下状态
                            if ($statusUpdated) {
                                $arr['o_status'] = 'ship_signed';
                                $db->autoExecute('t_order', $arr, 'UPDATE', 'o_id=' . $oid . '');
                                
                                // 如果有更新到这里，也需要返回下该订单的状态
                                $result['target_status'] = 'ship_signed';
                            }
                        }
                    }
                }
                
                $result['msg'] = '更新成功';
            } else {
                $result['msg'] = '不用更新';
            }
        } while (false);
        //返回结果了
        $json->setResult($result, true);
    }
    
    /**
     * 获取还未同步的订单数
     */
    public function getNoAddedCountAction()
    {
        $sql   = 'SELECT COUNT(*) c FROM t_order WHERE o_deleted=0 AND o_added=0';
        $count = self::getDb()->getValue($sql);
        
        JsonResponse::instance()->setResult($count, true);
    }
    
    /**
     * 获取完成单列表，不区分是否已经同步
     */
    public function fetchAction()
    {
        //根据请求获取列表，开始ID，数量
        $id    = intval($_POST['id']);
        $limit = intval($_POST['num']);
        if ($limit < 0) {
            $limit = 10;
        }
        if ($limit > 50) {
            $limit = 50;
        }
        $list = ThisModule::getOrderList($id, $limit);
        
        JsonResponse::instance()->setResult($list, true);
    }
    
    /**
     * 获取没有同步的列表
     */
    public function fetchNotAddedAction()
    {
        $limit = intval($_POST['num']); //个数
        if ($limit <= 0) {
            $limit = 10;
        }
        if ($limit > 50) {
            $limit = 50;
        }
        $list = ThisModule::getOrderListNotAdded($limit);
        
        JsonResponse::instance()->setResult($list, true);
    }
    
    /**
     * 获取订单的详细信息
     */
    public function getOrderInfoAction()
    {
        $oid  = intval($_POST['oid']);
        $json = JsonResponse::instance();
        $info = ThisModule::getOrderInfo($oid, true);
        if ($info) {
            $json->setResult($info, true);
        } else {
            $json->setError('找不到此订单信息', 2, true);
        }
    }
    
    /**
     * 设置订单状态为已经同步到本地
     */
    public function setOrderAddedAction()
    {
        $oid          = intval($_POST['oid']);
        $updateStatus = $_POST['update']; //是否要更新订单的状态
        $json         = JsonResponse::instance();
        
        if ($oid == 0) {
            $json->setError('订单ID为空！');
            exit;
        }
        
        // 获取订单信息
        $orderInfo = ThisModule::getOrderInfo($oid);
        
        if (!$orderInfo) {
            $json->setError('找不到此订单', 10001, true);
            exit;
        }
        
        $arr                = [];
        $arr['o_added']     = 1;
        $arr['o_last_time'] = time();
        
        //如果
        $statusUpdated = false;
        if ($orderInfo && $orderInfo['o_status'] == self::STATUS_ORIGINAL) {
            $arr['o_status'] = 'payed';
            $statusUpdated   = true;
        }
        
        $db = self::getDb();
        
        $db->autoExecute('t_order', $arr, 'UPDATE', 'o_id=' . $oid . '');
        $success = $db->affected_rows() ? 1 : 0;
        if ($success) {
            Func::addOrderMemo($oid, '设置订单同步状态为已同步', $statusUpdated ? self::STATUS_ORIGINAL : NULL, $statusUpdated ? 'payed' : NULL, '程序自动');
        }
        
        $ret = [
            'oid'     => $oid,
            'success' => $success,
        ];
        
        $json->setResult($ret, true);
    }
}

$controller = new OrderModuleController();
SimpleDispatcher::dispatch($controller);

/*
 * 此模块帮助类
 */

class ThisModule
{
    const ORDER_COLS = '*';
    private static $db = NULL;
    
    //
    private static function getDb()
    {
        if (is_null(self::$db)) {
            self::$db = openDb();
        }
        
        return self::$db;
    }
    
    //
    public static function getOrderList($start, $limit)
    {
        $db  = self::getDb();
        $sql = 'SELECT  ' . self::ORDER_COLS . '
            FROM `t_order` WHERE o_pay_status=\'Completed\' o_deleted=0 AND o_id>=' . intval($start) . ' ORDER BY o_id LIMIT ' . intval($limit);
        #echo $sql;exit;
        $list = $db->getAll($sql);
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['detail']   = self::getOrderDetail($value['o_id']);
                $list[$key]['shipping'] = self::getOrderShippingInfo($value['o_id']);
            }
        }
        
        return $list;
    }
    
    //获取没有同步的订单的详细信息
    public static function getOrderListNotAdded($limit)
    {
        $db  = self::getDb();
        $sql = 'SELECT  ' . self::ORDER_COLS . '
            FROM `t_order` 
            WHERE `o_status`=\'processing\' AND `o_deleted`=0 AND `o_added`=0 AND (`o_pay_status`=\'Completed\' OR `o_pay_status`=\'Refunded\' OR `need_sync`=1) 
            ORDER BY `o_id` 
            LIMIT ' . intval($limit);
        
        $list = $db->getAll($sql);
        
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['detail']   = self::getOrderDetail($value['o_id']);
                $list[$key]['shipping'] = self::getOrderShippingInfo($value['o_id']);
                if($value['currency'] != $value['o_pay_currency_code']){
                    //支付货币与用户网站所选货币不一致的话，取支付货币的汇率返回
                    $list[$key]['currency'] = $value['o_pay_currency_code'];
                    $list[$key]['currency_rate'] = CurrencyHelper::getExistCurrencyRateByCode($value['o_pay_currency_code']);
                    $list[$key]['currency_amount'] = $value['o_pay_amt'];
                }
                //查询撤销单、负单关联表
                $list[$key]['reversal_rel_o_id'] = 0;
                if($value['o_amount']<0){
                    $reversalRelSql = 'select * from t_order_reversal_rel where new_id = ' . intval($value['o_id']);
                    $reversalRelOrder  = $db->getRow($reversalRelSql);
                    if ($reversalRelOrder) {
                        $list[$key]['reversal_rel_o_id'] = $reversalRelOrder['old_id'];
                    }
                }
            }
        }
        
        return $list;
    }
    
    //
    public static function getOrderInfo($oid, $includeAll = false)
    {
        $db = self::getDb();
        
        $sql = 'SELECT * FROM `t_order` WHERE `o_deleted`=0 AND `o_id`=' . intval($oid);
        
        $ret = $db->getRow($sql);
        if ($ret && $includeAll) {
            $ret['detail']   = self::getOrderDetail($oid);
            $ret['shipping'] = self::getOrderShippingInfo($oid);
        }
        
        return $ret;
    }
    
    /**
     * 获取订单的详细列表
     *
     * @param integer $oid         订单ID
     * @param boolean $includeAttr 是否包含属性
     * @return mixed null|array
     */
    public static function getOrderDetail($oid, $includeAttr = true)
    {
        $db = self::getDb();
        
        $sql  = 'SELECT t1.*
            FROM `t_order_detail` t1 
            WHERE `od_deleted`=0 AND `o_id`=' . intval($oid) . ' ORDER BY `od_id` ASC';
        $list = $db->getAll($sql);
        
        if ($includeAttr) {
            if ($list) {
                foreach ($list as $key => $value) {
                    $id                        = $value['od_id'];
                    $list[$key]['attrs']       = self::getOrderDetailAttrs($id);
                    $list[$key]['goods_attrs'] = self::getGoodsAttr($value['g_id']);
                }
            }
        }
        
        return $list;
    }
    
    public static function getGoodsAttr($gid)
    {
        $gid = intval($gid);
        $sql = "SELECT t1.ga_value, t1.a_id, t2.a_name, t2.a_inner_name 
            FROM `t_goods_attr` t1
                LEFT JOIN `t_attribute` t2
                ON(t1.a_id=t2.a_id) 
            WHERE t1.g_id={$gid} ORDER BY t1.`ga_order`,t1.`ga_id`";
        
        $tmp  = self::getDb()->getAll($sql);
        $list = [];
        if ($tmp) {
            foreach ($tmp as $value) {
                $list[$value['a_id']] = [
                    'name'  => $value['a_name'],
                    'value' => $value['ga_value'],
                ];
            }
        }
        
        return $list;
    }
    
    /**
     * 获取订单详情的定制项的信息
     *
     * @param $odid
     * @return mixed
     */
    public static function getOrderDetailAttrs($odid)
    {
        $db = self::getDb();
        
        $sql  = 'SELECT t1.*, t2.ca_type AS `type`
            FROM `t_order_detail_attr` t1 
              LEFT JOIN `t_goods_cs_attr` t2 ON(t1.ca_id=t2.ca_id)
            WHERE `od_id`=' . intval($odid) . ' ORDER BY `oda_id`';
        $list = $db->getAll($sql);
        
        return $list;
    }
    
    public static function getOrderShippingInfo($oid)
    {
        $db = self::getDb();
        
        $sql = 'SELECT * FROM t_order_shipping WHERE o_id=' . intval($oid);
        $ret = $db->getRow($sql);
        
        return $ret;
    }
}
