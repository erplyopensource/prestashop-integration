<?php
/**
 * @category   Erply
 * @package    Erply_Connector
 */

include_once(dirname(__FILE__).'/ErplyAPI.class.php');

class ErplyFunctions
{
	protected $erply;
	protected $mapi;
	protected $sessid;
	protected $modifiedAttrName;
	protected $modifiiedAttrType;

	protected static $_erplyApi;
	protected static $_localeCodes;
	protected static $prestaLocaleCode;

	private static $enableLog = true;

	public function _construct(){
    }

    /**
     * @return ErplyAPI
     */
	public static function getErplyApi()
	{
		if(is_null(self::$_erplyApi))
		{
			self::$_erplyApi = new ErplyAPI();
			self::$_erplyApi->setErplyConnectionData(array(
				'clientCode' => Configuration::get('ERP_CLIENTCODE', 1),
				'username' => Configuration::get('ERP_USERNAME', 1),
				'password' => base64_decode(Configuration::get('ERP_PASSWORD', 1))
			));
			self::$_erplyApi->setErplyConnection();
		}
		return self::$_erplyApi;
	}

	/**
	 * @return string
	 */
	public static function getErplyCustomerCode()
	{
		return Configuration::get('ERP_CLIENTCODE', 1);
	}

	/**
	 * @param string $key - Product, Category etc.
	 * @return integer - timestamp
	 */
	public static function getLastSyncTS($key)
	{
		$configKey = 'ERPLY_'.strtoupper($key).'_LS_TS';
		$ts = Configuration::get($configKey);

		// Admin can configure the time from which onward invoices should be exported to ERPLY.
		// We must now check if this value is higher than last sync time (can be 0). If higher, then use
		// admin value.
		if($key == 'PRESTA_ORDER')
		{
			$expOrdersFrom = Configuration::get('ERPLY_EXPORT_ORDERS_FROM');
			$expOrdersFromTS = !empty($expOrdersFrom) ? $expOrdersFrom : 0;
			return max($ts, $expOrdersFromTS);
		}
		// Order history must be between ERPLY_EXPORT_ORDERS_FROM and ERPLY_PRESTA_ORDER_LS_TS
		if($key == 'PRESTA_ORDER_HISTORY')
		{
			$expOrdersFrom = Configuration::get('ERPLY_EXPORT_ORDERS_FROM');
			$expOrdersFromTS = !empty($expOrdersFrom) ? $expOrdersFrom : 0;
			$prestaOrderLastTS = self::getLastSyncTS('PRESTA_ORDER');
			if($ts < $expOrdersFromTS) {
				$ts = $expOrdersFromTS;
			}
			if($ts > $prestaOrderLastTS) {
				$ts = $prestaOrderLastTS;
			}
			return $ts;
		}

		return !empty($ts) ? $ts : 0;
	}

	/**
	 * @param string $key
	 * @param integer $timestamp
	 * @return void
	 */
	public static function setLastSyncTS($key, $timestamp)
	{
		$configKey = 'ERPLY_'.strtoupper($key).'_LS_TS';
		Configuration::updateValue($configKey, $timestamp);
		Configuration::set($configKey, $timestamp);
		return true;
	}

	/**
	 * @return integer - timestamp
	 */
	public static function getCategoryLastSyncTS()
	{
		return self::getLastSyncTS('Category');
	}

	/**
	 * @param integer $timestamp
	 * @return void
	 */
	public static function setCategoryLastSyncTS($timestamp)
	{
		return self::setLastSyncTS('Category', $timestamp);
	}

	/**
	 * @return integer - timestamp
	 */
	public static function getProductLastSyncTS()
	{
		return self::getLastSyncTS('Product');
	}

	/**
	 * @param integer $timestamp
	 * @return void
	 */
	public static function setProductLastSyncTS($timestamp)
	{
		return self::setLastSyncTS('Product', $timestamp);
	}

