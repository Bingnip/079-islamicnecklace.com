<?php
/**
 * 本地IP接口
 * 于： 2015年12月10日 下午1:35:14
 * 
 * @author    笑果(go_tit@163.com)
 * @copyright 2006-2015 IGG.INC
 */

define('DEBUG', TRUE);
require dirname(__FILE__) . '/common.inc.php';


class ThisController extends SimpleController
{
    const KEY = 'netflying';
    
    public function __construct()
    {
        
    }
    
    public function indexAction()
    {
        echo 'hi';
    }
    
    /**
     * 验证加密串
     * 
     * @param array  $data    数据
     * @param string $signKey KEY
     * @return boolean 成功与否
     */
    protected function verify(array $data, $signKey = 'sign')
    {
        $ret = FALSE;
        if (isset($data[$signKey]))
        {
            $sign = $data[$signKey];
            unset($data[$signKey]);
            $queryString = http_build_query($data, NULL, '&');
            
            if (md5($queryString . self::KEY) == $sign)
            {
                $ret = TRUE;
            }
        }
        
        return $ret;
    }
    
    public function recordAction()
    {
        $json = JsonResponse::instance();
        
        do {
            $get = $_GET;
            if (!$this->verify($get))
            {
                $json->setError('sign error');
                break;
            }
            
            $key = isset($_GET['key']) ? $_GET['key'] : '';
            if (!$key)
            {
                $json->setError('key not set');
            }
            
            $ip = CUSTOMER_IP;
            $data = array(
                'ip' => $ip,
                'created_time' => time(),
                'key' => $key,
            );
            
            $db = Koba::getDb();
            $db->autoExecute('netflying_ip_log', $data);
            
            $json->setResult($db->insert_id());
        } while(FALSE);
        
        $json->response();
    }
    
    /**
     * 获取IP
     */
    public function showIpAction()
    {
        $db = Koba::getDb();
        
        $key = isset($_GET['key']) ? $_GET['key'] : 'local';
        
        $sql = "SELECT * FROM `netflying_ip_log` WHERE `key`=" . qs($key) . ' ORDER BY `id` DESC LIMIT 1';
        $info = $db->getRow($sql);
        
        if ($info)
        {
            $time = date("Y-m-d H:i:s", $info['created_time']);
            
            echo "{$time}<br/>\n{$info['ip']}";
        }
        else
        {
            echo 'not found';
        }
    }
}

try {
    $controller = new ThisController();
    SimpleDispatcher::dispatch($controller);
} catch (Exception $e) {
    header('404 Not Found');
    echo '404 Not Found';exit;
}
