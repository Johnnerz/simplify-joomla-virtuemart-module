<?php

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.log.log');

if (!class_exists('vmPSPlugin'))
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

class plgVmPaymentSimplifyCommerce extends vmPSPlugin
{
    function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);

        $this->_loggable = TRUE;
        $this->_tablepkey = 'id';
        $this->_tableId = 'id';
        $this->tableFields = array_keys($this->getTableSQLFields());
        $varsToPush = $this->getVarsToPush();

        $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
    }

    function getTableSQLFields()
    {

        $SQLfields = array(
            "id" => ' int(1) unsigned NOT NULL AUTO_INCREMENT ',
            "virtuemart_order_id" => ' int(1) UNSIGNED DEFAULT NULL',
            "order_number" => ' char(32) DEFAULT NULL',
            "virtuemart_paymentmethod_id" => ' mediumint(1) UNSIGNED DEFAULT NULL',
            "payment_name" => ' char(255) NOT NULL DEFAULT \'\' ',
            "payment_order_total" => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
            "payment_currency" => 'char(3) ',
            "cost_per_transaction" => ' decimal(10,2) DEFAULT NULL ',
            "cost_percent_total" => ' decimal(10,2) DEFAULT NULL ',
            'log' => 'char(220)'
        );

        return $SQLfields;
    }

    protected function checkConditions($cart, $method, $cart_prices)
    {
        vmDebug('checkConditions');

        $address = (($cart->ST == 0) ? $cart->BT : $cart->ST);
        $amount = $cart_prices['salesPrice'];
        $amount_cond = ($amount >= 0 AND $amount <= 9999999);

        $countries = array();
        if (!empty($method->countries)) {
            if (!is_array($method->countries)) {
                $countries[0] = $method->countries;
            } else {
                $countries = $method->countries;
            }
        }

        if (!is_array($address)) {
            $address = array();
            $address['virtuemart_country_id'] = 0;
        }

        if (!isset($address['virtuemart_country_id'])) {
            $address['virtuemart_country_id'] = 0;
        }

        if (in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
            if ($amount_cond) {
                return TRUE;
            }
        }

        return FALSE;
    }

    function plgVmOnPaymentResponseReceived(&$html)
    {
        vmDebug('plgVmOnPaymentResponseReceived');
        return $this->plgVmOnPaymentResponseReceived($html);
    }

    function plgVmOnUserPaymentCancel()
    {
        vmDebug('plgVmOnUserPaymentCancel');
        return $this->plgVmOnUserPaymentCancel();
    }

    function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id)
    {
        vmDebug('plgVmOnShowOrderBEPayment');
        return $this->plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id);
    }

    function plgVmOnStoreInstallPaymentPluginTable($jplugin_id)
    {
        vmDebug('plgVmOnStoreInstallPaymentPluginTable');
        return $this->onStoreInstallPluginTable($jplugin_id);
    }

    function plgVmConfirmedOrder($cart, $order)
    {
        vmDebug('plgVmConfirmedOrder');

        if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }

        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }

        $session = JFactory::getSession();
        $return_context = $session->getId();

        $this->logInfo('plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');

        if (!class_exists('TableVendors'))
            require(JPATH_VM_ADMINISTRATOR . DS . 'table' . DS . 'vendors.php');

        $vendorModel = new VirtueMartModelVendor();
        $vendorModel->setId(1);
        $vendor = $vendorModel->getVendor();
        $vendorModel->addImages($vendor, 1);

        $paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
        $totalInPaymentCurrency = round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, false), 2);
        $cd = CurrencyDisplay::getInstance($cart->pricesCurrency);

        // Prepare data that should be stored in the database
        $dbValues["order_number"] = $order["details"]["BT"]->order_number;
        $dbValues["payment_name"] = $this->renderPluginName($method, $order);
        $dbValues["virtuemart_paymentmethod_id"] = $cart->virtuemart_paymentmethod_id;
        $dbValues["cost_per_transaction"] = $method->cost_per_transaction;
        $dbValues["cost_percent_total"] = $method->cost_percent_total;
        $dbValues["payment_currency"] = $method->payment_currency;
        $dbValues["payment_order_total"] = $totalInPaymentCurrency;
        $this->storePSPluginInternalData($dbValues);

        $orderId = $cart->virtuemart_order_id;

        $session = JFactory::getSession();
        $token = $_GET['cardToken'];

        require_once(JPATH_PLUGINS . DS . 'vmpayment/simplifycommerce/lib/Simplify.php');

        $publicKey = $method->simplify_commerce_sandbox_public_key;
        if ($method->simplify_commerce_payment_type == "1") {
            $publicKey = $method->simplify_commerce_live_public_key;
        }
        $privateKey = $method->simplify_commerce_sandbox_private_key;
        if ($method->simplify_commerce_payment_type == "1") {
            $publicKey = $method->simplify_commerce_live_private_key;
        }

        Simplify::$publicKey = $publicKey;
        Simplify::$privateKey = $privateKey;

        $paymentStatus = null;
        if (!empty($token)) {
            try {
                $simplifyPayment = Simplify_Payment::createPayment(array(
                    'amount' => $totalInPaymentCurrency * 100, // Cart total amount
                    'token' => $token, // Token returned by Simplify Card Tokenization
                    'description' => 'VirtueMart Order Number: ' . (int)$orderId,
                    'currency' => 'USD'
                ));

                $paymentStatus = $simplifyPayment->paymentStatus;

            } catch (Simplify_ApiException $e) {
                $errorMessage = 'There was an error processing your payment. Error message: ' . $e->getMessage() . '.<br>Please try again.';
                $this->handleError($cart, $order, $orderId, $errorMessage);
                return FALSE;
            }

            if ($paymentStatus != 'APPROVED') {
                $errorMessage = 'There was an error processing your payment. The status of the payment is: ' . $paymentStatus . '.';
                $this->handleError($cart, $order, $orderId, $errorMessage);
                return FALSE;
            }
        } else {
            $this->processConfirmedOrderPaymentResponse(2, $cart, $order, '', $dbValues['payment_name'], 'P');//Pending
        }
        $html = '<h4>Your Order ID - ' . $orderId . '</h4>' .
            '<p>You should receive an email shortly with your order confirmation.</p>';
        $this->processConfirmedOrderPaymentResponse(1, $cart, $order, $html, $dbValues['payment_name'], 'C');//Comfirmed
        return TRUE;
    }

    function handleError($cart, $order, $orderId, $errorMessage)
    {
        error_log($errorMessage);
        print '<div id="sys-error-container">' . $errorMessage . '.</div><!-- Copyright Online Store 2013 ';

        $cart->_confirmDone = FALSE;
        $cart->_dataValidated = FALSE;
        $cart->setCartIntoSession();
    }

    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart)
    {
        vmDebug('plgVmOnSelectCheckPayment');
        return $this->OnSelectCheck($cart);
    }

    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn)
    {
        vmDebug('plgVmDisplayListFEPayment');
        //JFactory::getApplication()->enqueueMessage("$cart", 'error');
        JFactory::getDocument()->addScript('https://www.simplify.com/commerce/simplify.pay.js');
        vmJsApi::addJScript(JURI::root(true) . '/plugins/vmpayment/simplifycommerce/assets/js/simplifycommerce.js');

        $method = $this->getVmPluginMethod($selected);
        $active = $this->selectedThisByMethodId($selected) ? 'true' : 'false';

        $publicKey = $method->simplify_commerce_sandbox_public_key;
        if ($method->simplify_commerce_payment_type == "1") {
            $publicKey = $method->simplify_commerce_live_public_key;
        }
        $q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $cart->paymentCurrency . '" ';
        $db = JFactory::getDBO();
        $db->setQuery($q);
        $currency_code_3 = $db->loadResult();
        $price = round($cart->cartPrices['billTotal'], 2) * 100;
        $vendorModel = VmModel::getModel('vendor');
        $vendorName = $vendorModel->getVendorName($cart->vendorId);

        vmJsApi::addJScript('activate_plugin', "
            jQuery(document).ready(function(){
                SimplifyPlugin.setActive($active);
                SimplifyPlugin.setValue('sc-key','$publicKey');
                SimplifyPlugin.setValue('name','$vendorName');
                SimplifyPlugin.setValue('reference','$cart->virtuemart_cart_id');
                SimplifyPlugin.setValue('currency','$currency_code_3');
                SimplifyPlugin.setValue('amount',$price);
            });
        ");
        return $this->displayListFE($cart, $selected, $htmlIn);
    }

    public function plgVmOnSelectCheck(VirtueMartCart $cart)
    {
        vmDebug('plgVmOnSelectCheck');
        return $this->onSelectCheck($cart);
    }

    public function plgVmOnSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name)
    {
        vmDebug('plgVmonSelectedCalculatePricePayment');
        return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array())
    {
        vmDebug('plgVmOnCheckAutomaticSelectedPayment');
        return $this->onCheckAutomaticSelected($cart, $cart_prices);
    }

    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name)
    {
        vmDebug('plgVmOnShowOrderFEPayment');
        $this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }

    function plgVmOnShowOrderPrintPayment($order_number, $method_id)
    {
        vmDebug('plgVmOnShowOrderPrintPayment');
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    function plgVmDeclarePluginParamsPayment($name, $id, &$data)
    {
        vmDebug('plgVmDeclarePluginParamsPayment');
        return $this->declarePluginParams('payment', $name, $id, $data);
    }

    function plgVmDeclarePluginParamsPaymentVM3(&$data)
    {
        vmDebug('plgVmDeclarePluginParamsPaymentVM3');
        return $this->declarePluginParams('payment', $data);
    }

    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table)
    {
        vmDebug('plgVmSetOnTablePluginParamsPayment');
        return $this->setOnTablePluginParams($name, $id, $table);
    }
}
