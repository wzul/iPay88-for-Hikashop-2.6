<?php

/*
 * @package A iPay88 for Hikashop Payment Plugin
 * @version 1.0.0
 * @author wanzul-hosting.com
 */

//Prevent from direct access
defined('_JEXEC') or die('Restricted access');

// You need to extend from the hikashopPaymentPlugin class which already define lots of functions in order to simplify your work
class plgHikashoppaymentiPay88 extends hikashopPaymentPlugin {

    //List of the plugin's accepted currencies. The plugin won't appear on the checkout if the current currency is not in that list. You can remove that attribute if you want your payment plugin to display for all the currencies
    var $accepted_currencies = array("MYR", "RM");
    // Multiple plugin configurations. It should usually be set to true
    var $multiple = true;
    //Payment plugin name (the name of the PHP file)
    var $name = 'ipay88';
    // This array contains the specific configuration needed (Back end > payment plugin edition), depending of the plugin requirements.
    // They will vary based on your needs for the integration with your payment gateway.
    // The first parameter is the name of the field. In upper case for a translation key.
    // The available types (second parameter) are: input (an input field), html (when you want to display some custom HTML to the shop owner), textarea (when you want the shop owner to write a bit more than in an input field), big-textarea (when you want the shop owner to write a lot more than in an input field), boolean (for a yes/no choice), checkbox (for checkbox selection), list (for dropdown selection) , orderstatus (to be able to select between the available order statuses)
    // The third parameter is the default value.
    var $pluginConfig = array(
        // User's API Secret Key
        'ipay88apikey' => array("Merchant Code", 'input'),
        // User's Collection ID
        'ipay88collectionid' => array("Merchant Key", 'input'),
        // To allow iPay88 Payment Notification
        //'ipay88deliver' => array('Enable Email & SMS Notification', 'boolean', '0'),
        // iPay88 Payment Verification Mode. 0 for Callback. 1 for Return
        'ipay88notification' => array('Verification Type', 'list', array(
                'Both' => 'Both',
                'Callback' => 'Callback',
                'Return' => 'Return'
            )),
        'notification' => array('ALLOW_NOTIFICATIONS_FROM_X', 'boolean', '1'),
        //iPay88 Mode: Production or Staging
        //'mode' => array('Mode', 'list', array(
        //        'Production' => 'Production',
        //        'Staging' => 'Staging'
        //    )),
        //Custom Redirect Path
        'successurl' => array("Success return url", 'input'),
        'cancelurl' => array("Cancel return url", 'input'),
        // Write some things on the debug file
        'debug' => array('DEBUG', 'boolean', '0'),
        // The URL where the user is redirected after a fail during the payment process
        //'cancel_url' => array('CANCEL_URL_DEFINE','html',''),
        // The URL where the user is redirected after the payment is done on the payment gateway. It's a pre determined URL that has to be given to the payment gateway
        //'return_url_gateway' => array('RETURN_URL_DEFINE', 'html',''),
        // The URL where the user is redirected by HikaShop after the payment is done ; "Thank you for purchase" page
        //'return_url' => array('RETURN_URL', 'input'),
        // The URL where the payment platform the user about the payment (fail or success)
        //'notify_url' => array('NOTIFY_URL_DEFINE','html',''),
        // Invalid status for order in case of problem during the payment process
        'invalid_status' => array('INVALID_STATUS', 'orderstatus'),
        // Valid status for order if the payment has been done well
        'verified_status' => array('VERIFIED_STATUS', 'orderstatus')
    );

    /**
     * The constructor is optional if you don't need to initialize some parameters of some fields of the configuration and not that it can also be done in the getPaymentDefaultValues function as you will see later on
     */
    function __construct(&$subject, $config) {
        $this->pluginConfig['notification'][0] = JText::sprintf('ALLOW_NOTIFICATIONS_FROM_X', 'ipay88');

        // This is the cancel URL of HikaShop that should be given to the payment gateway so that it can redirect to it when the user cancel the payment on the payment gateway page. That URL will automatically cancel the order of the user and redirect him to the checkout so that he can choose another payment method
        //$this->pluginConfig['cancel_url'][2] = HIKASHOP_LIVE . "index.php?option=com_hikashop&ctrl=order&task=cancel_order";
        // This is the "thank you" or "return" URL of HikaShop that should be given to the payment gateway so that it can redirect to it when the payment of the user is valid. That URL will reinit some variables in the session like the cart and will then automatically redirect to the "return_url" parameter
        //$this->pluginConfig['return_url'][2] = HIKASHOP_LIVE . "index.php?option=com_hikashop&ctrl=checkout&task=after_end";
        // This is the "notification" URL of HikaShop that should be given to the payment gateway so that it can send a request to that URL in order to tell HikaShop that the payment has been done (sometimes the payment gateway doesn't do that and passes the information to the return URL, in which case you need to use that notification URL as return URL and redirect the user to the HikaShop return URL at the end of the onPaymentNotification function)


        return parent::__construct($subject, $config);
    }

