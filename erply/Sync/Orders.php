<?php
include_once(dirname(__FILE__).'/Abstract.php');
include_once(dirname(__FILE__).'/Customers.php');
include_once(dirname(__FILE__).'/Products.php');
include_once(dirname(__FILE__).'/../ErplyFunctions.class.php');
include_once(dirname(__FILE__).'/../Presta/Order.php');

class Erply_Sync_Orders extends Erply_Sync_Abstract
{
	protected static $defaultErplyOrderType = 'INVWAYBILL';

	protected static $_prestaChangedOrdersIds;

	/**
	 * Sync. We only export orders.
	 * 
	 * @return integer - total objects synchronized
	 */
	public static function syncAll($ignoreLastTimestamp=false)
	{
		$total = 0;
		$total += self::exportAll( $ignoreLastTimestamp );

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
		ErplyFunctions::log('Start Order Export.');

		$return = 0;
		$prestaLocaleId = ErplyFunctions::getPrestaLocaleId();

		foreach(self::getPrestaChangedOrdersIds() as $prestaOrderId)
		{
			// Get Presta object
			$prestaOrderObj = new Erply_Presta_Order($prestaOrderId);

			// Find mapping
			$mappingObj = self::getSalesInvoiceMapping('local_id', $prestaOrderId);

			// Mapping found, Order IS in sync
			if(!is_null($mappingObj))
			{
				// Update ERPLY object
				if(self::updateErplyOrder($prestaOrderObj, $mappingObj))
				{
					$return++;
				}
			}
			// Mapping not found, Address NOT in sync.
			else
			{
				// Create new ERPLY object
				if(self::createErplyOrder($prestaOrderObj))
				{
					$return++;
				}
			}

			// Save now as last sync time for orders
			ErplyFunctions::setLastSyncTS('PRESTA_ORDER', strtotime($prestaOrderObj->date_upd));
		}

		ErplyFunctions::log('End Order Export. '.$return.' orders exported.');

		return $return;
	}

	/**
	 * Creates new ERPLY object.
	 * 
	 * @param Erply_Presta_Order $prestaOrderObj
	 * @return array - array( array $erplyAddressAry, ErplyMapping $mappingObj )
	 */
	protected static function createErplyOrder($prestaOrderObj)
	{
		return self::updateErplyOrder($prestaOrderObj);
	}

