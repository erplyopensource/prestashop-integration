<?php

// Check if any synchronizations have been executed previousely.
if((int)Configuration::get('ERP_CATEGORY_LAST_SYNC_MAX_TS') > 1
	|| (int)Configuration::get('PRE_CATEGORY_LAST_SYNC_MAX_TS') > 1
	|| (int)Configuration::get('ERP_PRODUCT_LAST_SYNC_MAX_TS') > 1
	|| (int)Configuration::get('PRE_PRODUCT_LAST_SYNC_MAX_TS') > 1)
{
	// Fetch data from ERPLY and set correct mappings to Presta database.
	include_once(_PS_MODULE_DIR_.'/erply/ErplyFunctions.class.php');
	include_once(_PS_MODULE_DIR_.'/erply/Sync.php');

	// Customers
	try {
		$apiRequestPageNr = 1;
		$apiRequest = array(
			  'recordsOnPage' => 100
			, 'pageNo' => $apiRequestPageNr
		);
		do {
			$apiResp = ErplyFunctions::getErplyApi()->callApiFunction('getCustomers', $apiRequest);
			foreach($apiResp->getRecords() as $apiRecord)
			{
				// Extract prestaCustomerID attribute.
				if(is_array($apiRecord['attributes'])) {
					foreach($apiRecord['attributes'] as $attr) {
						if($attr['attributeName'] == 'prestaCustomerID')
						{
							// Create new mapping and exit loop.
							$mapping = new ErplyMapping();
							$mapping->object_type = 'Customer';
							$mapping->local_id = $attr['attributeValue'];
							$mapping->erply_id = $apiRecord['customerID'];
							$mapping->add();
							break;
						}
					}
				}
			}

			// Set next page
			$apiRequestPageNr++;
			$apiRequest['pageNo'] = $apiRequestPageNr;
		}
		while((int)$apiResp->getRecordsCount() > 0);
	}
	catch(Erply_Exception $e) {
		print '<div class="alert error">Failed to import customer mappings! '.$e->getCode().': '.$e->getMessage().'</div>';
	}

	// CustomerAddresses
	try {
		$apiResp = ErplyFunctions::getErplyApi()->callApiFunction('getAddresses', $apiRequest);
		foreach($apiResp->getRecords() as $apiRecord)
		{
			// Extract prestaAddressID attribute.
			if(is_array($apiRecord['attributes'])) {
				foreach($apiRecord['attributes'] as $attr) {
					if($attr['attributeName'] == 'prestaAddressID')
					{
						// Create new mapping and exit loop.
						$mapping = new ErplyMapping();
						$mapping->object_type = 'CustomerAddress';
						$mapping->local_id = $attr['attributeValue'];
						$mapping->erply_id = $apiRecord['addressID'];
						$mapping->add();
						break;
					}
				}
			}
		}
	}
	catch(Erply_Exception $e) {
		print '<div class="alert error">Failed to import addresses mappings! '.$e->getCode().': '.$e->getMessage().'</div>';
	}

	/**
	 * Recursively find prestaCategoryID attribute from product groups.
	 * 
	 * @param array $apiRecord
	 * @return bool
	 */
	function erply_upgrade_0_1_3_map_category($apiRecord)
	{
		// Extract prestaCategoryID attribute.
		if(is_array($apiRecord['attributes'])) {
			foreach($apiRecord['attributes'] as $attr) {
				if($attr['attributeName'] == 'prestaCategoryID')
				{
					// Create new mapping and exit loop.
					$mapping = new ErplyMapping();
					$mapping->object_type = 'Category';
					$mapping->local_id = $attr['attributeValue'];
					$mapping->erply_id = $apiRecord['productGroupID'];
					$mapping->add();
					break;
				}
			}
		}

		// Go through subcategories.
		if(is_array($apiRecord['subGroups'])) {
			foreach($apiRecord['subGroups'] as $subCategory) {
				erply_upgrade_0_1_3_map_category($subCategory);
			}
		}

		return true;
	}

	// Categories
	try
	{
		$apiResp = ErplyFunctions::getErplyApi()->callApiFunction('getProductGroups');
		foreach($apiResp->getRecords() as $apiRecord)
		{
			erply_upgrade_0_1_3_map_category($apiRecord);
		}
	}
	catch(Erply_Exception $e) {
		print '<div class="alert error">Failed to import category mappings! '.$e->getCode().': '.$e->getMessage().'</div>';
	}

	// Products
	try {
		$apiRequestPageNr = 1;
		$apiRequest = array(
			  'recordsOnPage' => 100
			, 'pageNo' => $apiRequestPageNr
		);
		do {
			$apiResp = ErplyFunctions::getErplyApi()->callApiFunction('getProducts', $apiRequest);
			foreach($apiResp->getRecords() as $apiRecord)
			{
				// Extract prestaProductID attribute.
				if(is_array($apiRecord['attributes']))
				{
					// Map product
					$mapping = null;
					foreach($apiRecord['attributes'] as $attr) {
						if($attr['attributeName'] == 'prestaProductID')
						{
							// Create new mapping and exit loop.
							$mapping = new ErplyMapping();
							$mapping->object_type = 'Product';
							$mapping->local_id = $attr['attributeValue'];
							$mapping->erply_id = $apiRecord['productID'];
							$mapping->add();
							break;
						}
					}

					// Map images
					foreach($apiRecord['attributes'] as $attr) {
						if($attr['attributeName'] == 'prestaImages')
						{
							// Get product mapping and add image info.
							if(!is_null($mapping))
							{
								$mapping->info = array('images'=>unserialize($attr['attributeValue']));
								$mapping->update();
								break;
							}
						}
					}
				}
			}

			// Set next page
			$apiRequestPageNr++;
			$apiRequest['pageNo'] = $apiRequestPageNr;
		}
		while((int)$apiResp->getRecordsCount() > 0);
	}
	catch(Erply_Exception $e) {
		print '<div class="alert error">Failed to import product mappings! '.$e->getCode().': '.$e->getMessage().'</div>';
	}


	// Orders
	try {
		$apiRequestPageNr = 1;
		$apiRequest = array(
			  'recordsOnPage' => 100
			, 'pageNo' => $apiRequestPageNr
		);
		do {
			$apiResp = ErplyFunctions::getErplyApi()->callApiFunction('getSalesDocuments', $apiRequest);
			foreach($apiResp->getRecords() as $apiRecord)
			{
				// Extract prestaOrderID attribute.
				if(is_array($apiRecord['attributes'])) {
					foreach($apiRecord['attributes'] as $attr) {
						if($attr['attributeName'] == 'prestaOrderID')
						{
							// Create new mapping and exit loop.
							$mapping = new ErplyMapping();
							$mapping->object_type = 'SalesInvoice';
							$mapping->local_id = $attr['attributeValue'];
							$mapping->erply_id = $apiRecord['id'];
							$mapping->add();
							break;
						}
					}
				}
			}

			// Set next page
			$apiRequestPageNr++;
			$apiRequest['pageNo'] = $apiRequestPageNr;
		}
		while((int)$apiResp->getRecordsCount() > 0);
	}
	catch(Erply_Exception $e) {
		print '<div class="alert error">Failed to import addresses mappings! '.$e->getCode().': '.$e->getMessage().'</div>';
	}

	print '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />'.$this->l('System upgraded').'</div>';
}
else
{
	print '<div class="alert error">Cannot use converter because one or more synchronizations have been executed since module upgrade.</div>';
}

?>