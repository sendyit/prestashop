<?php
/**
 * Created by PhpStorm.
 * User: dervine
 * Date: 5/29/18
 * Time: 2:27 PM
 */
include(dirname(__FILE__).'/../../../config/config.inc.php');
include(_PS_ROOT_DIR_.'/init.php');
$myCart = Module::getInstanceByName('myCart');
$data = $_POST;
$shipping_cost = $data['shipping_cost'];
#echo $shipping_cost;
//$params = array("shipping_cost" => $shipping_cost);
$myCart->getPackageShippingCost($shipping_cost);
//$res = $sendyapimodule->getShipping($shipping_cost);
//echo $res;