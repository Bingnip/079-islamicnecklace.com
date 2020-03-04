<?php
/**
 * 更新缓存
 * 于： 2016-07-26 08:37:00
 *
 * @author    笑果(go_tit@163.com)
 * @copyright 2006-2016 Netflying.inc
 */

use helpers\BlockHelper;
use helpers\CategoryHelper;
use helpers\CurrencyHelper;

define('DEBUG', TRUE);
require dirname(__FILE__) . '/common.inc.php';

/**
 * 更新缓存
 */
class ApiFlushCacheController extends SimpleController
{
    public function allAction()
    {
        $this->smartyAction();
        $this->categoryAction();
        $this->htmlAction();
        $this->indexAction();
    }
    
    /**
     * 同步动作
     *
     * @return void
     */
    public function indexAction()
    {
        // sysConfig
        SysConfig::refreshCache();
        // currrency
        CurrencyHelper::flush();
        // block
        BlockHelper::flush();
        // Url rewrite
        Url::instance()->writeRewriteCache();
        // page
        Url::instance()->writePageKeyCache();
        
        CountryHelper::flushCache();

        echo 'sysconfig, currency, block ,page, rewrite done' . "\n";
    }
    
    public function smartyAction()
    {
        // 清掉html缓存
        $cacheDirs = [
            'smarty',
        ];
    
        foreach ($cacheDirs as $theDir) {
            $dir = PATH_CACHE . $theDir . '/';
            if ($dh = opendir($dir)) {
                while ($file = readdir($dh)) {
                    if ($file != '.' && $file != '..') {
                        // 清除
                        @unlink($dir . $file);
                    }
                }
            }
            
            echo "$theDir, done\n";
        }
    }
    
    public function htmlAction()
    {
        // 清掉html缓存
        $cacheDirs = [
            'html',
        ];
    
        foreach ($cacheDirs as $theDir) {
            $dir = PATH_CACHE . $theDir . '/';
            if ($dh = opendir($dir)) {
                while ($file = readdir($dh)) {
                    if ($file != '.' && $file != '..') {
                        // 清除
                        @unlink($dir . $file);
                    }
                }
            }
    
            echo "$theDir, done\n";
        }
    }

    /**
     * 更新分类的缓存
     *
     * @return void
     */
    public function categoryAction()
    {
        CategoryHelper::flushTree();
    }

    /**
     * 刷新opcache
     * 
     */
    public function opcacheAction()
    {
        static $done;
        if (!isset($done) || $done) {
            $done = 1;
            if (function_exists('opcache_reset')) {
                opcache_reset();
                echo 'opcache成功';
            } else {
                echo 'opcache不存在';
            }
        }
        
    }
    
    public function __destruct()
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
            echo 'opcache, done' . "\n";
        } 
    }
}

$controller = new ApiFlushCacheController();
SimpleDispatcher::dispatch($controller);