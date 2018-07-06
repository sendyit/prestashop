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

$sql = array();

/*
	create table that contains
	sendy_api_id,
	sendy_api_key,
	sendy_api_username
	from
	building
	floor
	other_details
*/
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'sendy_api` (
        `sendy_api_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `sendy_api_key` varchar(200) NOT NULL,
	    `sendy_api_username` varchar(200) NOT NULL,
	    `from` varchar(200) NOT NULL,
	    `building` varchar(200) DEFAULT NULL,
	    `floor` varchar(200) DEFAULT NULL,
	    `other_details` varchar(200) DEFAULT NULL,
        PRIMARY KEY  (`sendy_api_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}
