<?php
class Cart extends CartCore
{
    /*
    * module: sendyapimodule
    * date: 2018-06-19 00:34:52
    * version: 1.0.0
    */
    public function init()
    {
        parent::init();
        print_r($_POST);
//        // check if URL contain ajax option and set it.
//        if( $this->ajax = Tools::getValue( "ajax" ) ){
//            if( $this->ajax ){
//                $action = Tools::getValue( 'action' );
//
//                if( !empty( $action ) && method_exists( $this, 'ajaxProcess' . Tools::toCamelCase( $action ) ) ){
//                    // Return the method call
//                    $this->{'ajaxProcess' . Tools::toCamelCase( $action )}();
//                } else {
//                    $this->AjaxProcess();
//                }
//                // Required to avoid errorHandler in AJAX
//                exit;
//            }
//        }
    }
    public function getPackageShippingCost($id_carrier = null, $use_tax = true, Country $default_country = null, $product_list = null, $id_zone = null)
    {
        $shipping_cost = Tools::getValue( 'shipping_cost' );
        print Tools::jsonEncode( array(
            'shipping_cost' => $shipping_cost,
        ) );
        return 240;
    }
}
