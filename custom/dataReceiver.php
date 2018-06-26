<?php
/**

 * NOTICE OF LICENSE

 *

 * This file is licenced under the Software License Agreement.

 * With the purchase or the installation of the software in your application

 * you accept the licence agreement.

 *

 * You must not modify, adapt or create derivative works of this source code

 *

 *  @author    Dervine N

 *  @copyright Sendy Limited

 *  @license   LICENSE.txt

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
$context->cookie->__set('to_details', json_encode($to_details));

$res = $sendyapimodule->getPriceQuote($to_name, $to_lat, $to_long);
echo $res;