    /**
     * This function is called at the end of the checkout. That's the function which should display your payment gateway redirection form with the data from HikaShop
     */
    function onAfterOrderConfirm(&$order, &$methods, $method_id) {
        // This is a mandatory line in order to initialize the attributes of the payment method
        parent::onAfterOrderConfirm($order, $methods, $method_id);

        // Here we can do some checks on the options of the payment method and make sure that every required parameter is set and otherwise display an error message to the user
        // The plugin can only work if those parameters are configured on the website's backend
        if (empty($this->payment_params->ipay88apikey)) {
            // Enqueued messages will appear to the user, as Joomla's error messages
            $this->app->enqueueMessage('You have to configure an API Secret Key for the iPay88 plugin payment first : check your plugin\'s parameters, on your website backend', 'error');
            return false;
        } elseif (empty($this->payment_params->ipay88collectionid)) {
            $this->app->enqueueMessage('You have to configure a Collection ID for the iPay88 plugin payment first : check your plugin\'s parameters, on your website backend', 'error');
            return false;
        } else {
            // This feature is not available in Hikashop 3.0
            $address = $this->app->getUserState(HIKASHOP_COMPONENT . '.billing_address');

            require_once __DIR__ . '/ipay88api.php';
            if (!empty($address)) {
                $amout = round($order->cart->full_total->prices[0]->price_value_with_tax, 2);
                $encrypstr = iPay88_SHA::iPay88_signature($this->payment_params->ipay88collectionid, $this->payment_params->ipay88apikey, $order->order_id, $amout, "MYR");
                $notify_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=ipay88&tmpl=component&lang=' . $this->locale . $this->url_itemid . '&orderid=' . $order->order_id . '&return=0';
                $return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=ipay88&tmpl=component&lang=' . $this->locale . $this->url_itemid . '&orderid=' . $order->order_id . '&return=1';

                $vars = array(
                    'UserName' => @$order->cart->billing_address->address_firstname . " " . @$order->cart->billing_address->address_lastname,
                    'UserEmail' => $this->user->user_email,
                    'UserContact' => @$order->cart->billing_address->address_telephone,
                    'MerchantCode' => $this->payment_params->ipay88apikey,
                    'Currency' => 'MYR',
                    'RefNo' => $order->order_id,
                    'Amount' => $amout,
                    'ProdDesc' => 'Order Number: ' . $order->order_number,
                    'Remark' => 'Order Number: ' . $order->order_number,
                    'Signature' => $encrypstr,
                    'Lang' => 'UTF-8',
                    'URL' => iPay88_SHA::HOST,
                    'BackendURL' => $notify_url,
                    'ResponseURL' => $return_url
                );
            }
            $this->vars = $vars;

            // Ending the checkout, ready to be redirect to the plateform payment final form
            // The showPage function will call the example_end.php file which will display the redirection form containing all the parameters for the payment platform
            return $this->showPage('end');
        }
    }

    /**
     * To set the specific configuration (back end) default values (see $pluginConfig array)
     */
    function getPaymentDefaultValues(&$element) {
        $element->payment_name = 'iPay88';
        $element->payment_description = 'Pay using iPay88';
        $element->payment_images = '';
        $element->payment_params->ipay88deliver = false;
        $element->payment_params->ipay88notification = "Both";
        $element->payment_params->currency = $this->accepted_currencies[0];
        $element->payment_params->notification = true;
        $element->payment_params->invalid_status = 'cancelled';
        $element->payment_params->verified_status = 'confirmed';
    }

