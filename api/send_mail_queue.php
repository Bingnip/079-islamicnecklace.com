<?php
/**
 * 发送邮件队列的API
 *
 * @since 2012-11-04
 *
 */

define('DEBUG', TRUE);
require dirname(__FILE__) . '/common.inc.php';

$id = array_key_exists('id', $_REQUEST) ? intval($_REQUEST['id']) : 0;
$limit = array_key_exists('limit', $_REQUEST) ? intval($_REQUEST['limit']) : 0;
if ($limit <= 0) {
    $limit = 50;
}

if ($limit > 200) {
    $limit = 200;
}

$json = JsonResponse::instance();

$ret = EmailLogHelper::send($id ? $id : NULL, $limit);

$json->setResult($ret);
$response = $json->getResponseString();

echo CRON_PROG_SUCCESS . ':::' . $response;