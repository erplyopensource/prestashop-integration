<?php
include_once(dirname(__FILE__).'/Abstract.php');
include_once(dirname(__FILE__).'/../ErplyFunctions.class.php');

class Erply_Sync_Categories extends Erply_Sync_Abstract
{
	protected static $_erplyChangedCategories;
	protected static $_erplyChangedCategoriesIds;
	protected static $_prestaChangedCategories;
	protected static $_prestaChangedCategoriesIds;


	/**
	 * Sync all Categories both ways.
	 * 
	 * @return integer - total categories synchronized
	 */
	public static function syncAll($ignoreLastTimestamp=false)
	{
		$total = 0;
		$total += self::importAll($ignoreLastTimestamp);
		$total += self::exportAll($ignoreLastTimestamp);

		// Set now as last sync time.
		return $total;
	}

    /**
     * Import all ERPLY categories.
     * 
     * @return integer - Nr of categories imported.
     */
	public static function importAll($ignoreLastTimestamp=false)
	{
		ErplyFunctions::log('Start Category Import');

		$totalImported = 0;

		// Get object priority
		$objectPriority = self::getObjectPriority();

		// Get all ERPLY Categories chaned since last sync.
		$erplyChangedCategoriesAry = self::getErplyChangedCategories();

		// Get all Presta Categories changed since last sync.
		$prestaChangedCategoriesAry = self::getPrestaChangedCategories();

		foreach($erplyChangedCategoriesAry as $erplyCategory)
		{
			// Find mapping
			$mappingObj = self::getCategoryMapping('erply_id', $erplyCategory['productGroupID']);

			// Mapping exists (Category IS in sync), update Category.
			if(!is_null($mappingObj))
			{
				// Check if same Category has also changed in Presta
				if(in_array($mappingObj->getPrestaId(), self::$_prestaChangedCategoriesIds))
				{
					// Category has changed both in ERPLY and in Presta.
					// Check priority.
					if($objectPriority == 'ERPLY')
					{
						// Override Presta changes with ERPLY
						if(self::updatePrestaCategory($mappingObj->getPrestaId(), $erplyCategory))
						{
							$totalImported++;
						}
					}
					// else do nothing
				}
				else
				{
					// Category has not changed in Presta so update with ERPLY data.
					if(self::updatePrestaCategory($mappingObj->getPrestaId(), $erplyCategory))
					{
						$totalImported++;
					}
				}
			}
			// Mapping not found (Category NOT in sync), create new Category.
			else
			{
				// Create category
				if(self::createPrestaCategory($erplyCategory))
				{
					$totalImported++;
				}
			}
		}

		ErplyFunctions::log('End Category Import');

		return $totalImported;
	}

    /**
     * Export all Presta categories.
     * 
     * @return integer - Nr of categories exported.
     */
	public static function exportAll($ignoreLastTimestamp=false)
	{
		ErplyFunctions::log('Start Category Export.');

		$return = 0;

		// Get object priority
		$objectPriority = self::getObjectPriority();

		// Get all Presta Categories changed since last sync.
		$prestaChangedCategoriesAry = self::getPrestaChangedCategories();

		// Get all ERPLY Categories chaned since last sync.
		$erplyChangedCategoriesAry = self::getErplyChangedCategories();

		foreach($prestaChangedCategoriesAry as $prestaCategory)
		{
			// Find mapping
			$mappingObj = self::getCategoryMapping('local_id', $prestaCategory['id_category']);

			// Mapping found, Category IS in sync
			if(!is_null($mappingObj))
			{
				// Check if same Category has also changed in ERPLY
				if(in_array($mappingObj->getErplyId(), self::$_erplyChangedCategoriesIds))
				{
					// If object priority is Presta then export
					if($objectPriority == 'Presta')
					{
						// Update ERPLY category
						if(self::updateErplyCategory($prestaCategory, $mappingObj))
						{
							$return++;
						}
					}
					// else do nothing
				}
				// Category only changed in Presta, export.
				else
				{
					// Update ERPLY category
					if(self::updateErplyCategory($prestaCategory, $mappingObj))
					{
						$return++;
					}
				}
			}
			// Mapping not found, Category NOT in sync.
			else
			{
				// Create new ERPLY Category
				if(self::createErplyCategory($prestaCategory))
				{
					$return++;
				}
			}

			// Update last sync TS
			ErplyFunctions::setLastSyncTS('PRESTA_CATEGORIES', strtotime($prestaCategory['date_upd']));
		}

		ErplyFunctions::log('End Category Export.');

		return $return;
	}

