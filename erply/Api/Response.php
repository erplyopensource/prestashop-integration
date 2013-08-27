<?php
require_once(dirname(__FILE__).'/ErrorMessages.php');

class Erply_Api_Response
{
	protected $_apiRequest = array();
	protected $_apiResponse = array();


	public function __construct($apiResponse=array(), $apiRequest=array())
	{
		if(!empty($apiResponse) || !empty($apiRequest))
		{
			$this->init($apiResponse, $apiRequest);
		}
		return $this;
	}


	/**
	 * Init api response.
	 * 
	 * @param array $apiResponse - array sent from ERPLY API
	 * @param array $apiRequest - original request sent to ERPLY API
	 * @return Erply_Api_Response
	 */
	public function init($apiResponse=array(), $apiRequest=array()) {
		$this->_apiResponse = $apiResponse;
		$this->_apiRequest = $apiRequest;
		return $this;
	}

	/**
	 * Load all records from server.
	 * 
	 * @return GbBox_ErplyBridge_Api_Response
	 */
//	public function loadAll()
//	{
//		if($this->getTotalRecords() > $this->getRecordsCount()) {
//			$request = $this->getRequest();
//			$request['recordsOnPage'] = isset($request['recordsOnPage']) ? (int)$request['recordsOnPage'] : 20;
//			$nrOfPages = ceil($this->getTotalRecords() / $request['recordsOnPage']);
//			for($i=2; $i<=$nrOfPages; $i++) {
//				$request['pageNo'] = $i;
//				$subRequest = GbBox::getSingleton('ErplyBridge/Api')->callApiFunction( $this->getRequestFunction(), $request );
//				if(!$subRequest->isError())
//				{
//					foreach($subRequest->getRecords() as $subRecord)
//					{
//						array_push($this->_apiResponse['records'], $subRecord);
//					}
//				}
//			}
//			$this->_apiResponse['status']['recordsInResponse'] = $this->getTotalRecords();
//		}
//	}

	/**
	 * @return array
	 */
	public function getRequest() {
		return $this->_apiRequest;
	}

	/**
	 * @return string
	 */
	public function getRequestFunction() {
		if($this->_isValidResponse()) {
			return $this->_apiResponse['status']['request'];
		}
		return null;
	}

	/**
	 * @return string
	 */
	public function getStatus($key=null) {
		if($this->_isValidResponse()) {
			if(!is_null($key))
			{
				// Return key value or null
				return isset($this->_apiResponse['status'][$key]) ? $this->_apiResponse['status'][$key] : null;
			}
			else
			{
				// Return all status headers
				return $this->_apiResponse['status'];
			}
		}
	}

	/**
	 * @return bool
	 */
	public function isError() {
		if($this->getStatus('responseStatus') == 'ok') {
			return false;
		}
		return true;
	}

	/**
	 * @return int
	 */
	public function getErrorCode() {
		if($this->_isValidResponse()) {
			return $this->_apiResponse['status']['errorCode'];
		}
	}

	/**
	 * @return string
	 */
	public function getErrorMsg()
	{
		return Erply_Api_ErrorMessages::getMessage( $this->getErrorCode() );
	}

	/**
	 * @return array
	 */
	public function getRawResponse()
	{
		return $this->_apiResponse;
	}

	/**
	 * @return array
	 */
	public function getRecords() {
		if($this->_isValidResponse() && is_array($this->_apiResponse['records'])) {
			return $this->_apiResponse['records'];
		}
		return array();
	}

	/**
	 * @param int $i - element index
	 * @return array
	 */
	public function getRecord($i) {
		if($this->_isValidResponse() && is_array($this->_apiResponse['records']) && isset($this->_apiResponse['records'][$i])) {
			return $this->_apiResponse['records'][$i];
		}
		return array();
	}

	/**
	 * @return array
	 */
	public function getFirstRecord()
	{
		return $this->getRecord(0);
	}

	/**
	 * @return int - number of records in response
	 */
	public function getRecordsCount() {
		if($this->_isValidResponse()) {
			return count($this->_apiResponse['records']);
		}
		return 0;
	}

	/**
	 * @return int - number of records matching this query
	 */
	public function getTotalRecords() {
		if($this->_isValidResponse()) {
			return (int)$this->_apiResponse['status']['recordsTotal'];
		}
		return 0;
	}

	/**
	 * Send new request for next page.
	 * 
	 * @return GbBox_ErplyBridge_Api_Response
	 */
	public function getNextPage()
	{
		if(!$this->isLastPage())
		{
			// Make request from previous request
			$request = $this->getRequest();
			$request['pageNo'] = $this->getCurrentPageNr() + 1;

			return ErplyFunctions::getErplyApi()->callApiFunction( $this->getRequestFunction(), $request );
		}
		else
		{
			return null;
		}
	}

	/**
	 * @return int
	 */
	public function getLastPageNr()
	{
		$request = $this->getRequest();
		$recordsOnPage = isset($request['recordsOnPage']) ? (int)$request['recordsOnPage'] : 20;
		$totalRecords = $this->getTotalRecords();

		return ceil( $totalRecords / $recordsOnPage );
	}

	/**
	 * Get current page nr.
	 * 
	 * @return int
	 */
	public function getCurrentPageNr()
	{
		$request = $this->getRequest();
		return isset($request['pageNo']) ? (int)$request['pageNo'] : 1;
	}

	/**
	 * If current page is last or not.
	 * 
	 * @return bool
	 */
	public function isLastPage()
	{
		return ($this->getCurrentPageNr() == $this->getLastPageNr());
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return $this->getRecords();
	}

	/**
	 * Make sure api response is a valid response.
	 * 
	 * @return bool
	 */
	private function _isValidResponse() {
		if(is_array($this->_apiResponse) && isset($this->_apiResponse['status'])) {
			return true;
		}
		return false;
	}
}

?>