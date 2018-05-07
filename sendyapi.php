<?php
if (!defined('_PS_VERSION_'))
{
  exit;
}

/**
*  the sendyapi module main class
*  Author : Griffin M
*/
class SendyApi extends Module
{
	public function __construct()
	  {
	    $this->name = 'SendyApi';
	    $this->tab = 'front_office_features';
	    $this->version = '1.0.0';
	    $this->author = 'Sendy';
	    $this->need_instance = 0;
	    $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	    $this->bootstrap = true;

	    parent::__construct();

	    $this->displayName = $this->l('Sendy Api');
	    $this->description = $this->l('Sendy Public Api Module');

	    $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

	    if (!Configuration::get('MYMODULE_NAME'))
	      $this->warning = $this->l('No name provided');
	  }
	
}