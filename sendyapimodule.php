<?php
/**
 * Sendy API Module
 *
 * @author    Griffin M
 * @copyright Sendy
 *
 */

if (!defined('_PS_VERSION_')) {
    exit;
}
class SendyApiModule extends CarrierModule
{

    /** @var array Use to store the configuration from database */
    public $config_values;

    /** @var array submit values of the configuration page */
    protected static $config_post_submit_values = array('saveConfig');

    public  $id_carrier;

    private $_html = '';
    private $_postErrors = array();
    private $_moduleName = 'sendyapimodule';

    public function __construct()
    {
        $this->name = 'sendyapimodule'; // internal identifier, unique and lowercase
        $this->tab = 'shipping_logistics'; // backend module coresponding category
        $this->version = '1.0.0'; // version number for the module
        $this->author = 'Sendy'; // module author
        $this->need_instance = 0; // load the module when displaying the "Modules" page in backend
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Sendy Api Module'); // public name
        $this->description = $this->l('Sendy Prestashop Module for the Sendy public API'); // public description

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?'); // confirmation message at uninstall
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        if (self::isInstalled($this->name))
        {
            // Getting carrier list
            global $cookie;
            $carriers = Carrier::getCarriers($cookie->id_lang, true, false, false, NULL, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);

            // Saving id carrier list
            $id_carrier_list = array();
            foreach($carriers as $carrier)
                $id_carrier_list[] .= $carrier['id_carrier'];

            // Testing if Carrier Id exists
            $warning = array();
            if (!in_array((int)(Configuration::get('MYCARRIER1_CARRIER_ID')), $id_carrier_list))
                $warning[] .= $this->l('"Carrier 1"').' ';
            if (!Configuration::get('MYCARRIER1_OVERCOST'))
                $warning[] .= $this->l('"Carrier 1 Overcost"').' ';
            if (count($warning))
                $this->warning .= implode(' , ',$warning).$this->l('must be configured to use this module correctly').' ';
        }
    }

