<?php
include_once(dirname(__FILE__).'/Abstract.php');
include_once(dirname(__FILE__).'/../ErplyFunctions.class.php');

class Erply_Sync_DeletedItems extends Erply_Sync_Abstract
{

	/**
	 * Delete products from ERPLY that have been deleted from Presta.
	 * 
	 * @return boolean
	 */
	public static function deleteProductsFromErply()
	{
	}

	/**
	 * Delete products from Presta that have been deleted from ERPLY.
	 * 
	 * @return unknown_type
	 */
	public static function deleteProductsFromPresta()
	{
		ErplyFunctions::log('Start Product Import.');

		// What to do with items deleted from erply.
		// 1 - delete mappings only
		// 2 - delete mappings and data
		$doWhat = Configuration::get('ERPLY_WHEN_DELETED_FROM_ERPLY');

		$erplyLastSyncTS = ErplyFunctions::getLastSyncTS('ERPLY_PRODUCTS');
		$apiRequestPageNr = 1;
		$apiRequest = array(
			  'tableName' => 'product'
			, 'addedFrom' => $erplyLastSyncTS
			, 'orderByDir' => 'asc'
			, 'recordsOnPage' => self::$erplyApiRecordsOnPage
			, 'pageNo' => $apiRequestPageNr
		);

		do
		{
			$apiResp = ErplyFunctions::getErplyApi()->callApiFunction('getUserOperationsLog', $apiRequest);
			foreach($apiResp->getRecords() as $apiRecord) {
				if($apiRecord['operation'] = 'delete')
				{
					
				}
			}
		}
		while(!empty($erplyProducts));

		ErplyFunctions::log('End Product Import. Imported '.$return.' products.');

		return true;
	}
}

?>