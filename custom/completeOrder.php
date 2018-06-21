<?php
/**
 * Created by PhpStorm.
 * User: dervine
 * Date: 5/29/18
 * Time: 2:27 PM
 */
include(dirname(__FILE__).'/../../../config/config.inc.php');
include(_PS_ROOT_DIR_.'/init.php');
require_once _PS_ROOT_DIR_ . '/override/controllers/front/OrderConfirmationController.php';
require_once _PS_ROOT_DIR_ . '/modules/sendyapimodule/custom/dataReceiver.php';

$order_no =
$amount =

$sendyapimodule = Module::getInstanceByName('sendyapimodule');
$res = $sendyapimodule->completeOrder($order_no, $amount);
echo $res;