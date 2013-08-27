<?php
include_once(dirname(__FILE__).'/Abstract.php');
include_once(dirname(__FILE__).'/Customers.php');
include_once(dirname(__FILE__).'/../ErplyFunctions.class.php');

class Erply_Sync_CustomerAddresses extends Erply_Sync_Abstract
{
	protected static $_prestaChangedAddressesIds;

	/**
	 * Sync. We only export addresses.
	 * 
	 * @return integer - total objects synchronized
	 */
	public static function syncAll($ignoreLastTimestamp=false)
	{
		$total = 0;
		$total += self::exportAll($ignoreLastTimestamp);

		return $total;
	}

    /**
     * Import ERPLY objects.
     * 
     * @return integer - Nr of objects imported.
     */
	public static function importAll($ignoreLastTimestamp=false)
	{
		return 0;
	}

    /**
     * Export Presta objects.
     * 
     * @return integer - Nr of objects exported.
     */
	public static function exportAll($ignoreLastTimestamp=false)
	{
		ErplyFunctions::log('Start Customer Address Export.');

		if($ignoreLastTimestamp == true)
		{
			Configuration::set('ERPLY_PRESTA_CUST_ADDR_LS_TS', 0);
		}

		$return = 0;
		$prestaLocaleId = ErplyFunctions::getPrestaLocaleId();

		foreach(self::getPrestaChangedAddressesIds() as $prestaAddressId)
		{
			// Get Presta object
			$prestaAddressObj = new Address($prestaAddressId, $prestaLocaleId);

			// Find mapping
			$mappingObj = self::getCustomerAddressMapping('local_id', $prestaAddressId);

			// Mapping found, Address IS in sync
			if(!is_null($mappingObj))
			{
				// Update ERPLY object
				if(self::updateErplyAddress($prestaAddressObj, $mappingObj))
				{
					$return++;
				}
			}
			// Mapping not found, Address NOT in sync.
			else
			{
				// Create new ERPLY object
				if(self::createErplyAddress($prestaAddressObj))
				{
					$return++;
				}
			}

			// Update last sync TS
			ErplyFunctions::setLastSyncTS('PRESTA_CUST_ADDR', strtotime($prestaAddressObj->date_upd));
		}

		ErplyFunctions::log('End Customer Address Export.');

		return $return;
	}

	/**
	 * Creates new ERPLY product picture based on Presta product image.
	 * 
	 * @param Address $prestaAddressObj
	 * @return array - array( array $erplyAddressAry, ErplyMapping $mappingObj )
	 */
	protected static function createErplyAddress($prestaAddressObj)
	{
		ErplyFunctions::log('Creating ERPLY Customer Address.');

		$prestaLocaleId = ErplyFunctions::getPrestaLocaleId();
		$erplyAddressAry = array();

		// Customer mapping must exist
		$customerMappingObj = self::getCustomerMapping('local_id', $prestaAddressObj->id_customer);
		if(empty($customerMappingObj)) {
			// @todo error
			return false;
		}

		// Address type must exist.
		$erplyAddressTypeAry = self::getErplyDefaultAddressType();
		if(empty($erplyAddressTypeAry)) {
			// @todo error
			return false;
		}

		// Customer
		$erplyAddressAry['ownerID'] = $customerMappingObj->getErplyId();

		// Type
		$erplyAddressAry['typeID'] = $erplyAddressTypeAry['id'];

		// Street
		$erplyAddressAry['street'] = empty($prestaAddressObj->address2) ? $prestaAddressObj->address1 : $prestaAddressObj->address1.', '.$prestaAddressObj->address2;

		// City
		$erplyAddressAry['city'] = $prestaAddressObj->city;

		// Postal Code
		$erplyAddressAry['postalCode'] = $prestaAddressObj->postcode;

		// Country
		$erplyAddressAry['country'] = $prestaAddressObj->country;

		// Save
		$apiResp = ErplyFunctions::getErplyApi()->callApiFunction('saveAddress', $erplyAddressAry);
		$apiFirstRecordAry = $apiResp->getFirstRecord();
		$erplyAddressAry['addressID'] = $apiFirstRecordAry['addressID'];

		// Create mapping
		$mappingObject = new ErplyMapping();
		$mappingObject->object_type = 'CustomerAddress';
		$mappingObject->local_id = $prestaAddressObj->id;
		$mappingObject->erply_id = $erplyAddressAry['addressID'];
		$mappingObject->add();

		return array($erplyAddressAry, $mappingObject);
	}

	/**
	 * Update ERPLY object.
	 * 
	 * @param Address $prestaAddressObj
	 * @param ErplyMapping $mappingObj
	 * @return boolean
	 */
	protected static function updateErplyAddress($prestaAddressObj, $mappingObj=null)
	{
		if(!is_null($mappingObj)) {
			ErplyFunctions::log('Updating ERPLY Customer Address.');
		} else {
			ErplyFunctions::log('Creating ERPLY Customer Address.');
		}

		$prestaLocaleId = ErplyFunctions::getPrestaLocaleId();
		$erplyAddressAry = array();

		// Customer mapping must exist
		$customerMappingObj = self::getCustomerMapping('local_id', $prestaAddressObj->id_customer);
		if(empty($customerMappingObj)) {
			// @todo error
			return false;
		}

		// Address type must exist. Don't update type for existing address.
//		$erplyAddressTypeAry = self::getErplyDefaultAddressType();
//		if(empty($erplyAddressTypeAry)) {
//			// @todo error
//			return false;
//		}

		// ID
		$erplyAddressAry['addressID'] = $mappingObj->getErplyId();

		// Customer
		$erplyAddressAry['ownerID'] = $customerMappingObj->getErplyId();

		// Type. Dont update existing type.
//		$erplyAddressAry['typeID'] = $erplyAddressTypeAry['id'];

		// Street
		$erplyAddressAry['street'] = empty($prestaAddressObj->address2) ? $prestaAddressObj->address1 : $prestaAddressObj->address1.', '.$prestaAddressObj->address2;

		// City
		$erplyAddressAry['city'] = $prestaAddressObj->city;

		// Postal Code
		$erplyAddressAry['postalCode'] = $prestaAddressObj->postcode;

		// Country
		$erplyAddressAry['country'] = $prestaAddressObj->country;

		// Save
		ErplyFunctions::getErplyApi()->callApiFunction('saveAddress', $erplyAddressAry);
		return true;
	}

	/**
	 * Get array of Presta Addresses IDs that have changed since last sync.
	 * 
	 * @return array
	 */
	private static function getPrestaChangedAddressesIds()
	{
		if(is_null(self::$_prestaChangedAddressesIds))
		{
			// Init
			self::$_prestaChangedAddressesIds = array();

			$lastSyncTS = ErplyFunctions::getLastSyncTS('PRESTA_CUST_ADDR');
			$sql = '
SELECT `id_address` 
FROM '._DB_PREFIX_.'address 
WHERE 
	`id_customer` > 0 
	AND UNIX_TIMESTAMP(`date_upd`) > '.intval($lastSyncTS).' 
ORDER BY `date_upd` ASC';

			$addressesAry = Db::getInstance()->ExecuteS($sql);
			if(is_array($addressesAry)) {
				foreach($addressesAry as $addrAry)
				{
					self::$_prestaChangedAddressesIds[] = $addrAry['id_address'];
				}
			}
		}
		return self::$_prestaChangedAddressesIds;
	}
}

?>