<?php
/**
 *  Sendy Api Module
 *
 *  @author    Griffin M
 *  @copyright Sendy
 */

/*
	drop table sendy_api on unistallation
*/

$sql = array();
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'sendy_api`;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) return false;
}