	/**
	 * Update ERPLY object.
	 * 
	 * @param Erply_Presta_Order $prestaOrderObj
	 * @param ErplyMapping $mappingObj
	 * @return boolean
	 */
	protected static function updateErplyOrder($prestaOrderObj, $mappingObj=null)
	{
		if(!is_null($mappingObj)) {
			ErplyFunctions::log('Updating ERPLY Order. ERPLY ID: '.$mappingObj->getErplyId());
		} else {
			ErplyFunctions::log('Creating ERPLY Order. Presta ID: '.$prestaOrderObj->id);
		}

		$prestaLocaleId = ErplyFunctions::getPrestaLocaleId();
		$erplyOrderAry = array();

		// Customer mapping must exist
		$customerMappingObj = self::getCustomerMapping('local_id', $prestaOrderObj->id_customer);
		if(empty($customerMappingObj)) {
			// @todo error
			return false;
		}
		$billingAddressMappingObj = self::getCustomerAddressMapping('local_id', $prestaOrderObj->id_address_invoice);
		$shippingAddressMappingObj = self::getCustomerAddressMapping('local_id', $prestaOrderObj->id_address_delivery);
		if(empty($billingAddressMappingObj) || empty($shippingAddressMappingObj)) {
			// @todo error
			return false;
		}

		// ID
		if(!is_null($mappingObj)) {
			$erplyOrderAry['id'] = $mappingObj->getErplyId();
		}

		// type
		$erplyOrderAry['type'] = self::$defaultErplyOrderType;

		// currencyCode
		$currency = self::getPrestaCurrency( $prestaOrderObj->id_currency );
		if(!is_null($currency)) {
			$erplyOrderAry['currencyCode'] =  $currency->iso_code;
		}

		// Data and time
		$erplyOrderAry['date'] = date('Y-m-d', strtotime($prestaOrderObj->date_add));
		$erplyOrderAry['time'] = date('H:m:s', strtotime($prestaOrderObj->date_add));

		// Customer
		$erplyOrderAry['customerID'] = $customerMappingObj->getErplyId();
		$erplyOrderAry['payerID'] = $customerMappingObj->getErplyId();

		// Address
		$erplyOrderAry['addressID'] = $shippingAddressMappingObj->getErplyId();
		$erplyOrderAry['payerAddressID'] = $billingAddressMappingObj->getErplyId();

		// @todo invoiceState

		// @todo paymentType

		// @todo paymentStatus

		// @todo paymentInfo

		// internalNotes
		$erplyOrderAry['internalNotes'] = 'Prestashop Invoice Number: '.$prestaOrderObj->invoice_number;

		/*
		 * Products 
		 */

		$nr = 1;
		$prestaItemsAry = $prestaOrderObj->getItems();
		if(is_array($prestaItemsAry)) {
			foreach($prestaItemsAry as $prestaOrderItemAry)
			{
				// productID
				$prestaProductMappingObj = self::getProductMapping('local_id', $prestaOrderItemAry['product_id']);
				if(!is_null($prestaProductMappingObj)) {
					$erplyOrderAry['productID'.$nr] = $prestaProductMappingObj->getErplyId();
				}
	
				// itemName
				$erplyOrderAry['itemName'.$nr] = $prestaOrderItemAry['product_name'];
	
				// vatrateID
				$rate = (($prestaOrderItemAry['total_price_tax_incl'] / $prestaOrderItemAry['total_price_tax_excl']) - 1)*100;
				$erplyVatrate = self::getErplyVatrateByRate($rate);
				if(is_null($erplyVatrate)) $erplyVatrate = self::getErplyVatrateByRate(0);
				$erplyOrderAry['vatrateID'.$nr] = $erplyVatrate['id'];
	
				// amount
				$erplyOrderAry['amount'.$nr] = $prestaOrderItemAry['product_quantity'];
	
				// price
				$erplyOrderAry['price'.$nr] = $prestaOrderItemAry['product_price'];
	
				// Discount. Multiple discounts may have been applied.
				$erplyOrderAry['discount'.$nr] = $prestaOrderItemAry['discount'];
	
				$nr++;
			}
		}

		// Add shipping as last product
		if($prestaOrderObj->total_shipping)
		{
			// Item name
			$erplyOrderAry['itemName'.$nr] = 'Shipping';

			// Vatrate ID
			$rate = (($prestaOrderObj->total_shipping_tax_incl / $prestaOrderObj->total_shipping_tax_excl) - 1)*100;
			$erplyVatrateAry = self::getErplyVatrateByRate( $rate );
			if(is_null($erplyVatrateAry))
			{
				$erplyVatrateAry = self::getErplyVatrateByRate(0);
			}
			$erplyOrderAry['vatrateID'.$nr] = $erplyVatrateAry['id'];

			// Amount
			$erplyOrderAry['amount'.$nr] = 1;

			// Price
			$erplyOrderAry['price'.$nr] = round($prestaOrderObj->total_shipping / (1 + floatval($prestaOrderObj->carrier_tax_rate) / 100), 2);

			// Discount
			$erplyOrderAry['discount'.$nr] = 0;

			$nr++;
		}

		// Save
		$apiResp = ErplyFunctions::getErplyApi()->callApiFunction('saveSalesDocument', $erplyOrderAry);

		if(is_null($mappingObj))
		{
			// New document created. Create mapping.
			$mappingObj = new ErplyMapping();
			$mappingObj->object_type = 'SalesInvoice';
			$mappingObj->local_id = $prestaOrderObj->id;

			$apiRespFirstRecord = $apiResp->getFirstRecord();
			$mappingObj->erply_id = $apiRespFirstRecord['invoiceID'];

			if($mappingObj->add())
			{
				return array($erplyOrderAry, $mappingObj);
			}
			else
			{
				return false;
			}
		}
		else
		{
			// Existing object updated. Return ERPLY object.
			return $erplyOrderAry;
		}

		return false;
	}

	/**
	 * Get array of Presta Orders IDs that have changed since last sync.
	 * 
	 * @return array
	 */
	private static function getPrestaChangedOrdersIds()
	{
		if(is_null(self::$_prestaChangedOrdersIds))
		{
			// Init
			self::$_prestaChangedOrdersIds = array();

			// Get last sync time
			$lastSyncTS = ErplyFunctions::getLastSyncTS('PRESTA_ORDER');
			$sql = '
SELECT `id_order` 
FROM `'._DB_PREFIX_.'orders` 
WHERE 
	`invoice_number` > 0
	AND UNIX_TIMESTAMP(`date_upd`) > '.intval($lastSyncTS).' 
ORDER BY `date_upd` ASC';

			$ordersAry = Db::getInstance()->ExecuteS($sql);
			if(is_array($ordersAry)) {
				foreach($ordersAry as $orderAry)
				{
					self::$_prestaChangedOrdersIds[] = $orderAry['id_order'];
				}
			}
		}

		return self::$_prestaChangedOrdersIds;
	}
}

?>