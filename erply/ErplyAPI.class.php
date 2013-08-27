<?php
require_once(dirname(__FILE__).'/Exception.php');
require_once(dirname(__FILE__).'/Api/Response.php');

class ErplyAPI
{
	private $orderData = array();
	private $erpUserData = array('url' => "https://www.erply.net/api/");
	private $api;

	// getConfParameters response
	protected static $config;

	// Language code to use for ERPLY. 3 characters.
	protected static $erplyLocaleCode;
	protected static $prestaLocaleCode;


	public function  __construct() {
	}
	
	/*
	 * Function get erply user data
	 *
	 * return false if no erply data saved
	 */
	public function setErplyConnectionData($erpUserData){
		// check if all erply data is present
		if(!empty($erpUserData['clientCode']) && !empty($erpUserData['username']) && !empty($erpUserData['password']))
		{
            $this->erpUserData['url'] = 'https://'.$erpUserData['clientCode'].'.erply.com/api/';
            $this->erpUserData['clientCode'] = $erpUserData['clientCode'];
			$this->erpUserData['username'] = $erpUserData['username'];
			$this->erpUserData['password'] = $erpUserData['password'];
		}
		else
		{
			echo 'ERROR:Erply user data is required';
		}
	}

	public function setErplyConnection(){
		// include ERPLY API class
		require_once 'EAPI.class.php';
		$this->api = new EAPI();
		// Configuration settings - sessionKey is assigned automatically
		$this->api->url			= $this->erpUserData['url'];
		$this->api->clientCode	= $this->erpUserData['clientCode'];
		$this->api->username	= $this->erpUserData['username'];
		$this->api->password	= $this->erpUserData['password'];

		return 'connection set';
	}

	///////////////////////////// ERPLY WRAPPER FUNCTIONS ////////////////////////////////////////////

	public function getClientCode(){
		return $this->erpUserData['clientCode'];
	}

	/**
	 * @param string $key
	 * @return mixed - string if key IS NOT NULL, array otherwize.
	 */
	public static function getConfig($key=null)
	{
		if(is_null(self::$config))
		{
			self::$config = array();

			// Load config from API
			$apiResp = ErplyFunctions::getErplyApi()->callApiFunction('getConfParameters');
			self::$config = $apiResp->getFirstRecord();
		}

		if(!is_null($key)) {
			return isset(self::$config[$key]) ? self::$config[$key] : null;
		} else {
			return self::$config;
		}
	}

    /**
     * Returns time difference between local server and ERPLY server.
     * 
     * @return int
     */
	public function getTimeDifference()
	{
		return $this->api->getTimeDifference();
	}

	/**
	 * Get ERPLY server timestamp by Presta time.
	 * 
	 * @param integer $prestaTime
	 * @return integer
	 */
	public function getErplyTime($prestaTime=null)
	{
		if(is_null($prestaTime)) {
			$prestaTime = time();
		}
		return time() + self::getTimeDifference();
	}

	/**
	 * Get Presta timestamp by ERPLY timestamp.
	 * 
	 * @param integer $erplyTime
	 * @return integer
	 */
	public function getPrestaTime($erplyTime)
	{
		return $erplyTime - self::getTimeDifference();
	}

	/**
	 * @return string
	 */
	public static function getErplyLocaleCode()
	{
		if(is_null(self::$erplyLocaleCode))
		{
			// Get same locale as PrestaShop active locale.
			$prestaCode = ErplyFunctions::getPrestaLocaleCode();
			$erplyCode = ErplyFunctions::convertLocaleCode($prestaCode);
			if(empty($erplyCode)) {
				// Get ERPLY default if presta active is invalid.
				$erplyCode = self::getConfig('default_language');
			}
			self::$erplyLocaleCode = $erplyCode;
		}
		return self::$erplyLocaleCode;
	}

	/*
	 * if webshop user is registered in erply
	 * @param $this->inputParams|array - username, password
	 * @return $customerId
	 */
	public function verifyCustomerUser($username, $password){
		$inputParams = array('username' => $username, 'password' => $password);
		$response = $this->api->sendRequest('verifyCustomerUser', $inputParams);
		if($this->noErrorOnRequest($response)){
			return $response;
		}
		return false;
	}

