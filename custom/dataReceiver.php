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
$to_name = $data['to_name'];
$to_lat = $data['to_lat'];
$to_long = $data['to_long'];

#store to details in a cookie
$to_details = array(
	"to_name" => $to_name,
	"to_lat" => $to_lat,
	"to_long" => $to_long 
);

$context = Context::getContext();
$context->cookie->__set('to_details',json_encode($to_details));

$res = $sendyapimodule->getPriceQuote($to_name,$to_lat,$to_long);
echo $res;