	/**
	 * Update Presta Category with ERPLY category data.
	 * 
	 * @param integer | Category $prestaCategory
	 * @param array $erplyCategory
	 * @return Category - Presta Category
	 */
	private static function updatePrestaCategory($prestaCategory, $erplyCategory)
	{
		$localeId = ErplyFunctions::getPrestaLocaleId();

		// Load Presta Category if ID presented.
		if(!is_object($prestaCategory)) {
			$prestaCategory = new Category($prestaCategory);
		}

		ErplyFunctions::log('Updating Presta Category. Name: '.$prestaCategory->name[ $localeId ]);

		// Update Presta parent id. If not found then leave unchanged.
		if((int)$erplyCategory['parentGroupID'] > 0)
		{
			$parentMappingObj = self::getCategoryMapping('erply_id', $erplyCategory['parentGroupID']);
			if(!is_null($parentMappingObj))
			{
				$prestaCategory->id_parent = $parentMappingObj->getPrestaId();
			}
		}
		else
		{
			// In root category.
			$prestaCategory->id_parent = self::getPrestaRootCategoryId();
		}
		
		$prestaCategory->name[ $localeId ] = $erplyCategory['name'];
		$prestaCategory->active = (int)$erplyCategory['showInWebshop'];
		$prestaCategory->link_rewrite[ $localeId ] = Tools::link_rewrite( self::hideCategoryPosition( $prestaCategory->name[ $localeId ] ) );
		$prestaCategory->update();

		return $prestaCategory;
	}

	/**
	 * Create Presta category based on ERPLY data.
	 * 
	 * @param array $erplyCategory
	 * @return array
	 */
	private static function createPrestaCategory($erplyCategory)
	{
		ErplyFunctions::log('Creating Presta Category. Name: '.$erplyCategory['name']);
	
		$localeId = ErplyFunctions::getPrestaLocaleId();

		// Create new Presta Category.
		$prestaCategory = new Category(null, $localeId);

		// Find Presta parent id.
		$prestaParentId = self::getPrestaRootCategoryId();
		if((int)$erplyCategory['parentGroupID'] > 0)
		{
			$parentMappingObj = self::getCategoryMapping('erply_id', $erplyCategory['parentGroupID']);
			if(!is_null($parentMappingObj))
			{
				$prestaParentId = $parentMappingObj->local_id;
			}
		}
		$prestaCategory->id_parent = $prestaParentId;

		$name = self::prestaSafeName($erplyCategory['name']);
		$prestaCategory->name = self::createMultiLangField( $name );
		$prestaCategory->active = (int)$erplyCategory['showInWebshop'];

		$linkRewrite = $prestaCategory->name[ $localeId ];
		$linkRewrite = self::hideCategoryPosition( $linkRewrite );
		$linkRewrite = Tools::link_rewrite( $linkRewrite );
		$prestaCategory->link_rewrite = self::createMultiLangField( $linkRewrite );

//ErplyFunctions::debug('$prestaCategory', $prestaCategory); exit;

		if($prestaCategory->add())
		{
			// set category access
			$prestaCategory->addGroups(array(1));

			// Create mapping
			$mappingObj = self::createCategoryMapping($prestaCategory->id, $erplyCategory['productGroupID']);

			return array($prestaCategory, $mappingObj);
		}

		return false;
	}

