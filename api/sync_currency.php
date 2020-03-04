<?php
/**
 * 汇率自动同步
 *
 * @author 笑果(go_tit@163.com)
 * @since  2015年12月19日 下午4:10:23
 */

use helpers\CurrencyHelper;
use models\Currency;
use models\CurrencyRateUpdateLog;

define('DEBUG', true);
require dirname(__FILE__) . '/common.inc.php';

/**
 * 自动同步控制器
 */
class SyncCurrencyController extends SimpleController
{
    /**
     * 数据源的API接口地址
     *
     * @var string
     */
    // const API_URL = 'http://api.fixer.io/latest?base=USD';
    const API_URL = 'https://v3.exchangerate-api.com/bulk/fcdb599ac0ddd41742073d7c/%s';
    
    /**
     * 获取数据
     *
     * @param mixed $baseCurrency 基准货币
     * @return mixed 数据或者false
     */
    protected function getCurrencyData($baseCurrency = null)
    {
        if (is_null($baseCurrency)) {
            $baseCurrency = $this->getBaseCurrency();
        }
        
        $apiUrl = sprintf(static::API_URL, $baseCurrency);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        curl_close($ch);
        
        $json = json_decode($content, true);
        
        return $json ? $json : false;
    }
    
    /**
     * 获取基准货币
     * 
     * @return string
     */
    protected function getBaseCurrency()
    {
        return 'USD';
    }
    
    /**
     * 执行EMAIL报警
     *
     * @param array $arrMsg
     */
    protected function doAlert(array $arrMsg)
    {
        $alertEmails = [
            'go_tit@163.com',
        ];
        
        $finalSubject = '货币自动更新报警';
        
        $finalContent = implode("\n", $arrMsg);
        
        foreach ($alertEmails as $email) {
            EmailLogHelper::add($email, '', $finalSubject, $finalContent, '', 'alert');
        }
    }
    
    /**
     * 检测动作
     *
     * @return void
     */
    public function checkAction()
    {
        
    }
    
    /**
     * 同步动作
     *
     * @return void
     */
    public function indexAction()
    {
        // echo $this->doAlert(array('测试的'));exit;
        
        $need     = 0;
        $success  = 0;
        $arrAlert = [];
        $baseCurrency = $this->getBaseCurrency();
        do {
            $data = $this->getCurrencyData($baseCurrency);
            if (!$data) {
                $arrAlert[] = '没有数据';
                break;
            }
            
            if (!isset($data['result']) && $data['result'] != 'success') {
                $arrAlert[] = '结果返回不为success，返回:' . $data['result'];
                break;
            }
            
            // 如果没有BASE或者BASE！=USD，就报警了
            if (!isset($data['from']) || $data['from'] != $baseCurrency) {
                $arrAlert[] = '获取到数据的BASE不对';
                break;
            }
            
            // 汇率的日期
            $rateTimestamp = $data['timestamp'];
            $rateDate = date('Y-m-d');
            // 判断下，汇率的日期太久的话，就不更新
            $now  = time();
            $days = ($now - $rateTimestamp) / (3600 * 24);
            if ($days > 5) {
                $arrAlert[] = '汇率的日期超过5天，请确认';
                break;
            }
            
            // 汇率列表
            $rates = $data['rates'];
            // 获取站点的汇率
            $currencyList = Currency::getList();
            if ($currencyList) {
                foreach ($currencyList as $currencyCode => $value) {
                    if ($currencyCode == $baseCurrency) {
                        continue;
                    }
                    
                    $need++;
                    
                    if (!isset($rates[$currencyCode])) {
                        $arrAlert[] = "获取的数据里不存在[{$currencyCode}]的汇率";
                        continue;
                    }
                    
                    // 现在汇率，需要做一下运算以符合我们的数据
                    $currencyRate = $rates[$currencyCode];
                    // $currencyRate = $currencyRate / 0.975;
                    $currencyRate *= 100;
                    
                    // 执行更新 
                    if (Currency::updateRate($currencyCode, $currencyRate)) {
                        // 添加下日志
                        CurrencyRateUpdateLog::addLog($currencyCode, $value['rate'], $currencyRate, $rateDate, '程序自动更新');
                        $success++;
                    } else {
                        // 没有更新成功的话，需要报警
                        $arrAlert[] = "更新汇率不成功[{$currencyCode}]";
                    }
                }
                
                // 清除缓存
                CurrencyHelper::flush();
            } else {
                $arrAlert[] = "站点的货币列表为空";
            }
        } while (0);
        
        if ($arrAlert) {
            $this->doAlert($arrAlert);
        }
        
        $result = [
            'need'    => $need,
            'success' => $success,
            'alert'   => $arrAlert,
        ];
        
        echo TIMER_PROC_SUCCESS . ':::' . json_encode($result);
    }
}

$controller = new SyncCurrencyController();
SimpleDispatcher::dispatch($controller);