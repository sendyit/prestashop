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
$context = Context::getContext();
$response = array(
	// TO DO: Allow tracking multiple orders
	"tracking_url" => $context->cookie->tracking
);
echo json_encode($response);
?>