    /**
     * After submiting the platform payment form, this is where the website will receive the response information from the payment gateway servers and then validate or not the order
     */
    function onPaymentNotification(&$statuses) {

        include_once 'ipay88api.php';

        $order_id = filter_var($_GET['orderid'], FILTER_SANITIZE_STRING);
        $dbOrder = $this->getOrder($order_id);
        $this->loadPaymentParams($dbOrder);
        $this->loadOrderData($dbOrder);
        $ipay88 = new iPay88_Callback($this->payment_params->ipay88collectionid);
        // Ensure that payment parameter is loaded
        if (empty($this->payment_params))
            return false;

        if (!isset($_GET['return']))
            return false;

        // If this is User Return. Continue even if not paid
        if ($_GET['return'] === '1') {
            $return = true;
            $data = $ipay88->verifySignature()->requeryStatus(
                            array(
                                'exit' => false,
                    ))->getData();
            if ($this->payment_params->ipay88notification == 'Both' || $this->payment_params->ipay88notification == 'Return') {
                $ipn = true;
            } else
                $ipn = false;
        }
        // If this is Backend. End if not paid
        elseif ($_GET['return'] === '0') {
            $return = false;
            /*
              $data = $ipay88->verifySignature()->requeryStatus(
              array(
              'exit' => true,
              ))->getData();
             * 
             */
            //Temporary

            $data = $ipay88->verifySignature()->getData();


            if ($this->payment_params->ipay88notification == 'Both' || $this->payment_params->ipay88notification == 'Callback')
                $ipn = true;
            else
                $ipn = false;
        }
        // Security reason. If other than that, die.
        else {
            return false;
        }
        $billid = filter_var($_REQUEST['TransId'], FILTER_SANITIZE_STRING);
        if ($order_id != $data['RefNo'])
            return false;

        // Here we are configuring the "succes URL" and the "fail URL". 
        // After checking all the parameters sent by the payment gateway, we will redirect 
        // the customer to one or another of those URL (not necessary for our example platform).
        $return_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id=' . $order_id . $this->url_itemid;
        $cancel_url = HIKASHOP_LIVE . 'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id=' . $order_id . $this->url_itemid;

        $success = $data['Status'] == 1 ? true : false;

        // If paid.
        if ($success) {
            // If this is return and the payment is success
            if ($return) {
                // If the IPN is set to Return or Both, 
                // update DB. Otherwise, leave it
                if ($ipn) {

                    // Save to DB only 1 times. Check first
                    if ($dbOrder->order_status != $this->payment_params->verified_status) {
                        $this->modifyOrder($order_id, $this->payment_params->verified_status, true, true);
                    }
                }

                //If user set custom redirect path
                if (!empty($this->payment_params->successurl)) {
                    $return_url = $this->payment_params->successurl;
                }

                $this->app->redirect($return_url);
            }

            // If this is callback and the payment is success
            elseif (!$return) {
                // If the IPN is true, update DB. Otherwise, leave it
                if ($ipn) {
                    // Save to DB only 1 times. Check first
                    if ($dbOrder->order_status == $this->payment_params->verified_status)
                        return true;
                    $this->modifyOrder($order_id, $this->payment_params->verified_status, true, true);

                    // Debug mode activated or not
                    if ($this->payment_params->debug) {
                        // Here we display debug information which will be catched by HikaShop and stored in the payment log file available in the configuration's Files section.
                        echo print_r($vars, true) . "\n\n\n";
                        echo print_r($dbOrder, true) . "\n\n\n";
                        echo print_r($data['TransId'], true) . "\n\n\n";
                        echo print_r($data['Status'], true) . "\n\n\n";
                    }
                }
                echo 'RECEIVEOK';
            }
            return true;
        }
        // If not paid
        elseif (!$success) {
            // If this is return and the payment is not success
            // iPay88 API does not return callback if the not paid
            if ($ipn) {
                $this->modifyOrder($order_id, $this->payment_params->invalid_status, true, true);
            }

            //If user set custom redirect path
            if (!empty($this->payment_params->cancelurl)) {
                $cancel_url = $this->payment_params->cancelurl;
            }
            $this->app->redirect($cancel_url);
        } else
            return false;
    }

    function onPaymentConfigurationSave(&$element) {
        if (empty($element->payment_params->currency))
            $element->payment_params->currency = $this->accepted_currencies[0];
        return true;
    }

}
