<?php
/**
 * Created by PhpStorm.
 * User: dervine
 * Date: 5/29/18
 * Time: 2:27 PM
 */
include(dirname(__FILE__).'/../../../config/config.inc.php');
include(_PS_ROOT_DIR_.'/init.php');
include_once(__PATH__TO__CLASS__FILE__.'/sendyapimodule.php');
$data = $_POST;
$to_name = $data['to_name'];
$to_lat = $data['to_lat'];
$to_long = $data['to_long'];

//echo $to_name;

//include_once('../sendyapimodule.php');

$sendy_module  = Module::getInstanceByName('sendyapimodule');
//$res = $sendy_module->getPriceQuote($to_name, $to_lat, $to_long);

//echo json_encode($res);