    /**
     * Install this module
     * @return boolean
     */
    public function install()
    {
        #include dirname(__FILE__) . '/sql/install.php';

        $carrierConfig = array(
            0 => array('name' => 'Express',
                'id_tax_rules_group' => 0,
                'active' => true,
                'deleted' => 0,
                'shipping_handling' => false,
                'range_behavior' => 0,
                'delay' => array('mx' => 'Entrega rapida', 'en' => 'Description 1', Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) => 'Description 1'),
                'id_zone' => 1,
                'is_module' => true,
                'shipping_external' => true,
                'external_module_name' => 'mycarrier',
                'need_range' => true
            ),
        );
        $id_carrier1 = $this->installExternalCarrier($carrierConfig[0]);
        Configuration::updateValue('MYCARRIER1_CARRIER_ID', (int)$id_carrier1);
        if (!parent::install() ||
            !$this->initConfig() ||
            !$this->registerHook('actionAdminControllerSetMedia') ||
            !$this->registerHook('actionFrontControllerSetMedia') ||
            !$this->registerHook('displayHome') ||
            !Configuration::updateValue('MYCARRIER1_OVERCOST', '') ||
            !$this->registerHook('updateCarrier'))
            return false;
        return true;
//        return parent::install() &&
//            $this->initConfig() &&
//            $this->registerHook('actionAdminControllerSetMedia') &&
//            $this->registerHook('actionFrontControllerSetMedia') &&
//            $this->registerHook('displayHome');
//

    }

    /**
     * Uninstall this module
     * @return boolean
     */
    public function uninstall()
    {
        include dirname(__FILE__) . '/sql/uninstall.php';

//            parent::uninstall();

        if (!parent::uninstall() ||
            !Configuration::deleteByName($this->name) ||
            !Configuration::deleteByName('MYCARRIER1_OVERCOST') ||
            !$this->unregisterHook('updateCarrier'))
            return false;

        // Delete External Carrier
        $Carrier1 = new Carrier((int)(Configuration::get('MYCARRIER1_CARRIER_ID')));
        // If external carrier is default set other one as default
        if (Configuration::get('PS_CARRIER_DEFAULT') == (int)($Carrier1->id))
        {
            global $cookie;
            $carriersD = Carrier::getCarriers($cookie->id_lang, true, false, false, NULL, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
            foreach($carriersD as $carrierD)
                if ($carrierD['active'] AND !$carrierD['deleted'] AND ($carrierD['name'] != $this->_config['name']))
                    Configuration::updateValue('PS_CARRIER_DEFAULT', $carrierD['id_carrier']);
        }

        // Then delete Carrier
        $Carrier1->deleted = 1;
        if (!$Carrier1->update())
            return false;

        return true;
    }

    public static function installExternalCarrier($config)
    {
        $carrier = new Carrier();
        $carrier->name = $config['name'];
        $carrier->id_tax_rules_group = $config['id_tax_rules_group'];
        $carrier->id_zone = $config['id_zone'];
        $carrier->active = $config['active'];
        $carrier->deleted = $config['deleted'];
        $carrier->delay = $config['delay'];
        $carrier->shipping_handling = $config['shipping_handling'];
        $carrier->range_behavior = $config['range_behavior'];
        $carrier->is_module = $config['is_module'];
        $carrier->shipping_external = $config['shipping_external'];
        $carrier->external_module_name = $config['external_module_name'];
        $carrier->need_range = $config['need_range'];

        $languages = Language::getLanguages(true);
        foreach ($languages as $language)
        {
            if ($language['iso_code'] == 'fr')
                $carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
            if ($language['iso_code'] == 'en')
                $carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
            if ($language['iso_code'] == Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')))
                $carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
        }

        if ($carrier->add())
        {
            $groups = Group::getGroups(true);
            foreach ($groups as $group)
                Db::getInstance()->autoExecute(_DB_PREFIX_.'carrier_group', array('id_carrier' => (int)($carrier->id), 'id_group' => (int)($group['id_group'])), 'INSERT');

            $rangePrice = new RangePrice();
            $rangePrice->id_carrier = $carrier->id;
            $rangePrice->delimiter1 = '0';
            $rangePrice->delimiter2 = '10000';
            $rangePrice->add();

            $rangeWeight = new RangeWeight();
            $rangeWeight->id_carrier = $carrier->id;
            $rangeWeight->delimiter1 = '0';
            $rangeWeight->delimiter2 = '10000';
            $rangeWeight->add();

            $zones = Zone::getZones(true);
            foreach ($zones as $zone)
            {
                Db::getInstance()->autoExecute(_DB_PREFIX_.'carrier_zone', array('id_carrier' => (int)($carrier->id), 'id_zone' => (int)($zone['id_zone'])), 'INSERT');
                Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_.'delivery', array('id_carrier' => (int)($carrier->id), 'id_range_price' => (int)($rangePrice->id), 'id_range_weight' => NULL, 'id_zone' => (int)($zone['id_zone']), 'price' => '0'), 'INSERT');
                Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_.'delivery', array('id_carrier' => (int)($carrier->id), 'id_range_price' => NULL, 'id_range_weight' => (int)($rangeWeight->id), 'id_zone' => (int)($zone['id_zone']), 'price' => '0'), 'INSERT');
            }

            // Copy Logo
            if (!copy(dirname(__FILE__).'/carrier.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg'))
                return false;

            // Return ID Carrier
            return (int)($carrier->id);
        }

        return false;
    }


    /**
     * Add the CSS & JavaScript files you want to be loaded in BO.
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        $this->context->controller->addJS($this->getPathUri() . 'views/js/custom.js');
        $this->context->controller->addCSS($this->getPathUri() . 'views/css/custom.css');
        $this->context->controller->addJS($this->getPathUri() . 'views/js/google_map.js');

    }

    /**
     * Set the default configuration
     * @return boolean
     */
    protected function initConfig()
    {
        // config should be the one saved on the sendy_api table
        $this->config_values = array(
            'sendy_api_key' => 'mysendykey',
            'sendy_api_username' => 'mysendyusername',
            'api_enviroment' => 'sandbox',
            'api_from' => '', #get current location here
            'api_lat' =>'',
            'api_long' => '',
            'api_building' => '',  #try to prefill with location
            'api_floor' => '', #leave blank
            'other_details' => '' #other details
        );

        return $this->setConfigValues($this->config_values);
    }

    /**
     * Configuration page
     */

    public function getContent()
    {

        $this->config_values = $this->getConfigValues();

        $this->context->smarty->assign(array(
            'module' => array(
                'class' => get_class($this),
                'name' => $this->name,
                'displayName' => $this->displayName,
                'dir' => $this->_path
            )
        ));

        return $this->postProcess();
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $output = '';

        switch ($this->getPostSubmitValue()) {
            /* save module configuration */
            case 'saveConfig':

                $this->config_values = array(
                    'sendy_api_key' => 'mysendykey',
                    'sendy_api_username' => 'mysendyusername',
                    'api_enviroment' => 'sandbox',
                    'api_from' => '', #get current location here
                    'api_lat'=> '',
                    'api_long' => '',
                    'api_building' => '',  #try to prefill with location
                    'api_floor' => '', #leave blank
                    'other_details' => '' #other details
                );
                $config_keys = array_keys($this->config_values);

                foreach ($config_keys as $key) {
                    $this->config_values[$key] = Tools::getValue($key, $this->config_values[$key]);
                }

                $api_key = $this->config_values['sendy_api_key'];
                $api_username = $this->config_values['sendy_api_username'];
                $api_env = $this->config_values['api_enviroment'];


                $res = $this->connectSendyApi($api_key, $api_username, $api_env);
                $res = json_decode($res, true);
                if ($res["status"]) {
                    //$output .= $this->displayConfirmation($this->l(json_encode($res)));
                    if ($this->setConfigValues($this->config_values)) {
                        $output .= $this->displayConfirmation($this->l('Settings updated'));
                        $output .= $this->displayConfirmation($this->l(json_encode($this->getConfigValues())));

                    }
                } else {
                    $output .= $this->displayError($this->l($res['description']));
                }
//
//                $quote = $this->getPriceQuote($api_key, $api_username, $api_env);
//                $quote = json_decode($quote, true);
//                if ($quote["status"]) {
//                    $output .= $this->displayConfirmation($this->l(json_encode($quote)));
//                } else {
//                    $output .= $this->displayError($this->l($quote['description']));
//                }

            // it continues to default

            default:
                $output .= $this->renderForm();
                break;
        }

        return $output;

    }

    /**
     * Create the structure ob_flush() your form.
     */
    protected function getConfigForm()
    {
        $options = array(
            array(
                'id_option' => 'sandbox',
                'name' => 'SandBox'
            ),
            array(
                'id_option' => 'live',
                'name' => 'Live'
            ),
        );
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->displayName,
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'label' => $this->l('Sendy API Key'),
                        'name' => 'sendy_api_key',
                        'type' => 'text',
                        'class' => 'fixed-width-lg',
                        'required' => true
                    ),
                    array(
                        'label' => $this->l('Sendy API Username'),
                        'name' => 'sendy_api_username',
                        'type' => 'text',
                        'class' => 'fixed-width-lg',
                        'required' => true
                    ),
                    array(
                        'label' => $this->l('Enviroment'),
                        'name' => 'api_enviroment',
                        'type' => 'select',
                        'required' => true,
                        'options' => array(
                            'query' => $options,
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'label' => $this->l('From'),
                        'name' => 'api_from',
                        'type' => 'text',
                        'required' => true
                    ),
                    array(
                        'label' => $this->l('Lat'),
                        'name' => 'api_lat',
                        'type' => 'text',
                        'required' => true
                    ),
                    array(
                        'label' => $this->l('Long'),
                        'name' => 'api_long',
                        'type' => 'text',
                        'required' => true
                    ),
                    array(
                        'label' => $this->l('Building'),
                        'name' => 'api_building',
                        'type' => 'text',
                        'class' => 'fixed-width-lg',
                    ),
                    array(
                        'label' => $this->l('Floor'),
                        'name' => 'api_floor',
                        'type' => 'text',
                        'class' => 'fixed-width-lg',
                    ),
                    array(
                        'label' => $this->l('Other Details'),
                        'name' => 'other_details',
                        'type' => 'textarea',
                    )
                ),
                'submit' => array(
                    'name' => 'saveConfig',
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-success pull-right'
                )
            )
        );
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->name;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name . '&module_name=' . $this->name . '&tab_module=' . $this->tab;

        $helper->tpl_vars = array(
            'fields_value' => $this->config_values, /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }


    /**
     * Get configuration array from database
     * @return array
     */
    public function getConfigValues()
    {
        return json_decode(Configuration::get($this->name), true);
    }

    public function connectSendyApi($api_key, $api_username, $env = 'sandbox')
    {
        $request = '{
                      "command": "rider_location",
                      "data": {
                        "api_key": "' . $api_key . '",
                        "api_username": "' . $api_username . '",
                        "lat": -1.28869,
                        "long": 36.823363
                      },
                      "request_token_id": "request_token_id"
                    }';

        if ($env == 'sandbox') {
            $url = 'https://apitest.sendyit.com/v1/';
        } else {
            $url = 'https://api.sendyit.com/v1/';
        }
        $ch = curl_init($url);
        # Setup request to send json via POST.
        $payload = json_encode(json_decode($request, true));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        # Return response instead of printing.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        # Send request.
        $result = curl_exec($ch);
        curl_close($ch);
        # Print response.
        return $result;
    }

    /**
     * perform a price request
     * set 'from' as store's location
     * 'to' to be set by customer during checkout
     * return a price quote
     */
    public function getPriceQuote($api_to, $to_lat, $to_long)
    {   $this->config_values = $this->getConfigValues();
        $api_key = $this->config_values['sendy_api_key'];
        $api_username = $this->config_values['sendy_api_username'];
        $env = $this->config_values['api_enviroment'];
        $request = '{
                      "command": "request",
                      "data": {
                        "api_key": "' . $api_key . '",
                        "api_username": "' . $api_username . '",
                        "vendor_type": 1,
                        "from": {
                          "from_name": "'.$this->config_values['api_from'].'",
                          "from_lat": "'.$this->config_values['api_lat'].'",
                          "from_long":"'.$this->config_values['api_long'].'",
                          "from_description": ""
                        },
                        "to": {
                          "to_name": "'.$api_to.'",
                          "to_lat": "'.$to_lat.'",
                          "to_long": "'.$to_long.'",
                          "to_description": ""
                        },
                        "recepient": {
                          "recepient_name": "Sendy User",
                          "recepient_phone": "0728561783",
                          "recepient_email": "support@sendy.co.ke"
                        },
                        "delivery_details": {
                          "pick_up_date": "2016-04-20 12:12:12",
                          "collect_payment": {
                            "status": false,
                            "pay_method": 0,
                            "amount": 10
                          },
                          "return": true,
                          "note": " Sample note",
                          "note_status": true,
                          "request_type": "delivery",
                          "order_type": "ondemand_delivery",
                          "ecommerce_order": "ecommerce_order_001",
                          "skew": 1,
                          "package_size": [
                            {
                              "weight": 20,
                              "height": 10,
                              "width": 200,
                              "length": 30,
                              "item_name": "laptop"
                            }
                          ]
                        }
                      },
                      "request_token_id": "request_token_id"
                    }';
        if ($env == 'sandbox') {
            $url = 'https://apitest.sendyit.com/v1/#request';
        } else {
            $url = 'https://api.sendyit.com/v1/#request';
        }
        $ch = curl_init($url);
        # Setup request to send json via POST.
        $payload = json_encode(json_decode($request, true));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        # Return response instead of printing.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        # Send request.
        $result = curl_exec($ch);
        curl_close($ch);
        # Print response.
        return $result;

    }

    /**
     * Set configuration array to database
     * @param array $config
     * @param bool $merge when true, $config can be only a subset to modify or add additional fields
     * @return array
     */

    public function setConfigValues($config, $merge = false)
    {
        if ($merge) {
            $config = array_merge($this->getConfigValues(), $config);
        }

        if (Configuration::updateValue($this->name, json_encode($config))) {
            return $config;
        }


        return false;
    }

    public function updateFromConfig($api_from, $api_lat, $api_long){
        $config = $this->getConfigValues();
        $config['api_from'] = $api_from;
        $config['api_lat'] = $api_lat;
        $config['api_long'] = $api_long;
        $this->setConfigValues($config);
    }

    /**
     * Get the action submitted from the configuration page
     * @return string
     */
    protected function getPostSubmitValue()
    {
        foreach (self::$config_post_submit_values as $value) {
            if (Tools::isSubmit($value)) {
                return $value;
            }
        }

        return false;
    }

    /**
     * Determines if on the module configuration page
     * @return bool
     */
    public function isConfigPage()
    {
        return self::isAdminPage('modules') && Tools::getValue('configure') === $this->name;
    }

    /**
     * Determines if on the specified admin page
     * @param string $page
     * @return bool
     */
    public static function isAdminPage($page)
    {
        return Tools::getValue('controller') === 'Admin' . ucfirst($page);
    }


    public function hookDisplayBackOfficeHeader($params)
    {
        $this->hookBackOfficeHeader($params);
    }

    public function hookBackOfficeHeader($params)
    {
        $this->context->controller->addJS($this->getPathUri() . 'views/js/custom.js', 'all');
        $this->context->controller->addCSS($this->getPathUri() . 'views/js/custom.css', 'all');
        $this->context->controller->addJS($this->getPathUri() . 'views/js/google_map.js');

    }

    /**
     * Add the CSS & JavaScript files on FO.
     */
    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
        $this->context->controller->addJS($this->getPathUri() . 'views/js/google_map.js');

    }

    /**
     * cartPage content hook (Technical name: displayHome)
     */
    public function hookDisplayShoppingCart($params)
    {
        !isset($params['tpl']) && $params['tpl'] = 'displayHome';

        $this->config_values = $this->getConfigValues();
        $this->smarty->assign($this->config_values);

        return $this->display(__FILE__, $params['tpl'] . '.tpl');
    }
    /*
   ** Form Config Methods
   **
   */

    public function getContent1()
    {
        $this->_html .= '<h2>' . $this->l('Sendy Api Module').'</h2>';
        if (!empty($_POST) AND Tools::isSubmit('submitSave'))
        {
            $this->_postValidation();
            if (!sizeof($this->_postErrors))
                $this->_postProcess();
            else
                foreach ($this->_postErrors AS $err)
                    $this->_html .= '<div class="alert error"><img src="'._PS_IMG_.'admin/forbbiden.gif" alt="nok" /> '.$err.'</div>';
        }
        $this->_displayForm();
        return $this->_html;
    }

    private function _displayForm()
    {
        $this->_html .= '<fieldset>
        <legend><img src="'.$this->_path.'logo.gif" alt="" /> '.$this->l('My Carrier Module Status').'</legend>';

        $alert = array();
        if (!Configuration::get('MYCARRIER1_OVERCOST') || Configuration::get('MYCARRIER1_OVERCOST') == '')
            $alert['carrier1'] = 1;

        if (!count($alert))
            $this->_html .= '<img src="'._PS_IMG_.'admin/module_install.png" /><strong>'.$this->l('My Carrier is configured and online!').'</strong>';
        else
        {
            $this->_html .= '<img src="'._PS_IMG_.'admin/warn2.png" /><strong>'.$this->l('My Carrier is not configured yet, please:').'</strong>';
            $this->_html .= '<br />'.(isset($alert['carrier1']) ? '<img src="'._PS_IMG_.'admin/warn2.png" />' : '<img src="'._PS_IMG_.'admin/module_install.png" />').' 1) '.$this->l('Configure the carrier 1 overcost');

        }

        $this->_html .= '</fieldset><div class="clear"> </div>
            <style>
                #tabList { clear: left; }
                .tabItem { display: block; background: #FFFFF0; border: 1px solid #CCCCCC; padding: 10px; padding-top: 20px; }
            </style>
            <div id="tabList">
                <div class="tabItem">
                    <form action="index.php?tab='.Tools::getValue('tab').'&configure='.Tools::getValue('configure').'&token='.Tools::getValue('token').'&tab_module='.Tools::getValue('tab_module').'&module_name='.Tools::getValue('module_name').'&id_tab=1&section=general" method="post" class="form" id="configForm">

                    <fieldset style="border: 0px;">
                        <h4>'.$this->l('General configuration').' :</h4>
                        <label>'.$this->l('My Carrier1 overcost').' : </label>
                        <div class="margin-form"><input type="text" size="20" name="mycarrier1_overcost" value="'.Tools::getValue('mycarrier1_overcost', Configuration::get('MYCARRIER1_OVERCOST')).'" /></div>
                    </div>
                    <br /><br />
                </fieldset>                
                <div class="margin-form"><input class="button" name="submitSave" type="submit"></div>
            </form>
        </div></div>';
    }
    private function _postValidation()
    {
        // Check configuration values
        if (Tools::getValue('mycarrier1_overcost') == '')
            $this->_postErrors[]  = $this->l('You have to configure at least one carrier');
    }

    private function _postProcess()
    {
        // Saving new configurations
        if (Configuration::updateValue('MYCARRIER1_OVERCOST', Tools::getValue('mycarrier1_overcost')))
            $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
        else
            $this->_html .= $this->displayErrors($this->l('Settings failed'));
    }
    /*
    ** Hook update carrier
    **
    */

    public function hookupdateCarrier($params)
    {
        if ((int)($params['id_carrier']) == (int)(Configuration::get('MYCARRIER1_CARRIER_ID')))
            Configuration::updateValue('MYCARRIER1_CARRIER_ID', (int)($params['carrier']->id));
    }
    /*
    ** Front Methods
    **
    ** If you set need_range at true when you created your carrier (in install method), the method called by the cart will be getOrderShippingCost
    ** If not, the method called will be getOrderShippingCostExternal
    **
    ** $params var contains the cart, the customer, the address
    ** $shipping_cost var contains the price calculated by the range in carrier tab
    **
    */

    public function getOrderShippingCost($params, $shipping_cost)
    {
        // This example returns shipping cost with overcost set in the back-office, but you can call a webservice or calculate what you want before returning the final value to the Cart
        if ($this->id_carrier == (int)(Configuration::get('MYCARRIER1_CARRIER_ID')) && Configuration::get('MYCARRIER1_OVERCOST') > 1)
            return (float)(Configuration::get('MYCARRIER1_OVERCOST'));

        // If the carrier is not known, you can return false, the carrier won't appear in the order process
        return false;
    }

    public function getOrderShippingCostExternal($params)
    {
        // This example returns the overcost directly, but you can call a webservice or calculate what you want before returning the final value to the Cart
        if ($this->id_carrier == (int)(Configuration::get('MYCARRIER1_CARRIER_ID')) && Configuration::get('MYCARRIER1_OVERCOST') > 1)
            return (float)(Configuration::get('MYCARRIER1_OVERCOST'));

        // If the carrier is not known, you can return false, the carrier won't appear in the order process
        return false;
    }



}

?>