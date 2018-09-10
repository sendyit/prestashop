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
 * @author    Dervine N
 * @copyright Sendy Limited
 * @license   LICENSE.txt
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SendyApiModule extends CarrierModule
{
    const PREFIX = 'ps_';

    /** @var array Use to store the configuration from database */
    public $config_values;
    /** @var array submit values of the configuration page */
    protected static $config_post_submit_values = array('saveConfig');
    protected $_hooks = array(
        'actionCarrierUpdate', //For control change of the carrier's ID (id_carrier), the module must use the updateCarrier hook.
        'actionAdminControllerSetMedia',
        'actionFrontControllerSetMedia',
        'displayShoppingCart',
        'backOfficeHeader',
        'displayBackOfficeHeader',
        'displayConfirmation'
    );
    protected $_carriers = array();

    public function __construct()
    {
        $this->name = 'sendyapimodule'; // internal identifier, unique and lowercase
        $this->tab = 'shipping_logistics'; // backend module corresponding category
        $this->version = '1.0.0'; // version number for the module
        $this->author = 'Sendy'; // module author
        $this->module_key = '1fe8081ab6f83eea15bfd7c2a0a14741';
        $this->need_instance = 0; // load the module when displaying the "Modules" page in backend
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Sendy Ecommerce'); // public name
        $this->description = $this->l('This module integrates Sendy delivery service to the customers shopping flow.'); // public description
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?'); // confirmation message at uninstall
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->initCarriers();
    }

    private function initCarriers()
    {
        $this->_carriers['Sendy'] = 'sendy';
    }

    /**
     * Install this module
     * @return boolean
     */
    public function install()
    {
        if (parent::install()) {
            foreach ($this->_hooks as $hook) {
                if (!$this->registerHook($hook)) {
                    return false;
                }
            }

            if (!$this->createCarriers()) { //function for creating new carrier
                return false;
            }

            return true;
        }

        return false;
    }

    protected function createCarriers()
    {
        foreach ($this->_carriers as $key => $value) {
            //Create new carrier
            $carrier = new Carrier();
            $carrier->name = $this->l($key);
            $carrier->active = true;
            $carrier->deleted = 0;
            $carrier->shipping_handling = false;
            $carrier->range_behavior = 0;
            $carrier->delay[Configuration::get('PS_LANG_DEFAULT')] = 'Instant delivery to your doorstep.';
            $carrier->shipping_external = true;
            $carrier->is_module = true;
            $carrier->external_module_name = $this->name;
            $carrier->need_range = true;

            if ($carrier->add()) {
                $groups = Group::getGroups(true);
                foreach ($groups as $group) {
                    Db::getInstance()->autoExecute(_DB_PREFIX_ . 'carrier_group', array(
                        'id_carrier' => (int)$carrier->id,
                        'id_group' => (int)$group['id_group']
                    ), 'INSERT');
                }

                $rangePrice = new RangePrice();
                $rangePrice->id_carrier = $carrier->id;
                $rangePrice->delimiter1 = '0';
                $rangePrice->delimiter2 = '1000000';
                $rangePrice->add();

                $rangeWeight = new RangeWeight();
                $rangeWeight->id_carrier = $carrier->id;
                $rangeWeight->delimiter1 = '0';
                $rangeWeight->delimiter2 = '1000000';
                $rangeWeight->add();

                $zones = Zone::getZones(true);
                foreach ($zones as $z) {
                    Db::getInstance()->autoExecute(
                        _DB_PREFIX_ . 'carrier_zone',
                        array('id_carrier' => (int)$carrier->id,
                            'id_zone' => (int)$z['id_zone']),
                        'INSERT'
                    );
                    Db::getInstance()->autoExecuteWithNullValues(
                        _DB_PREFIX_ . 'delivery',
                        array('id_carrier' => $carrier->id,
                            'id_range_price' => (int)$rangePrice->id,
                            'id_range_weight' => null,
                            'id_zone' => (int)$z['id_zone'],
                            'price' => '0'),
                        'INSERT'
                    );
                    Db::getInstance()->autoExecuteWithNullValues(
                        _DB_PREFIX_ . 'delivery',
                        array('id_carrier' => $carrier->id,
                            'id_range_price' => null,
                            'id_range_weight' => (int)$rangeWeight->id,
                            'id_zone' => (int)$z['id_zone'],
                            'price' => '0'),
                        'INSERT'
                    );
                }

                copy(dirname(__FILE__) . '/logo.png' . $value . '.jpg', _PS_SHIP_IMG_DIR_ . '/' . (int)$carrier->id . '.jpg'); //assign carrier logo


                Configuration::updateValue(self::PREFIX . $value, $carrier->id);
                Configuration::updateValue(self::PREFIX . $value . '_reference', $carrier->id);
            }
        }

        return true;
    }

    protected function deleteCarriers()
    {
        foreach ($this->_carriers as $value) {
            $tmp_carrier_id = Configuration::get(self::PREFIX . $value);
            $carrier = new Carrier($tmp_carrier_id);
            $carrier->delete();
        }

        return true;
    }

    /**
     * Uninstall this module
     * @return boolean
     */
    public function uninstall()
    {
        if (parent::uninstall()) {
            foreach ($this->_hooks as $hook) {
                if (!$this->unregisterHook($hook)) {
                    return false;
                }
            }

            if (!$this->deleteCarriers()) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in BO.
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        $this->context->controller->addJQueryUi('ui.timepicker');
        $this->context->controller->addCSS($this->getPathUri() . 'views/css/custom.css');
        $this->context->controller->addJS($this->getPathUri() . 'views/js/custom.js');
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
            'sendy_api_key' => 'cdy1uf762o573xw78sf5se1y9ettx0',
            'sendy_api_username' => 'prestauser',
            'api_enviroment' => 'sandbox',
            'api_from' => 'MarsaBit Plaza, Ngong Road, Nairobi, Kenya', #get current location here
            'api_lat' => '-1.299897',
            'api_long' => '36.77305249999995',
            'api_building' => 'Marsabit Plaza',  #try to prefill with location
            'api_floor' => '3', #leave blank
            'api_delivery' => '',
            'other_details' => 'room 307' #other details
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
                    'sendy_api_key' => 'cdy1uf762o573xw78sf5se1y9ettx0',
                    'sendy_api_username' => 'prestauser',
                    'api_enviroment' => 'sandbox',
                    'api_from' => 'MarsaBit Plaza, Ngong Road, Nairobi, Kenya', #get current location here
                    'api_lat' => '-1.299897',
                    'api_long' => '36.77305249999995',
                    'api_building' => 'Marsabit Plaza',  #try to prefill with location
                    'api_floor' => '3', #leave blank
                    'api_delivery' => '',
                    'other_details' => 'room 307' #other details
                );
                $this->config_values['[api_delivery]'] = explode(',',$obj->api_delivery);
                $_POST['api_delivery'] = implode(',', Tools::getValue('api_delivery'));
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
                    if ($this->setConfigValues($this->config_values)) {
                        $output .= $this->displayConfirmation($this->l('Congratulations! You completed this step. Go to \'Shipping -> Carriers on the left side menu to continue the setup.'));
                        //$output .= $this->displayConfirmation($this->l(json_encode($this->getConfigValues())));
                    }
                } else {
                    $output .= $this->displayError($this->l($res['description']));
                }
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
                'name' => 'Testing'
            ),
            array(
                'id_option' => 'live',
                'name' => 'Live'
            ),
        );
        $delivery = array(
            array(
                'check_id' => 'six',
                'name' => '6.00 AM - 8.00 AM'
            ),
            array(
                'check_id' => 'eight',
                'name' => '8.00 AM - 10.00 AM'
            ),
            array(
                'check_id' => 'ten',
                'name' => '10.00 AM - 12.00 PM'
            ),
            array(
                'check_id' => 'noon',
                'name' => '12.00 PM - 2.00 PM'
            ),
            array(
                'check_id' => 'two',
                'name' => '2.00 PM - 4.00 PM'
            ),
            array(
                'check_id' => 'four',
                'name' => '4.00 PM - 6.00 PM'
            ),
            array(
                'check_id' => 'late',
                'name' => '6.00 PM - 8.00 PM'
            ),
        );
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->displayName,
                    'icon' => 'icon-cogs'
                ),
                'desc' => '<b>Below you can set up the credentials for your store. You only need to do it once!</b><p></p><p></p>
                 &rarr;  To set it up on your test environment <b>(Testing)</b>; use <b>\'mysendykey\'</b> as your Sendy Api Key and <b>\'mysendyusername\' </b>as your Sendy Api Username.
                 <p></p><p>&rarr;  For production environment <b>(Live)</b>, set up your Sendy Api Key and Sendy Api Username by 
                 logging in into your <a href="https://app.sendyit.com/biz/auth/login">Sendy Account</a>, <p></p>&nbsp;&nbsp;&nbsp;&nbsp;Click on Menu &rarr; Admin Settings &rarr; Generate API Key and Username; then follow the procedure. 
                 </p><p></p>&rarr;  You need to log in as the Admin for you to access the Admin Settings panel.',
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
                        'label' => $this->l('Environment'),
                        'name' => 'api_enviroment',
                        'type' => 'select',
                        'required' => true,
                        'class' => 'fixed-width-lg',
                        'options' => array(
                            'query' => $options,
                            'id' => 'id_option',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'label' => $this->l('Your Shop Location'),
                        'name' => 'api_from',
                        'desc' => 'Please choose from Google Maps list.',
                        'type' => 'text',
                        'class' => 'fixed-width-lg',
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
                        'label' => $this->l('Delivery Hours'),
                        'name' => 'api_delivery',
                        'type' => 'select',
                        'multiple' => 'true',
                        'required' => true,
                        'options' => array(
                            'query' => $delivery,
                            'id' => 'check_id',
                            'name' => 'name',
                            'selected' =>'selected',
                        )
                    ),
                    array(
                        'label' => $this->l('Other Details'),
                        'name' => 'other_details',
                        'type' => 'textarea',
                        'class' => 'fixed-width-lg',
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
            'fields_value' => $this->config_values,
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
    public function getPriceQuote($api_to, $to_lat, $to_long, $recepient_name = "Sendy User", $recepient_phone = "0716163362", $recepient_email = "ndervine@sendy.co.ke")
    {
        $this->config_values = $this->getConfigValues();
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
                          "from_name": "' . $this->config_values['api_from'] . '",
                          "from_lat": "' . $this->config_values['api_lat'] . '",
                          "from_long":"' . $this->config_values['api_long'] . '",
                          "from_description": ""
                        },
                        "to": {
                          "to_name": "' . $api_to . '",
                          "to_lat": "' . $to_lat . '",
                          "to_long": "' . $to_long . '",
                          "to_description": ""
                        },
                        "recepient": {
                          "recepient_name": "' . $recepient_name . '",
                          "recepient_phone": "' . $recepient_phone . '",
                          "recepient_email": "' . $recepient_email . '"
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
                          "request_type": "quote",
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
        $context = Context::getContext();
        $context->cookie->__set('price_request_data', json_encode($result));
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

    public function updateFromConfig($api_from, $api_lat, $api_long)
    {
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
        return Tools::getValue('controller') === 'Admin' . Tools::ucfirst($page);
    }

    public function hookDisplayBackOfficeHeader($params)
    {
        $this->hookBackOfficeHeader($params);
    }

    public function hookBackOfficeHeader($params)
    {
        $this->context->controller->addJQueryUi('ui.datetimepicker');
        $this->context->controller->addJS($this->getPathUri() . 'views/js/custom.js', 'all');
        $this->context->controller->addJS($this->getPathUri() . 'views/js/cookie.js', 'all');
        $this->context->controller->addJS($this->getPathUri() . 'views/js/google_map.js', 'all');
        $this->context->controller->addCSS($this->getPathUri() . 'views/js/custom.css', 'all');
    }

    /**
     * Add the CSS & JavaScript files on FO.
     */
    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->addJQueryUi('ui.datetimepicker');
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addJS($this->_path . '/views/js/cookie.js');
        $this->context->controller->addJS($this->_path . '/views/js/google_map.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
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

    public function getOrderShippingCost($params, $shipping_cost)
    {
        return $shipping_cost;
    }

    public function getOrderShippingCostExternal($params)
    {
        return $this->getOrderShippingCost($params, 0);
    }

    public function hookActionCarrierUpdate($params)
    {
        if ($params['carrier']->id_reference == Configuration::get(self::PREFIX . 'sendy_reference')) {
            Configuration::updateValue(self::PREFIX . 'sendy', $params['carrier']->id);
        }
    }

    public function completeOrder($notes = 'Sample Order Note')
    {
        $day = $_COOKIE['pickupDay'];
        $time = $_COOKIE['pickupTime'];
        switch($day) {
            case 'today':
                $day = date("Y-m-d");
                break;
            case 'kesho':
                $day = date("Y-m-d", time() + 86400);
                break;
            default:
                $day = " 11:00:00";
        }
        switch($time) {
            case 'morning':
                $time = " 11:00:00";
                break;
            case 'lunch':
                $time = " 13:00:00";
                break;
            case 'evening':
                $time = " 15:00:00";
                break;
            case 'late':
                $time = " 17:00:00";
                break;
            default:
                $time = " 11:00:00";
        }
        $date = $day . $time;
        $pick_up_date = date('Y-m-d H:i:s', strtotime($date));
        $context = Context::getContext();
        $price_request_data = $context->cookie->price_request_data;
        $price_request_data = json_decode(json_decode($price_request_data), true);
        //return json_encode($price_request_data);
        $order_no = $price_request_data['data']['order_no'];
        $amount = $price_request_data['data']['amount'];
        $this->config_values = $this->getConfigValues();
        //return json_encode($this->config_values);
        $api_key = $this->config_values['sendy_api_key'];
        $api_username = $this->config_values['sendy_api_username'];
        $env = $this->config_values['api_enviroment'];
        $request = '{
                      "command": "complete",
                      "data": {
                        "api_key": "' . $api_key . '",
                        "api_username": "' . $api_username . '",
                        "order_no": "' . $order_no . '",
                        "delivery_details": {
                          "pick_up_date": "' . $pick_up_date . '",
                          "collect_payment": {
                            "status": false,
                            "pay_method": 0,
                            "amount": "' . $amount . '"
                          },
                          "return": false,
                          "note": "' . $notes . '",
                          "note_status": true
                        }
                      },
                      "request_token_id": "request_token_id"
                    }';
        if ($env == 'sandbox') {
            $url = 'https://apitest.sendyit.com/v1/#request';
        } else {
            $url = 'https://api.sendyit.com/v1/#request';
        }
        if ($env == 'sandbox') {
            $tracking_url = 'https://apptest.sendyit.com/biz/coporate/track_order_new/' .$order_no;
        } else {
            $tracking_url = 'https://apptest.sendyit.com/biz/coporate/track_order_new/' .$order_no;
        }
        $context = Context::getContext();
        $context->cookie->__set('tracking', $tracking_url);
        //return $_COOKIE['tracking'];
        $ch = curl_init($url);
        # Setup request to send json via POST.
        $payload = json_encode(json_decode($request, true));
        // $payload = $request;
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

}
