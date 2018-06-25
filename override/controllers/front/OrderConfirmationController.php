<?php
class OrderConfirmationController extends OrderConfirmationControllerCore
{
    /*
    * module: sendyapimodule
    * date: 2018-06-25 23:19:57
    * version: 1.0.0
    */
    public function completeOrder($notes)
    {
        $sendyapimodule = Module::getInstanceByName('sendyapimodule');
        $sendyapimodule->completeOrder($notes);
    }

    public function displayOrderConfirmation($order)
    {
        $sendyapimodule = Module::getInstanceByName('sendyapimodule');
        $address = new Address($this->context->cart->id_address_delivery);
        $customer = new Customer($order->id_customer);
        $fname = $customer->firstname;
        $lname = $customer->lastname;
        $customer_name = $fname . " " . $lname;
        $customer_phone = $address->phone;
        $customer_mail = $customer->email;
        $context = Context::getContext();
        $to_details_cookie = $context->cookie->to_details;
        $to_details = json_decode($to_details_cookie, true);
        $api_to = $to_details['to_name'];
        $to_lat = $to_details['to_lat'];
        $to_long = $to_details['to_long'];
        $sendyapimodule->getPriceQuote($api_to, $to_lat, $to_long, $customer_name, $customer_phone, $customer_mail);
        $order = new Order($order->id);
        $notes = nl2br($order->getFirstMessage());
        $this->completeOrder($notes);
        return Hook::exec('displayOrderConfirmation', array('order' => $order));
    }
}
