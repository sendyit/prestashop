<?php
/**
 * Created by PhpStorm.
 * User: dervine
 * Date: 5/29/18
 * Time: 2:27 PM
 */
include(dirname(__FILE__).'/../../../config/config.inc.php');
include(_PS_ROOT_DIR_.'/init.php');
$sendyapimodule = Module::getInstanceByName('sendyapimodule');
$data = $_POST;
$shipping_cost = $data['shipping_cost'];
$res = $sendyapimodule->showPriceQuote($shipping_cost);
echo $res;