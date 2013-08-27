<?php
include_once(dirname(__FILE__).'/../ErplyMapping.php');

class Erply_Sync_Abstract
{
	protected static $erplyApiRecordsOnPage = 100;
	private static $_categoryMappings = array();
	private static $_erplyAddressTypesAry;
	private static $_erplyVatratesAry;
	private static $_erplyDefaultCategory;
	private static $_prestaVatratesAry;
	private static $_prestaCurrenciesAry;
	private static $_customerGroupsMappingsAry = array();


	/**
	 * Get object priority configuration value.
	 * 
	 * @return string - ERPLY | Presta
	 */
	protected static function getObjectPriority()
	{
		$val = Configuration::get('ERPLY_OBJECT_PRIORITY');
		return !empty($val) ? $val : 'ERPLY';
	}

	/*
	 * Mappings
	 */

	/**
	 * Return Category mapping.
	 * 
	 * @param string $fieldName
	 * @param integer $fieldValue
	 * @return ErplyMapping
	 */
	public static function getCategoryMapping($fieldName, $fieldValue)
	{
		// Get from cache. Category mapping are kept in cache because there is
		// not too many of them but they are frequently used.
		foreach(self::$_categoryMappings as $mapping) {
			if($mapping->$fieldName == $fieldValue)
			{
				return $mapping;
			}
		}

		// Get from DB
		$mapping = ErplyMapping::getMapping('Category', $fieldName, $fieldValue);
		if(!is_null($mapping))
		{
			// Check if valid mapping.
			if(Category::categoryExists( $mapping->getPrestaId() ))
			{
				// Add to cache
				self::$_categoryMappings[] = $mapping;
			}
			else
			{
				$mapping->delete();
				$mapping = null;
			}
		}

		return $mapping;
	}

	/**
	 * Create new Category mapping.
	 * 
	 * @param integer $localId
	 * @param integer $erplyId
	 * @return ErplyMapping
	 */
	public static function createCategoryMapping($localId, $erplyId)
	{
		$mappingObj = new ErplyMapping();
		$mappingObj->object_type = 'Category';
		$mappingObj->local_id = $localId;
		$mappingObj->erply_id = $erplyId;
		if($mappingObj->add())
		{
			// Add to cache
			self::$_categoryMappings[] = $mappingObj;
		}

		return $mappingObj;
	}

	/**
	 * Return Product mapping.
	 * 
	 * @param string $fieldName
	 * @param integer $fieldValue
	 * @return ErplyMapping
	 */
	public static function getProductMapping($fieldName, $fieldValue)
	{
		$mapping = ErplyMapping::getMapping('Product', $fieldName, $fieldValue);
		if(!is_null($mapping))
		{
			// Check if valid mapping.
			if(!Product::existsInDatabase( $mapping->getPrestaId(), 'product' ))
			{
				// Presta product no longer exists, remove mapping
				$mapping->delete();
				$mapping = null;
			}
		}

		return $mapping;
	}

	/**
	 * Get Customer Group Mapping.
	 * 
	 * @param string $fieldName
	 * @param integer $fieldValue
	 * @return ErplyMapping
	 */
	public static function getCustomerGroupMapping($fieldName, $fieldValue)
	{
		// Try from cache
		foreach(self::$_customerGroupsMappingsAry as $mappingObj) {
			if($mappingObj->$fieldName == $fieldValue)
			{
				return $mappingObj;
			}
		}

		// Load from DB
		$mappingObj = ErplyMapping::getMapping('CustomerGroup', $fieldName, $fieldValue);
		if(!is_null($mappingObj))
		{
			// Check if valid mapping.
			$groupObj = new Group( $mappingObj->getPrestaId() );
			if(!$groupObj)
			{
				// Presta group has been deleted
				$mappingObj->delete();
				$mappingObj = null;
			}
			else
			{
				// Add to cache
				self::$_customerGroupsMappingsAry[] = $mappingObj;
			}
		}

		return $mappingObj;
	}

	/**
	 * @param string $fieldName
	 * @param int $fieldValue
	 * @return ErplyMapping
	 */
	public static function getCustomerMapping($fieldName, $fieldValue)
	{
		// Load from DB
		$mappingObj = ErplyMapping::getMapping('Customer', $fieldName, $fieldValue);
		if(!is_null($mappingObj))
		{
			// Check if valid mapping.
			$tmpObj = new Customer( $mappingObj->getPrestaId() );
			if(!$tmpObj)
			{
				// Presta object has been deleted
				$mappingObj->delete();
				$mappingObj = null;
			}
		}

		return $mappingObj;
	}

	/**
	 * @param string $fieldName
	 * @param int $fieldValue
	 * @return ErplyMapping
	 */
	public static function getCustomerAddressMapping($fieldName, $fieldValue)
	{
		// Load from DB
		$mappingObj = ErplyMapping::getMapping('CustomerAddress', $fieldName, $fieldValue);
		if(!is_null($mappingObj))
		{
			// Check if valid mapping.
			if(Address::addressExists( $mappingObj->getPrestaId() ) == false)
			{
				// Presta object has been deleted
				$mappingObj->delete();
				$mappingObj = null;
			}
		}

		return $mappingObj;
	}

	/**
	 * @param string $fieldName
	 * @param int $fieldValue
	 * @return ErplyMapping
	 */
	public static function getSalesInvoiceMapping($fieldName, $fieldValue)
	{
		// Load from DB
		$mappingObj = ErplyMapping::getMapping('SalesInvoice', $fieldName, $fieldValue);
		if(!is_null($mappingObj))
		{
			// Check if valid mapping.
			if(!($tmpOrderObj = new Order( $mappingObj->getPrestaId() )))
			{
				// Presta object has been deleted
				$mappingObj->delete();
				$mappingObj = null;
			}
		}

		return $mappingObj;
	}