	/*
	 * save new order
	 * @return bool
	 */
	public function saveSalesDocument($inputParams){
		$response = $this->api->sendRequest('saveSalesDocument', $inputParams);
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'][0]['invoiceID'];
		}
		return false;
	}

	public function deleteSalesDocument($inputParams){
		$response = $this->api->sendRequest('deleteSalesDocument', $inputParams);
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return true;
		}
		return false;
	}

	/*
	 * save new customer
	 */
	public function saveCustomer($inputParams){
		$response = $this->api->sendRequest('saveCustomer', $inputParams);
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'][0]['customerID'];
		}
		return false;
	}

	/*
	 * save/update client's address
	 */
	public function saveAddress($inputParams){
		$response = $this->api->sendRequest('saveAddress', $inputParams);
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'][0]['addressID'];
		}
		return false;
	}

	/*
	 * save/update product group
	 */
	public function saveProductGroup($inputParams){
		$response = $this->api->sendRequest('saveProductGroup', $inputParams);
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'][0]['productGroupID'];
		}
		return false;
	}

	public function saveProduct($inputParams)
	{
		$response = $this->api->sendRequest('saveProduct', $inputParams);
		$response = json_decode($response, true);

		if($this->noErrorOnRequest($response)){
			return $response['records'][0]['productID'];
		}
		return false;
	}

	public function getPayments($inputParams = false){
		$response = $this->api->sendRequest('getPayments');
		if($inputParams){
			$response = $this->api->sendRequest('getPayments', $inputParams);
		}
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'];
		}
		return false;
	}

	/*
	 * save/update payment
	 */
	public function savePayment($inputParams){
		$response = $this->api->sendRequest('savePayment', $inputParams);
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'][0]['paymentID'];
		}
		return false;
	}

	/*
	 * get address types
	 * @return $response|array
	 */
	public function getAddressTypes(){
		$inputParams = array('lang' => 'eng');
		$response = $this->api->sendRequest('getAddressTypes', $inputParams);
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'];
		}
		return false;
	}

	public function getVatRates(){
		$response = $this->api->sendRequest('getVatRates');
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'];
		}
		return false;
	}

	public function getProductUnits(){
		$response = $this->api->sendRequest('getProductUnits');
		$response = json_decode($response, true);

		if($this->noErrorOnRequest($response)){
			return $response;
		}
		return false;
	}

	public function getAddresses($inputParams){
		$response = $this->api->sendRequest('getAddresses', $inputParams);
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'];
		}
		return false;
	}

	public function getCustomerGroups($lang = false){
		if($lang){
			$inputParams = array(
				'lang' => $lang
			);
			$response = $this->api->sendRequest('getCustomerGroups', $inputParams);
		}else{
			$response = $this->api->sendRequest('getCustomerGroups');
		}
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response;
		}
		return false;
	}

	/**
	 * Call ERPLY API function.
	 * 
	 * @param string $method
	 * @param array $params
	 * @return Erply_Api_Response
	 */
	public function callApiFunction($method, $params=array())
	{
		$responseAry = $this->api->sendRequest($method, $params);
		$responseAry = json_decode($responseAry, true);

		$responseObj = new Erply_Api_Response($responseAry, $params);
		if($responseObj->isError())
		{
			if($responseObj->getErrorCode() == 1002) {
				echo '<div class="alert error">Erply allows max 500 requests in an hour. The limit is reached. Please try again after one hour</div>';
			}
			else {
				echo '<div class="alert error">ERROR: '. $responseObj->getRequestFunction() .' - '. $responseObj->getErrorCode(). '</div>';
			}
			$e = new Erply_Exception();
			throw $e->setData(array(
				  'message' => $responseObj->getErrorMsg()
				, 'code' => $responseObj->getErrorCode()
				, 'isApiError' => true
				, 'apiResponseObj' => $responseObj
			));
		}

		return $responseObj;
	}

	public function getCustomers($inputParams = false){
		if($inputParams){
			$response = $this->api->sendRequest('getCustomers', $inputParams);
		}else{
			$response = $this->api->sendRequest('getCustomers');
		}
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'];
		}
		return false;
	}

	public function getProductGroups(){
		$response = $this->api->sendRequest('getProductGroups');
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'];
		}
		return false;
	}

	public function getProducts($inputParams = false){
		if($inputParams){
			$response = $this->api->sendRequest('getProducts', $inputParams);
		}else{
			$response = $this->api->sendRequest('getProducts');
		}
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'];
		}
		return false;
	}

	public function getSalesDocuments($inputParams = false){
		if($inputParams){
			$response = $this->api->sendRequest('getSalesDocuments', $inputParams);
		}else{
			$response = $this->api->sendRequest('getSalesDocuments');
		}
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'];
		}
		return false;
	}

	public function getCurrencies(){
		$response = $this->api->sendRequest('getCurrencies');
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'];
		}
		return false;
	}

	public function saveProductPicture($inputParams){
		$response = $this->api->sendRequest('saveProductPicture', $inputParams);
		$response = json_decode($response, true);
		if($this->noErrorOnRequest($response)){
			return $response['records'][0]['productPictureID'];
		}
		return false;
	}

	private function noErrorOnRequest($response){
		if(empty($response['status']['errorCode'])){
			return true;
		}

		if($response['status']['errorCode'] == 1002)
		{
			$e = new Erply_Exception();
			throw $e->setData(array(
				  'message' => 'Erply allows max 500 requests in an hour. The limit is reached. Please try again after one hour'
				, 'code' => '1002'
				, 'isApiError' => true
				, 'apiResponseObj' => $responseObj
			));
		}
		else
		{
			echo '<div class="alert error">ERROR: on request ' . $response['status']['request'] . '. Code: ' . $response['status']['errorCode'] . (in_array($response['status']['errorCode'], array('1010', '1011', '1012', '1014')) ? ' ErrorField: '.$response['status']['errorField'] : '') . '</div>';
		}
		return false;
	}

}
