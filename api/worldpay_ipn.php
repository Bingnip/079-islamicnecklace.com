<?php
/**
 * WP的IPN消息转发接收程序
 * 于： 2017-03-29 15:33:09
 * 
 * @author    笑果(go_tit@163.com)
 * @copyright 2006-2017 Netflying.inc
 */

use WorldPay\WorldPay;
use Models\Order;

require dirname(__FILE__) . '/common.inc.php';

// 加密密钥
define('THIS_ENCYPT_KEY', 'k#J3GHOWqxRs&*Jz');

/**
 * 控制器
 */
class WorldPayIpnController extends SimpleController
{
    /**
     * 检测
     * 
     * @throws Exception 出错就抛异常
     */
    protected function preCheck()
    {
        $sign = isset($_POST['sign']) ? $_POST['sign'] : '';
        $signPass = false;
        if ($sign) {
            $signData = $_POST;
            unset($signData['sign']);
            if ($signData) {
                ksort($signData);
                $tmp = [];
                foreach ($signData as $key => $value) {
                    $tmp[] = "$key={$value}";
                }
                
                if (md5(implode('&', $tmp) . THIS_ENCYPT_KEY) == $sign) {
                    $signPass = true;
                }
            }
        }
        
        if (!$signPass) {
            throw new Exception('SIGN验证出错');
        }
        
        
    }
    
    /**
     * 接收程序
     * 
     * @return void
     */
    public function indexAction()
    {
        $json = JsonResponse::instance();
        
        do {
            if ($_SERVER['REQUEST_METHOD'] != 'POST') {
                $json->setError('POST Only');
                break;
            }
            
            $string = isset($_POST['data']) ? $_POST['data'] : '';
            $data = json_decode($string, TRUE);

            // 写到日志
            self::writeLogToFile(json_encode($data), 'forward');
    
            try {
                $this->preCheck();
            } catch (Exception $e) {
                $json->setError($e->getMessage());
                break;
            }
            
            // 订单id
            $orderId = $data['OrderCode'];
            // 由于站点加了标识: sign-orderid-other
            if (stripos($orderId, '-') !== false) {
                $tmp = explode('-', $orderId);
                $orderId = $tmp[1];
            }

            // 付款状态
            $paymentStatus = $data['PaymentStatus'];

            // 看下是不是支付成功的状态，如果是，需要更新下成功状态的字串为我们可认的
            if (WorldPay::isCompletedStatus($paymentStatus)) {
                $paymentStatus = 'Completed';
            }

            // 付款金额
            $payAmount = $data['PaymentAmount'] / 100;
            // 币种
            $currencyCode = $data['PaymentCurrency'];
            // 付款方式
            $paymentMethod = $data['PaymentMethod'];
            //
            $txnId = $data['PaymentId'];
            // 查询订单
            $orderInfo = OrderHelper::getOrderInfo($orderId);

            if ($orderInfo) {
                $arrUpdate = [];
                if ($orderInfo['o_pay_status'] != $paymentStatus) {
                    $arrUpdate['o_pay_status'] = $paymentStatus;
                }

                if ($orderInfo['o_pay_amt'] == 0) {
                    $arrUpdate['o_pay_amt'] = $payAmount;
                }

                if ($orderInfo['o_pay_method'] != $paymentMethod && $paymentMethod) {
                    $arrUpdate['o_pay_method'] = $paymentMethod;
                }

                if ($orderInfo['o_pay_currency_code'] != $currencyCode && $currencyCode) {
                    $arrUpdate['o_pay_currency_code'] = $currencyCode;
                }
                // 需要更新的话
                if ($arrUpdate) {
                    OrderHelper::updateOrder($orderId, $arrUpdate);
                }

                // 看下是否为refunded的状态
                if (WorldPay::isRefundedStatus($paymentStatus)) {
                    // 如果完成单，然后状态变成这样的，生成退单
                    if ($orderInfo['o_pay_status'] == OrderHelper::STATUS_PAY_COMPLETED) {
                        // 生成退单
                        if (!Order::renderReversalOrder($orderId, $paymentStatus, $payAmount)) {
                            error_log("worldpay-usd生成退单失败:" . $orderId);
                        }
                    }


                    /*
                    $arrInsert = $orderInfo;
                    unset($arrInsert['o_id']);
                    $arrInsert['o_amount'] = -$arrInsert['o_amount'];
                    $arrInsert['o_pay_amt'] = -$arrInsert['o_pay_amt'];
                    $arrInsert['o_pay_status'] = $paymentStatus;
                    $arrInsert['o_last_time'] = time();
                    
                    $arrInsert = addslashes_deep($arrInsert);
                    
                    $db->autoExecute('t_order', $arrInsert);
                    
                    $newId = $db->insert_id();
                    // 添加入关联表
                    $arrInsert = array();
                    $arrInsert['new_id'] = $newId;
                    $arrInsert['old_id'] = $orderId;
                    $arrInsert['add_time'] = time();
                    // 如果有理由信息，则添加下
                    // $arrInsert['memo'] = key_exists('reason_code', $_POST) && $_POST['reason_code']?$_POST['reason_code']:'';
                    
                    $arrInsert = addslashes_deep($arrInsert);
                    $db->autoExecute('t_order_reversal_rel', $arrInsert);*/
                } elseif ($paymentStatus == 'Completed') {
                    // 执行下成功收到IPN消息的处理事件
                    SiteEventHandlers::whenReceiveComplatedIpn($orderId);
                    // 如果状态为成功，并且还没有发送过邮件，就去发送了
                    if (!$orderInfo['o_email_sent'] && $orderInfo['o_email']) {
                        $itemHtml = Func::getItemHtml($orderId);
                        Func::sendPayEmail($orderInfo['o_email'], $orderInfo['o_first_name'] . ' ' . $orderInfo['o_last_name'], $orderId, $itemHtml);
                    }
                }

                // 插入ipn日志
                Func::addIpnLog($orderId, '', '', $txnId, $paymentStatus, json_encode($data));

                $json->setResult($orderId);
            } else {
                $json->setError('Order not found');
            }
        } while (0);
        
        $json->response();
    }

    protected static function writeLogToFile($data, $type = 'base')
    {
        $dir = $file = ROOT_DATA . 'worldpay/';
        if (!is_dir($dir)) {
            createfolder($dir, 0775);
        }
    
        $file = ROOT_DATA . 'worldpay/' . date('Y-m') . ".{$type}.log";

        $f = fopen($file, 'a');

        fwrite($f, date('Y-m-d H:i:s') . "\n--------------------\n" . $data . "\n--------------------\n\n");
        fclose($f);
    }
}

WorldPayIpnController::dispatch();