	/*
	 * Erply functions
	 */

	
	/**
	 * @return ErplyAPI
	 */
	public static function getErplyApi()
	{
		return ErplyFunctions::getErplyApi();
	}

	/**
	 * @return array
	 */
	public static function getErplyVatrates()
	{
		if(is_null(self::$_erplyVatratesAry))
		{
			// Init
			self::$_erplyVatratesAry = array();

			// Load vatrates from ERPLY
			$vatratesAry = ErplyFunctions::getErplyApi()->getVatRates();
			if(is_array($vatratesAry)) {
				foreach($vatratesAry as $vatrateAry)
				{
					self::$_erplyVatratesAry[] = $vatrateAry;
				}
			}
		}
		return self::$_erplyVatratesAry;
	}

	/**
	 * @return array
	 */
	public static function getErplyDefaultAddressType()
	{
		if(is_null(self::$_erplyAddressTypesAry))
		{
			// Init
			self::$_erplyAddressTypesAry = array();

			// Get types
			$apiResp = ErplyFunctions::getErplyApi()->callApiFunction('getAddressTypes');
			self::$_erplyAddressTypesAry = $apiResp->getRecords();
		}

		return isset(self::$_erplyAddressTypesAry[0]) ? self::$_erplyAddressTypesAry[0] : null;
	}

	/**
	 * @return array
	 */
	public static function getErplyDefaultCategory()
	{
		if(is_null(self::$_erplyDefaultCategory))
		{
			$apiResp = self::getErplyApi()->callApiFunction('getProductGroups');
			if(!$apiResp->isError()) {
				self::setErplyDefaultCategory( $apiResp->getFirstRecord() );
			}
		}
		return self::$_erplyDefaultCategory;
	}

	/**
	 * @param array $categoryAry
	 * @return boolean
	 */
	public static function setErplyDefaultCategory($categoryAry)
	{
		if(isset($categoryAry['subGroups'])) {
			unset($categoryAry['subGroups']);
		}
		self::$_erplyDefaultCategory = $categoryAry;
		return true;
	}

	/*
	 * Presta functions
	 */

	/**
	 * @return array
	 */
	public static function getPrestaVatrates()
	{
		if(is_null(self::$_prestaVatratesAry))
		{
			// Load Presta taxes
			self::$_prestaVatratesAry = Tax::getTaxes();
		}
		return self::$_prestaVatratesAry;
	}

	/**
	 * @param float $rate
	 * @return array
	 */
	public static function getErplyVatrateByRate($rate)
	{
		$vatratesAry = self::getErplyVatrates();
		foreach($vatratesAry as $vatrateAry) {
			//rounded to 1 decimal because we calculate the rates backwards and precision gets lost
			if(round((float)$rate, 1) == round((float)$vatrateAry['rate'], 1))
			{
				return $vatrateAry;
			}
		}
		return null;
	}

	/**
	 * @param float $rate
	 * @return array
	 */
	public static function getPrestaVatrateByRate($rate)
	{
		foreach(self::getPrestaVatrates() as $prestaVatrateAry) {
			if(round((float)$rate, 2) == round((float)$prestaVatrateAry['rate'], 2))
			{
				return $prestaVatrateAry;
			}
		}
		return null;
	}

	/**
	 * @param integer $id - presta tax id
	 * @return array
	 */
	public static function getPrestaVatrateById($id)
	{
		foreach(self::getPrestaVatrates() as $prestaVatrateAry) {
			if($id == $prestaVatrateAry['id_tax'])
			{
				return $prestaVatrateAry;
			}
		}
		return null;
	}

	/**
	 * @param integer $id
	 * @return Currency
	 */
	public static function getPrestaCurrency($id)
	{
		if(is_null(self::$_prestaCurrenciesAry))
		{
			// Init cache
			// Se must read currencies from database and not to use Currency::getCurrencies() because
			// getCurrencies only returns active currencies. Deleted ones are not returned.
			self::$_prestaCurrenciesAry = array();
			$sql = 'SELECT * FROM '._DB_PREFIX_.'currency';
			foreach(Db::getInstance()->ExecuteS( $sql ) as $key=>$currency)
			{
				self::$_prestaCurrenciesAry[] = new Currency($currency['id_currency']);
			}
		}

		// Get from cache
		foreach(self::$_prestaCurrenciesAry as $prestaCurrencyObj) {
			if($prestaCurrencyObj->id == $id)
			{
				return $prestaCurrencyObj;
			}
		}

		// Load from DB
		$curr = new Currency($id, ErplyFunctions::getPrestaLocaleId());
		if($curr)
		{
			self::$_prestaCurrenciesAry[] = $curr;
			return $curr;
		}
		else
		{
			// @todo error
			return null;
		}
	}

	/**
	 * Remove invalid characters and truncate to 64 char.
	 * 
	 * @param string $name
	 * @return string
	 */
	public static function prestaSafeName($name, $maxLength=64)
	{
		$search = array('<', '>', ';', '=', '#', '{', '}');
		$replace = '_';
		$name = str_replace($search, $replace, $name);
		return substr($name, 0, $maxLength);
	}

	/**
	 * @param mixed $value
	 * @return array
	 */
	public static function createMultiLangField($value)
	{
		$resp = array();
		foreach(Language::getLanguages() AS $lang)
		{
			$resp[$lang['id_lang']] = $value;
		}
		return $resp;
	}
}

?>