	/**
	 * Update ERPLY product group.
	 * 
	 * @param array $prestaCategory
	 * @param ErplyMapping $mapping
	 * @return array - ERPLY product group
	 */
	private static function updateErplyCategory($prestaCategory, $mapping)
	{
		ErplyFunctions::log('Updating ERPLY Category. Name: '.$prestaCategory['name']);

		$erplyCategory = array();

		// ID
		$erplyCategory['productGroupID'] = $mapping->getErplyId();

		// Get ERPLY id for parent group.
		// Not in root
		if((int)$prestaCategory['id_parent'] !== self::getPrestaRootCategoryId())
		{
			$categoryMapping = self::getCategoryMapping('local_id', $prestaCategory['id_parent']);
			if(!is_null($categoryMapping))
			{
				// Parent category IN in sync.
				$erplyCategory['parentGroupID'] = $categoryMapping->getErplyId();
			}
			else
			{
				// Parent category IS NOT in sync.
				return false;
			}
		}
		// Category in root.
		else
		{
			$erplyCategory['parentGroupID'] = 0;
		}

		// Name
		$nameField = 'name'.strtoupper(ErplyFunctions::getErplyLocaleCode());
		$erplyCategory[$nameField] = $prestaCategory['name'];

		// Save
		if(! ErplyFunctions::getErplyApi()->saveProductGroup($erplyCategory)) {
			return false;
		}

		return $erplyCategory;
	}

	/**
	 * @param array $prestaCategory
	 * @return array - array( array $erplyCategory, ErplyMapping $mappingObj ) 
	 */
	private static function createErplyCategory($prestaCategory)
	{
		//we do not import the root category
		if((int) $prestaCategory['id_category'] === self::getPrestaRootCategoryId()) {
			ErplyFunctions::log('Create ERPLY Category skipped. Root category: '.$prestaCategory['name']);
			return false;
		}
		
		ErplyFunctions::log('Creating ERPLY Category. Name: '.$prestaCategory['name']);

		$erplyCategory = array();

		// Get ERPLY id for parent group.
		// Not in root
		if((int)$prestaCategory['id_parent'] !== self::getPrestaRootCategoryId())
		{
			$categoryMapping = self::getCategoryMapping('local_id', $prestaCategory['id_parent']);
			if(!is_null($categoryMapping))
			{
				// Parent category IN in sync.
				$erplyCategory['parentGroupID'] = $categoryMapping->getErplyId();
			}
			else
			{
				// Parent category IS NOT in sync.
				ErplyFunctions::log('Parent category not in sync. Create ERPLY Category canceled');
				return false;
			}
		}
		// Category in root.
		else
		{
			$erplyCategory['parentGroupID'] = 0;
		}

		// Name
		$nameField = 'name'.strtoupper(ErplyFunctions::getErplyLocaleCode());
		$erplyCategory[$nameField] = $prestaCategory['name'];

		// Create
		$erplyCategory['productGroupID'] = ErplyFunctions::getErplyApi()->saveProductGroup($erplyCategory);

		// Create mapping
		$mappingObj = self::createCategoryMapping($prestaCategory['id_category'], $erplyCategory['productGroupID']);

		return true;
	}

	/**
	 * Get all ERPLY products chaned since last sync.
	 * 
	 * @return array
	 */
	public static function getErplyChangedCategories()
	{
		// Load changed categories.
		if(is_null(self::$_erplyChangedCategories))
		{
			// Init changed categories
			self::$_erplyChangedCategories = array();
			self::$_erplyChangedCategoriesIds = array();

			$apiResp = ErplyFunctions::getErplyApi()->callApiFunction('getProductGroups');
			foreach($apiResp->getRecords() as $erplyCategory)
			{
				self::_makeErplyChangedCategoriesRecursive($erplyCategory);
			}
//			$erplyCategories = ErplyFunctions::getErplyApi()->getProductGroups();
//			if(is_array($erplyCategories)) {
//				foreach($erplyCategories as $erplyCategory)
//				{
//					self::_makeErplyChangedCategoriesRecursive($erplyCategory);
//				}
//			}

			// Update last sync TS
			ErplyFunctions::setLastSyncTS('ERPLY_CATEGORIES', $apiResp->getStatus('requestUnixTime'));
		}

		return self::$_erplyChangedCategories;
	}

