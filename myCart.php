<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 6/11/18
 * Time: 4:21 PM
 */

class myCart extends CartCore
{
    public function getPackageShippingCost($id_carrier = null, $use_tax = true, Country $default_country = null, $product_list = null, $id_zone = null)
    {
//        include(dirname(__FILE__).'/custom/setShipping.php');
        return 700;


    }

}