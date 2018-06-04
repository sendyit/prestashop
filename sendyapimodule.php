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
class SendyApiModule extends Module
{

    /** @var array Use to store the configuration from database */
    public $config_values;

    /** @var array submit values of the configuration page */
    protected static $config_post_submit_values = array('saveConfig');

    public function __construct()
    {
        $this->name = 'sendyapimodule'; // internal identifier, unique and lowercase
        $this->tab = 'front_office_features'; // backend module coresponding category
        $this->version = '1.0.0'; // version number for the module
        $this->author = 'Sendy'; // module author
        $this->need_instance = 0; // load the module when displaying the "Modules" page in backend
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Sendy Api Module'); // public name
        $this->description = $this->l('Sendy Prestashop Module for the Sendy public API'); // public description

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?'); // confirmation message at uninstall

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Install this module
     * @return boolean
     */
    public function install()
    {
        #include dirname(__FILE__) . '/sql/install.php';

        return parent::install() &&
            $this->initConfig() &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayHome');


    }

    /**
     * Uninstall this module
     * @return boolean
     */
    public function uninstall()
    {
        include dirname(__FILE__) . '/sql/uninstall.php';

        return Configuration::deleteByName($this->name) &&
            parent::uninstall();
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
    public function getOrderShippingCost($params, $shipping_cost)
    {
        if (Context::getContext()->customer->logged == true)
        {
            $id_address_delivery = Context::getContext()->cart->id_address_delivery;
            $address = new Address($id_address_delivery);
            return 100; // i want to return `$shipping_cost`
        }
        return $shipping_cost;
    }
}

?>