	/**
	 * Convert ISO 639-2 Code to ISO 639-1 Code or vice versa.
	 * @param $codeIn
	 * @return unknown_type
	 */
	public static function convertLocaleCode($code)
	{
		// Init mappings
		if(is_null(self::$_localeCodes))
		{
			self::$_localeCodes = array();

			// Read mapping table.
			if(($fh = fopen(dirname(__FILE__)."/locale_codes.csv", "r")) !== false)
			{
				while(($data = fgetcsv($fh, 1000)) !== false)
				{
					$data = array_map('trim', $data);
					if(!empty($data[0]) && !empty($data[1]))
					{
						self::$_localeCodes[$data[0]] = $data[1];
					}
				}
				fclose($fh);
			}
		}

		// ISO_639-2 is 3 characters long
		if(strlen($code) == 3)
		{
			return self::$_localeCodes[$code] ? self::$_localeCodes[$code] : null;
		}
		// ISO_639-1 is 2 characters long
		elseif(strlen($code) == 2) {
			foreach(self::$_localeCodes as $key=>$val) {
				if($val == $code)
				{
					return $key;
				}
			}
		}

		// Match not found
		return null;
	}

	/**
	 * @return int
	 */
	public static function getPrestaLocaleId()
	{
		return Configuration::get('PS_LANG_DEFAULT');
	}

	/**
	 * @return string - 2 characters
	 */
	public static function getPrestaLocaleCode()
	{
		if(is_null(self::$prestaLocaleCode)) {
			self::$prestaLocaleCode = Language::getIsoById( self::getPrestaLocaleId() );
		}
		return self::$prestaLocaleCode;
	}

	/**
	 * Returns Erply default locale.
	 * 
	 * @return string - 3 characters
	 */
	public static function getErplyLocaleCode()
	{
		return ErplyAPI::getConfig('default_language');
	}

	public static function erpGetAttribute($attributes, $attrName){
		if($attributes){
			foreach($attributes as $attribute){
				if($attribute['attributeName'] == $attrName){
					return $attribute['attributeValue'];
				}
			}
		}
		return false;
	}

	/*
	 * add attribute to erply attributes
	 */
	public static function erpSetAttribute($attributeToSet, $attributes = false){
		$newAttributes = array();
		$added = false;
		if($attributes){
			// add attribute
			foreach($attributes as $key => $attribute){
				if($attribute['attributeName'] == $attributeToSet['attributeName']){
					$attribute['attributeType'] = $attributeToSet['attributeType'];
					$attribute['attributeValue'] = $attributeToSet['attributeValue'];
					$added = true;
				}
				$newAttributes[$key] = $attribute;
			}

		}
		if(!$added){
			$newAttributes[] = $attributeToSet;
		}
		return $newAttributes;
	}

	public static function erpConvertAttributes($attributes){
		// convert to erply input
		$erpAttributes = array();
		foreach($attributes as $key => $attribute){
			$erpAttributes['attributeName' . ($key + 1)] = $attribute['attributeName'];
			$erpAttributes['attributeType' . ($key + 1)] = $attribute['attributeType'];
			$erpAttributes['attributeValue' . ($key + 1)] = $attribute['attributeValue'];
		}
		return $erpAttributes;
	}

	public static function debug()
	{
		print '<pre>';
		foreach(func_get_args() as $var)
		{
			print_r($var);
			print "\n";
		}
		print '</pre>';
		return true;
	}

	/**
	 * Log message or variables into erply_log.txt file.
	 * 
	 * @return void
	 */
	public static function log()
	{
		if(self::$enableLog == true)
		{
			$msg = "\r\n". date('Y.m.d H:i:s') .' - ';
	
			$nr = 0;
			// Add all input arguments to message.
			foreach(func_get_args() as $arg)
			{
				if($nr++ > 0) {
					$msg .= ', ';
				}
				if(is_numeric($arg) || is_string($arg)) {
					$msg .= $arg;
				} elseif(is_bool($arg)) {
					$msg .= ($arg === true) ? 'TRUE' : 'FALSE';
				} else {
					$msg .= print_r($arg, true);
				}
			}
	
			// Write to file
			file_put_contents(dirname(__FILE__) . '/erply_log.txt', $msg, FILE_APPEND);
		}
	}
}
