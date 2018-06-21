<?php
class Cart extends CartCore
{
    /*
    * module: sendyapimodule
    * date: 2018-06-19 00:34:52
    * version: 1.0.0
    */
    public function setShippingCost($shipping_cost){
    	$context = Context::getContext();
		$context->cookie->__set('shipping_cost',$shipping_cost);
    }
    public function getPackageShippingCost($id_carrier = null, $use_tax = true, country $default_country = null, $product_list = null, $id_zone = null, $shipping_cost = 200)
    {
    	$context = Context::getContext();
    	$shipping_cost_cookie = $context->cookie->shipping_cost;

    	if(isset($shipping_cost_cookie)){
    		return $shipping_cost_cookie;
    	}
        return $shipping_cost;
    }
}