	/**
	 * @param array $erplyCategory
	 * @param integer $parentCategoryId
	 * @return void
	 */
	private static function _makeErplyChangedCategoriesRecursive($erplyCategory, $parentCategoryId=null)
	{
		// Inport only changed categories.
		// We cannot check lastModified because when moving category
		// from one parent to another, lastModified does not get updated.
//		$lastSyncTS = ErplyFunctions::getLastSyncTS('ERPLY_CATEGORIES');
//		if($erplyCategory['added'] > $lastSyncTS || $erplyCategory['lastModified'] > $lastSyncTS)
//		{
			$category = $erplyCategory;
			unset($category['subGroups']);
			$category['parentGroupID'] = $parentCategoryId;
			self::$_erplyChangedCategories[] = $category;
			self::$_erplyChangedCategoriesIds[] = $category['productGroupID'];
//		}

		// Handle subcategories.
		if(!empty($erplyCategory['subGroups']) && is_array($erplyCategory['subGroups'])) {
			foreach($erplyCategory['subGroups'] as $subCategory)
			{
				self::_makeErplyChangedCategoriesRecursive($subCategory, $erplyCategory['productGroupID']);
			}
		}
	}

	/**
	 * Get array of Presta Categories that have changed since last sync.
	 * 
	 * @return array
	 */
	public static function getPrestaChangedCategories()
	{
		if(is_null(self::$_prestaChangedCategories))
		{
			// Init changed categories
			self::$_prestaChangedCategories = array();
			self::$_prestaChangedCategoriesIds = array();

			$lastSyncTS = ErplyFunctions::getLastSyncTS('PRESTA_CATEGORIES');
			$categoriesAry = self::_getPrestaCategoryChildren(self::getPrestaRootCategoryId(), true);
			foreach($categoriesAry as $category)
			{
				if(strtotime($category['date_upd']) > $lastSyncTS)
				{
					self::$_prestaChangedCategories[] = $category;
					self::$_prestaChangedCategoriesIds[] = $category['id_category'];
				}
			}
		}
		return self::$_prestaChangedCategories;
	}

	/**
	 * Get Presta category subcategoryes.
	 * 
	 * @param integer $parentId
	 * @param boolean $recursive
	 * @return array
	 */
	private static function _getPrestaCategoryChildren($parentId, $recursive=true)
	{
		$returnAry = array();
		$sql = '
SELECT *
FROM `'._DB_PREFIX_.'category` c
LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON c.`id_category` = cl.`id_category`
WHERE 
	cl.`id_lang` = '.intval(ErplyFunctions::getPrestaLocaleId()).'
	AND c.`id_parent` = '. intval($parentId) .'
ORDER BY cl.`name` ASC';

		$childrenAry = Db::getInstance()->ExecuteS($sql);
		foreach($childrenAry as $childAry)
		{
			$returnAry[] = $childAry;

			// Get subchildren recursively
			if($recursive === true)
			{
				$subchildrenAry = self::_getPrestaCategoryChildren( $childAry['id_category'], true );
				foreach($subchildrenAry as $subchildAry)
				{
					$returnAry[] = $subchildAry;
				}
			}
		}

		return $returnAry;
	}

	static private function hideCategoryPosition($name)
	{
		return preg_replace('/^[0-9]+\./', '', $name);
	}

	static private function getPrestaRootCategoryId()
	{
		//$val = (int)Context::getContext()->shop->getCategory(); //TODO
		$val = Configuration::get('ERPLY_PRESTA_ROOT_CATEGORY_ID');
		return !empty($val) ? (int) $val : 1;
	}

}

?>