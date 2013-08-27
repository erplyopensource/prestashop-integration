<?php
include_once(dirname(__FILE__).'/ErplyFunctions.class.php');
include_once(dirname(__FILE__).'/ErplyMapping.php');

class Erply_Sync
{
	/**
	 * Import all ERPLY data
	 * 
	 * @param bool $ignoreLastTimestamp
	 * @return boolean
	 */
	public static function importAll($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/CustomerGroups.php');
		include_once(dirname(__FILE__).'/Sync/Customers.php');
		include_once(dirname(__FILE__).'/Sync/CustomerAddresses.php');
		include_once(dirname(__FILE__).'/Sync/Categories.php');
		include_once(dirname(__FILE__).'/Sync/Products.php');
		include_once(dirname(__FILE__).'/Sync/Orders.php');
		include_once(dirname(__FILE__).'/Sync/OrderHistory.php');

		try
		{
			Erply_Sync_Categories::importAll( $ignoreLastTimestamp );
			Erply_Sync_Products::importAll( $ignoreLastTimestamp );
			Erply_Sync_CustomerGroups::importAll( $ignoreLastTimestamp );
			Erply_Sync_Customers::importAll( $ignoreLastTimestamp );
			Erply_Sync_CustomerAddresses::importAll( $ignoreLastTimestamp );
			Erply_Sync_Orders::importAll( $ignoreLastTimestamp );
			Erply_Sync_OrderHistory::importAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	/**
	 * Export all Presta data
	 * 
	 * @param bool $ignoreLastTimestamp
	 * @return boolean
	 */
	public static function exportAll($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/CustomerGroups.php');
		include_once(dirname(__FILE__).'/Sync/Customers.php');
		include_once(dirname(__FILE__).'/Sync/CustomerAddresses.php');
		include_once(dirname(__FILE__).'/Sync/Categories.php');
		include_once(dirname(__FILE__).'/Sync/Products.php');
		include_once(dirname(__FILE__).'/Sync/Orders.php');
		include_once(dirname(__FILE__).'/Sync/OrderHistory.php');
		try
		{
			Erply_Sync_Categories::exportAll( $ignoreLastTimestamp );
			Erply_Sync_Products::exportAll( $ignoreLastTimestamp );
			Erply_Sync_CustomerGroups::exportAll( $ignoreLastTimestamp );
			Erply_Sync_Customers::exportAll( $ignoreLastTimestamp );
			Erply_Sync_CustomerAddresses::exportAll( $ignoreLastTimestamp );
			Erply_Sync_Orders::exportAll( $ignoreLastTimestamp );
			Erply_Sync_OrderHistory::exportAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	/**
	 * Sync both ways.
	 * 
	 * @param bool $ignoreLastTimestamp
	 * @return boolean
	 */
	public static function syncAll($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/CustomerGroups.php');
		include_once(dirname(__FILE__).'/Sync/Customers.php');
		include_once(dirname(__FILE__).'/Sync/CustomerAddresses.php');
		include_once(dirname(__FILE__).'/Sync/Categories.php');
		include_once(dirname(__FILE__).'/Sync/Products.php');
		include_once(dirname(__FILE__).'/Sync/Orders.php');
		include_once(dirname(__FILE__).'/Sync/OrderHistory.php');

		try
		{
			Erply_Sync_Categories::syncAll( $ignoreLastTimestamp );
			Erply_Sync_Products::syncAll( $ignoreLastTimestamp );
			Erply_Sync_CustomerGroups::syncAll( $ignoreLastTimestamp );
			Erply_Sync_Customers::syncAll( $ignoreLastTimestamp );
			Erply_Sync_CustomerAddresses::syncAll( $ignoreLastTimestamp );
			Erply_Sync_Orders::syncAll( $ignoreLastTimestamp );
			Erply_Sync_OrderHistory::syncAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	public static function importCustomers($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/CustomerGroups.php');
		include_once(dirname(__FILE__).'/Sync/Customers.php');
		include_once(dirname(__FILE__).'/Sync/CustomerAddresses.php');

		try
		{
			Erply_Sync_CustomerGroups::importAll( $ignoreLastTimestamp );
			Erply_Sync_Customers::importAll( $ignoreLastTimestamp );
			Erply_Sync_CustomerAddresses::importAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	public static function exportCustomers($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/CustomerGroups.php');
		include_once(dirname(__FILE__).'/Sync/Customers.php');
		include_once(dirname(__FILE__).'/Sync/CustomerAddresses.php');

		try
		{
			Erply_Sync_CustomerGroups::exportAll( $ignoreLastTimestamp );
			Erply_Sync_Customers::exportAll( $ignoreLastTimestamp );
			Erply_Sync_CustomerAddresses::exportAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	public static function syncCustomers($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/CustomerGroups.php');
		include_once(dirname(__FILE__).'/Sync/Customers.php');
		include_once(dirname(__FILE__).'/Sync/CustomerAddresses.php');

		try
		{
			Erply_Sync_CustomerGroups::syncAll( $ignoreLastTimestamp );
			Erply_Sync_Customers::syncAll( $ignoreLastTimestamp );
			Erply_Sync_CustomerAddresses::syncAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	public static function importCategories($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/Categories.php');

		try
		{
			Erply_Sync_Categories::importAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	public static function exportCategories($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/Categories.php');

		try
		{
			Erply_Sync_Categories::exportAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	public static function syncCategories($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/Categories.php');

		try
		{
			Erply_Sync_Categories::syncAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	public static function importProducts($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/Products.php');

		try
		{
			Erply_Sync_Products::importAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	public static function exportProducts($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/Products.php');

		try
		{
			Erply_Sync_Products::exportAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	public static function syncProducts($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/Products.php');

		try
		{
			Erply_Sync_Products::syncAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	public static function importSales()
	{
		return true;
	}

	public static function exportSales($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/Orders.php');
		include_once(dirname(__FILE__).'/Sync/OrderHistory.php');

		try
		{
			Erply_Sync_Orders::exportAll( $ignoreLastTimestamp );
			Erply_Sync_OrderHistory::exportAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	public static function syncSales($ignoreLastTimestamp=false)
	{
		include_once(dirname(__FILE__).'/Sync/Orders.php');
		include_once(dirname(__FILE__).'/Sync/OrderHistory.php');

		try
		{
			Erply_Sync_Orders::syncAll( $ignoreLastTimestamp );
			Erply_Sync_OrderHistory::syncAll( $ignoreLastTimestamp );
		}
		catch(Erply_Exception $e)
		{
			echo '<div class="alert error">ERROR '.$e->getCode().': '.$e->getMessage().'</div>';
		}

		return true;
	}

	public static function resetLastImportTimestamps()
	{
		ErplyFunctions::setLastSyncTS('ERPLY_CATEGORIES', 0);
		ErplyFunctions::setLastSyncTS('ERPLY_PRODUCTS', 0);
		ErplyFunctions::setLastSyncTS('ERPLY_CUST_GROUPS', 0);
		return true;
	}

	public static function resetLastExportTimestamps()
	{
		ErplyFunctions::setLastSyncTS('PRESTA_CATEGORIES', 0);
		ErplyFunctions::setLastSyncTS('PRESTA_PRODUCTS', 0);
		ErplyFunctions::setLastSyncTS('PRESTA_CUST_GROUPS', 0);
		ErplyFunctions::setLastSyncTS('PRESTA_CUSTOMERS', 0);
		ErplyFunctions::setLastSyncTS('PRESTA_CUST_ADDR', 0);
		ErplyFunctions::setLastSyncTS('PRESTA_ORDER', 0);
		return true;
	}
}

?>