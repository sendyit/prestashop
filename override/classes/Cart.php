<?php
class Cart extends CartCore
{
    /*
    * module: sendyapimodule
    * date: 2018-06-19 00:34:52
    * version: 1.0.0
    */
    public function getPackageShippingCost($id_carrier = null, $use_tax = true, country $default_country = null, $product_list = null, $id_zone = null, $shipping_cost = 100)
    {
        return $shipping_cost;
    }
}
