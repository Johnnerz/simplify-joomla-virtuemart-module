<?php

defined('_JEXEC') or die('Restricted Access');

jimport('joomla.log.log');

if (!class_exists('vmPSPlugin'))
	require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

class plgVmPaymentSimplifyCommerce extends vmPSPlugin
{
	public static $_this = FALSE;

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
			'log'=>'char(220)'
		);

		return $SQLfields;
	}

	function _clearSimplifyCommerceSession ()
	{
		$session = JFactory::getSession(array('simplifycommerce'));
		$session->clear('simplifycommerce','vm');
	}

	function _setSimplifyCommerceIntoSession ()
	{

		$session = JFactory::getSession();
		$sessionSimplifyCommerce = new stdClass();
		// card information
		$sessionSimplifyCommerce->cc_type = $this->_cc_type;
		$sessionSimplifyCommerce->cc_number = $this->_cc_number;
		$sessionSimplifyCommerce->cc_cvv = $this->_cc_cvv;
		$sessionSimplifyCommerce->cc_expire_month = $this->_cc_expire_month;
		$sessionSimplifyCommerce->cc_expire_year = $this->_cc_expire_year;
		$sessionSimplifyCommerce->cc_valid = $this->_cc_valid;
		$session->set('simplifycommerce', serialize($sessionSimplifyCommerce), 'vm');
	}
	function _getSimplifyCommerceFromSession ()
	{

		$session = JFactory::getSession();
		$sessionSimplifyCommerce = $session->get('simplifycommerce', 0, 'vm');

		if (!empty($sessionSimplifyCommerce)) {
			$simplifyCommerceData = unserialize($sessionSimplifyCommerce);
			$this->_cc_type = $simplifyCommerceData->cc_type;
			$this->_cc_number = $simplifyCommerceData->cc_number;
			$this->_cc_cvv = $simplifyCommerceData->cc_cvv;
			$this->_cc_expire_month = $simplifyCommerceData->cc_expire_month;
			$this->_cc_expire_year = $simplifyCommerceData->cc_expire_year;
			$this->_cc_valid = $simplifyCommerceData->cc_valid;
		}
	}

	public function plgVmOnCheckoutAdvertise($cart, &$payment_advertise) {

		if (!$this->selectedThisByMethodId($cart->virtuemart_paymentmethod_id)) {
			return NULL; // Another method was selected, do nothing
		}
		if (!($this->_currentMethod = $this->getVmPluginMethod($cart->virtuemart_paymentmethod_id))) {
			return FALSE;
		}
		$session=JFactory::getSession();
		if(JFactory::getApplication()->input->get('simplifyToken')) {

			$session->set('simplify_tkn',JFactory::getApplication()->input->get('simplifyToken'));
		}

		$temp=JRequest::getVar('cc_cvv_' . $cart->virtuemart_paymentmethod_id, '');

//		if($temp =='' && $this->_cc_number !=''){}
//		else {
//
//			$this->_cc_type = JRequest::getVar('cc_type_' . $cart->virtuemart_paymentmethod_id, '');
//			$this->_cc_name = JRequest::getVar('cc_name_' . $cart->virtuemart_paymentmethod_id, '');
//			$this->_cc_number = str_replace(" ", "", JRequest::getVar('cc_number_' . $cart->virtuemart_paymentmethod_id, ''));
//			$this->_cc_cvv = JRequest::getVar('cc_cvv_' . $cart->virtuemart_paymentmethod_id, '');
//			$this->_cc_expire_month = JRequest::getVar('cc_expire_month_' . $cart->virtuemart_paymentmethod_id, '');
//			$this->_cc_expire_year = JRequest::getVar('cc_expire_year_' . $cart->virtuemart_paymentmethod_id, '');
//
//			if (!$this->_validate_creditcard_data(TRUE)) {}
//			else{
//				$this->_setSimplifyCommerceIntoSession();
//			}
//			return true;
//		}

	}

	function _validate_creditcard_data ($enqueueMessage = TRUE)
	{

		$html = '';
		$this->_cc_valid = FALSE;

//		if (!Creditcard::validate_credit_card_number($this->_cc_type, $this->_cc_number)) {
//			$this->_errormessage[0] = 'VMPAYMENT_SIMPLIFY_COMMERCE_CARD_NUMBER_INVALID';
//			$this->_cc_valid = FALSE;
//		}
//
//		if (!Creditcard::validate_credit_card_cvv($this->_cc_type, $this->_cc_cvv)) {
//			$this->_errormessage[1] = 'VMPAYMENT_SIMPLIFY_COMMERCE_CARD_CVV_INVALID';
//			$this->_cc_valid = FALSE;
//		}
//		if (!Creditcard::validate_credit_card_date($this->_cc_type, $this->_cc_expire_month, $this->_cc_expire_year)) {
//			$this->_errormessage[2] = 'VMPAYMENT_SIMPLIFY_COMMERCE_CARD_EXPIRATION_DATE_INVALID';
//			$this->_cc_valid = FALSE;
//		}
//		if(count($this->_errormessage)){
//
//			if (!$this->_cc_valid) {
//				if($this->bool!=true){
//					foreach ($this->_errormessage as $msg) {
//						$html .= Jtext::_($msg) . "<br/>";
//					}
//					$this->bool=true;
//				}
//
//
//			}
//
//
//			if (!$this->_cc_valid && $enqueueMessage) {
//				$app = JFactory::getApplication();
//				$app->enqueueMessage($html);
//			}
//		}

		return $this->_cc_valid;

	}





	public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn)
	{
		$this->debugLog($cart , "plgVmDisplayListFEPayment", 'debug');

		$session = JFactory::getSession();
		$db=JFactory::getDbo();
		$q=$db->getQuery(true);
		$q='SELECT virtuemart_paymentmethod_id from #__virtuemart_paymentmethods WHERE payment_element="simplifycommerce"';
		$db->setQuery($q);
		try{
			$rst1=$db->loadResult();
		}
		catch(Exception $e){
			$app = JFactory::getApplication();
			$app->enqueueMessage($e->getMessage(),'error');

		}

		$p_method_new = $this->getVmPluginMethod((int)$rst1);

		/* *********** clear session ********** */

		if(!($rst1==$selected)){

			if($session->get('simplify_tkn') || $session->get('tkn')){
				$session->clear('simplify_tkn');
				$session->clear('tkn');
			}
			$this->_clearSimplifyCommerceSession();
		}

		if ($this->getPluginMethods($cart->vendorId) === 0) {
			if (empty($this->_name)) {
				$app = JFactory::getApplication();
				$app->enqueueMessage(JText::_('COM_VIRTUEMART_CART_NO_' . strtoupper($this->_psType)));
				return FALSE;
			} else {
				return FALSE;
			}
		}
		$html = array();
		$method_name = $this->_psType . '_name';

		$simplifyCommercePublicKey = "";

		VmConfig::loadJLang('com_virtuemart', true);
		vmJsApi::jCreditCard();

		if (JFactory::getApplication()->isSite()) {
			JFactory::getDocument()->addStyleSheet('//netdna.bootstrapcdn.com/font-awesome/4.0.1/css/font-awesome.css');
			JFactory::getDocument()->addStyleSheet(JURI::root(true) . '/plugins/vmpayment/simplifycommerce/assets/css/simplifycommerce.css');

			vmJsApi::jQuery();
			JFactory::getDocument()->addScript('https://www.simplify.com/commerce/v1/simplify.js');
			JFactory::getDocument()->addScript(JURI::root(true) . '/plugins/vmpayment/simplifycommerce/assets/js/simplifycommerce.js');
		}

		$htmla = '';
		$html = array();
		foreach ($this->methods as $this->_currentMethod) {
			if ($this->checkConditions($cart, $this->_currentMethod, $cart->cartPrices)) {
				$cartPrices=$cart->cartPrices;
				$methodSalesPrice = $this->setCartPrices($cart, $cartPrices, $this->_currentMethod);
				$this->_currentMethod->$method_name = $this->renderPluginName($this->_currentMethod);
				$html = $this->getPluginHtml($this->_currentMethod, $selected, $methodSalesPrice);

				$publicKey = $this->_currentMethod->simplify_commerce_sandbox_public_key;

				vmJsApi::addJScript('set_simplify_commerce_key',"
				jQuery(function(){
					jQuery(document).data('simplify_commerce_public_key','$publicKey');
				});
				");

				$html.= '<div id="simplify-payment-form">
			<div class="frm-grp">
		        <label>Credit Card Number: </label>
		        <input id="cc-number" type="text" maxlength="20" autocomplete="off" value="" autofocus />
		    </div>
		    <div class="frm-grp">
		        <label>CVC: </label>
		        <input id="cc-cvc" type="text" maxlength="3" autocomplete="off" value=""/>
		    </div>
		    <div class="frm-grp">
		        <label>Expiry Date: </label>
		        <select id="cc-exp-month">
		            <option value="01">Jan</option>
		            <option value="02">Feb</option>
		            <option value="03">Mar</option>
		            <option value="04">Apr</option>
		            <option value="05">May</option>
		            <option value="06">Jun</option>
		            <option value="07">Jul</option>
		            <option value="08">Aug</option>
		            <option value="09">Sep</option>
		            <option value="10">Oct</option>
		            <option value="11">Nov</option>
		            <option value="12">Dec</option>
		        </select>
		        <select id="cc-exp-year">
		            <option value="17">2017</option>
		            <option value="18">2018</option>
		            <option value="19">2019</option>
		            <option value="20">2020</option>
		            <option value="21">2021</option>
		            <option value="22">2022</option>
		        </select>
		    </div>
		    <div class="simplify-processing frm-grp">Validating your credit card... <i class="fa fa-refresh fa-spin"></i></div>
		</div>
		<div>API Key = '.$publicKey.'</div>
		<div id="error-container"></div>';

				$htmla[] = $html;
			}
		}
		$htmlIn[] = $htmla;

		return TRUE;
	}

	function plgVmConfirmedOrder($cart, $order)
	{
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
		$simplify = $session->get('simplify', 0, 'vm');

		if (!empty($simplify)) {
			$simplifyData = unserialize($simplify);
			$token = $simplifyData->token;

			require_once(JPATH_PLUGINS . DS .'vmpayment/simplifycommerce/lib/Simplify.php');

			Simplify::$publicKey = $method->publicKey;
			Simplify::$privateKey = $method->privateKey;

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
			$this->processPayment($order, $orderId);
			print '<!-- Copyright Online Store 2013 ';

			$cart->_confirmDone = FALSE;
			$cart->_dataValidated = FALSE;
			$cart->setCartIntoSession();

			return FALSE;
		}

		$html = '<h4>Your Order ID - ' . $orderId . '</h4>' .
			'<p>You should receive an email shortly with your order confirmation.</p>';

		$modelOrder = VmModel::getModel('orders');
		$order['order_status'] = 'C';
		$order['virtuemart_order_id'] = $orderId;
		$order['customer_notified'] = 1;
		$order['comments'] = '';
		$modelOrder->updateStatusForOneOrder($orderId, $order, TRUE);

		//We delete the old stuff
		$cart->emptyCart();
		$this->clearSimplifySession();
		JRequest::setVar('html', $html);
		return TRUE;
	}

	function clearSimplifySession()
	{
		$session = JFactory::getSession();
		$session->clear('simplify', 'vm');
	}

	function handleError($cart, $order, $orderId, $errorMessage)
	{
		error_log($errorMessage);
		print '<div id="sys-error-container">' . $errorMessage . '.</div><!-- Copyright Online Store 2013 ';

		$cart->_confirmDone = FALSE;
		$cart->_dataValidated = FALSE;
		$cart->setCartIntoSession();
	}

	protected function renderPluginName ($plugin)
	{

		$return = '';
		$plugin_name = $this->_psType . '_name';
		$plugin_desc = $this->_psType . '_desc';
		$description = '';

		if (!empty($plugin->$plugin_desc)) {
			$description = '<span class="' . $this->_type . '_description">' . $plugin->$plugin_desc  . '</span>';
		}

		$this->_getSimplifyCommerceFromSession();
		$pluginName = $return . '<span class="' . $this->_type . '_name">' . $plugin->$plugin_name . '</span>' .$description;

		return $pluginName;
	}

//	function plgVmOnShowOrderBEPayment ($virtuemart_order_id, $virtuemart_payment_id)
//	{
//		if (!($this->_currentMethod=$this->selectedThisByMethodId($virtuemart_payment_id))) {
//			return NULL; // Another method was selected, do nothing
//		}
//		if (!($paymentTable = $this->getDataByOrderId($virtuemart_order_id))) {
//			return NULL;
//		}
//		VmConfig::loadJLang('com_virtuemart');
//
//		$html = '<table class="adminlist">' . "\n";
//		$html .= $this->getHtmlHeaderBE();
//		$html .= $this->getHtmlRowBE('COM_VIRTUEMART_PAYMENT_NAME', $paymentTable->payment_name);
//		$html .= $this->getHtmlRowBE('STRIPE_PAYMENT_ORDER_TOTAL', number_format($paymentTable->payment_order_total,2) . " " .self::$paymentcurrency);
//		$html .= $this->getHtmlRowBE('STRIPE_COST_PER_TRANSACTION', $paymentTable->cost_per_transaction);
//		$html .= $this->getHtmlRowBE('STRIPE_COST_PERCENT_TOTAL', $paymentTable->cost_percent_total);
//		$code = "authorizenet_response_";
//		foreach ($paymentTable as $key => $value) {
//			if (substr($key, 0, strlen($code)) == $code) {
//				$html .= $this->getHtmlRowBE($key, $value);
//			}
//		}
//		$html .= '</table>' . "\n";
//		return $html;
//	}

	function plgVmgetPaymentCurrency ($virtuemart_paymentmethod_id, &$paymentCurrencyId) {


		if (!($method = $this->getVmPluginMethod ($virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement ($method->payment_element)) {
			return FALSE;
		}
		$this->getPaymentCurrency ($method);

		$paymentCurrencyId = $method->payment_currency;
		return;
	}

	protected function checkConditions($cart, $method, $cart_prices)
	{
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

	public function getVmPluginCreateTableSQL()
	{
		return $this->createTableSQL('Simplify Commerce Payments Table');
	}

	function plgVmDeclarePluginParamsPaymentVM3( &$data) {
		return $this->declarePluginParams('payment', $data);
	}


	public function plgVmDeclarePluginParamsPayment($name, $id, &$data)
	{
		return $this->declarePluginParams('payment', $name, $id, $data);
	}

	public function plgVmSetOnTablePluginParamsPayment($name, $id, &$table)
	{
		return $this->setOnTablePluginParams($name, $id, $table);
	}

	public function plgVmOnStoreInstallPaymentPluginTable($jplugin_id)
	{
		return parent::onStoreInstallPluginTable($jplugin_id);
	}

	public function plgVmOnPaymentResponseReceived(&$html)
	{
		return null;
	}

	public function plgVmOnSelectCheckPayment(VirtueMartCart $cart)
	{
		if (!$this->selectedThisByMethodId($cart->virtuemart_paymentmethod_id)) {
			return NULL; // Another method was selected, do nothing
		}

		if (isset($_POST['simplifyToken'])) {
			$session = JFactory::getSession();
			$simplify = new stdClass();
			$simplify->token = $_POST['simplifyToken'];
			$session->set('simplify', serialize($simplify), 'vm');
		}

		return TRUE;
	}

	public function plgVmOnSelectedCalculatePricePayment (VirtueMartCart $cart, array &$cart_prices, &$payment_name)
	{
		if (!($this->_currentMethod = $this->getVmPluginMethod($cart->virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($this->_currentMethod->payment_element)) {
			return FALSE;
		}


		$cart_prices['payment_tax_id'] = 0;
		$cart_prices['payment_value'] = 0;
		if (!$this->checkConditions($cart, $this->_currentMethod, $cart_prices)) {
			return FALSE;
		}
		$payment_name = $this->renderPluginName($this->_currentMethod);
		$this->setCartPrices($cart, $cart_prices, $this->_currentMethod);
		return TRUE;
	}


	function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array())
	{
		return $this->onCheckAutomaticSelected($cart, $cart_prices);
	}

	public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name)
	{
		$this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
	}

	function plgVmonShowOrderPrintPayment($order_number, $method_id)
	{
		return $this->onShowOrderPrint($order_number, $method_id);
	}

}
