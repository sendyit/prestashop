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
require_once _PS_ROOT_DIR_ . '/override/classes/Cart.php';

$sendyapicart = new Cart();

$data = $_POST;
$shipping_cost = $data['shipping_cost'];
$id_carrier = null;
$use_tax = true;
$default_country = null;
$product_list = null;
$id_zone = null;

$sendyapicart->setShippingCost($shipping_cost);

echo $sendyapicart->getPackageShippingCost($id_carrier, $use_tax, $default_country, $product_list, $id_zone, $shipping_cost);
