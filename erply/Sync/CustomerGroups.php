<?php
include_once(dirname(__FILE__).'/Abstract.php');
include_once(dirname(__FILE__).'/../ErplyFunctions.class.php');

class Erply_Sync_CustomerGroups extends Erply_Sync_Abstract
{
	private static $_erplyChangedGroupsIds = array();
	private static $_prestaChangedGroupsIds;


	public static function syncAll($ignoreLastTimestamp=false)
	{
		$total = 0;
		$total += self::importAll( $ignoreLastTimestamp );
		$total += self::exportAll( $ignoreLastTimestamp );
		return $total;
	}

	/**
	 * Import ERPLY object to Presta that have changed since last sync.
	 * 
	 * @return integer - nr of object imported.
	 */
	public static function importAll($ignoreLastTimestamp=false)
	{
		ErplyFunctions::log('Start Customer Group Import.');

		if($ignoreLastTimestamp == true)
		{
			Configuration::set('ERPLY_ERPLY_CUST_GROUPS_LS_TS', 0);
		}

		$return = 0;
		$objectPriority = self::getObjectPriority();

		// Load all groups ids changed since last sync.
		self::getPrestaChangedGroupsIds();

		// Get ERPLY last changed groups.
		$apiResp = ErplyFunctions::getErplyApi()->callApiFunction('getCustomerGroups', array(
			'changedSince' => ErplyFunctions::getLastSyncTS('ERPLY_CUST_GROUPS')
		));
		foreach($apiResp->getRecords() as $erplyGroupAry)
		{
			// Add to cache
			self::$_erplyChangedGroupsIds[] = $erplyGroupAry['customerGroupID'];

			// Get Mapping
			$mappingObj = self::getCustomerGroupMapping('erply_id', $erplyGroupAry['customerGroupID']);

			if(!is_null($mappingObj))
			{
				// Object in sync. Update only if not changed in Presta or priority is ERPLY
				if( !self::prestaGroupHasChanged( $mappingObj->getPrestaId() ) || $objectPriority == 'ERPLY' )
				{
					// Update Presta Group
					$prestaGroupObj = self::getPrestaGroup( $mappingObj->getPrestaId() );
					if(($resp = self::updatePrestaGroup( $prestaGroupObj, $erplyGroupAry, $mappingObj )))
					{
						list($prestaGroupObj, $mappingObj) = $resp;
						$return++;
					}
				}
			}
			else
			{
				// Object NOT in sync. Create Presta object.
				if(($resp = self::createPrestaGroup($erplyGroupAry)))
				{
					list($prestaGroupAry, $mappingObj) = $resp;
					$return++;
				}
			}

			// Update last sync TS
			$newSyncTS = !empty($erplyGroupAry['lastModified']) ? $erplyGroupAry['lastModified'] : $erplyGroupAry['added'];
			ErplyFunctions::setLastSyncTS('ERPLY_CUST_GROUPS', $newSyncTS);
		}

		ErplyFunctions::log('End Customer Group Import.');

		return $return;
	}

	/**
	 * Export all Customer Groups to ERPLY that have chaned or created since last sync.
	 * 
	 * @param bool $ignoreLastTimestamp
	 * @return integer - nr of groups exported
	 */
	public static function exportAll($ignoreLastTimestamp=false)
	{
		ErplyFunctions::log('Start Customer Group Export.');

		if($ignoreLastTimestamp == true)
		{
			Configuration::set('ERPLY_PRESTA_CUST_GROUPS_LS_TS', 0);
		}

		$return = 0;
		$objectPriority = self::getObjectPriority();

		foreach(self::getPrestaChangedGroupsIds() as $prestaGroupId)
		{
			// Unset
			$prestaGroupObj = null;

			if(($mappingObj = self::getCustomerGroupMapping('local_id', $prestaGroupId)))
			{
				// Object is in sync.
				if(!self::erplyGroupHasChanged($mappingObj->getErplyId()) || $objectPriority == 'Presta')
				{
					// ERPLY object not changed or priority is Presta. Update ERPLY object.
					$prestaGroupObj = self::getPrestaGroup( $prestaGroupId );
					if(self::updateErplyGroup( $prestaGroupObj, $mappingObj ))
					{
						$return++;
					}
				}
			}
			else
			{
				// Object not in sync. Create in ERPLY.
				$prestaGroupObj = self::getPrestaGroup( $prestaGroupId );
				if(($resp = self::createErplyGroup( $prestaGroupObj )))
				{
					$return++;
				}
			}

			// Update last sync TS
			if(!is_null($prestaGroupObj))
			{
				ErplyFunctions::setLastSyncTS('PRESTA_CUST_GROUPS', strtotime($prestaGroupObj->date_upd));
			}
		}

		ErplyFunctions::log('End Customer Group Export.');

		return $return;
	}

	/**
	 * @param Group $prestaGroupObj
	 * @param array $erplyGroupAry
	 * @param ErplyMapping $mappingObj
	 * @return array
	 */
	public static function updatePrestaGroup($prestaGroupObj, $erplyGroupAry, $mappingObj)
	{
		ErplyFunctions::log('Updating Presta Customer Group. Group: '.$erplyGroupAry['name']);

		$prestaLocaleId = ErplyFunctions::getPrestaLocaleId();

		// Name
		$name = self::prestaSafeName( $erplyGroupAry['name'] );
		$prestaGroupObj->name[ $prestaLocaleId ] = $name;

		// Save
		if($prestaGroupObj->update())
		{
			return array($prestaGroupObj, $mappingObj);
		}

		return false;
	}

	/**
	 * @param array $erplyGroupAry
	 * @return array
	 */
	public static function createPrestaGroup($erplyGroupAry)
	{
		ErplyFunctions::log('Creating Presta Customer Group. Group name: '.$erplyGroupAry['name']);

		$prestaGroupObj = new Group();

		// Name
		$name = self::prestaSafeName( $erplyGroupAry['name'] );
		$prestaGroupObj->name = self::createMultiLangField( $name );

		// Reduction
		$prestaGroupObj->reduction = 0;

		// Price display method
		$prestaGroupObj->price_display_method = Group::getDefaultPriceDisplayMethod();

		// Save
		if($prestaGroupObj->add())
		{
			// Create mapping.
			$mappingObj = new ErplyMapping();
			$mappingObj->object_type = 'CustomerGroup';
			$mappingObj->erply_id = $erplyGroupAry['customerGroupID'];
			$mappingObj->local_id = $prestaGroupObj->id;
			$mappingObj->add();

			return array($prestaGroupObj, $mappingObj);
		}

		return false;
	}

	/**
	 * @param Group $prestaGroupObj
	 * @param ErplyMapping $mappingObj
	 * @return boolean
	 */
	protected static function updateErplyGroup($prestaGroupObj, $mappingObj)
	{
		$prestaLocaleId = ErplyFunctions::getPrestaLocaleId();
		$erplyGroupAry = array();

		ErplyFunctions::log('Updating ERPLY Customer Group. Group: '.$prestaGroupObj->name[ $prestaLocaleId ]);

		// ID
		$erplyGroupAry['customerGroupID'] = $mappingObj->getErplyId();

		// Name
		$nameField = 'name'.strtoupper( ErplyFunctions::getErplyLocaleCode() );
		$erplyGroupAry[$nameField] = $prestaGroupObj->name[ $prestaLocaleId ];

		// Save
		try {
			ErplyFunctions::getErplyApi()->callApiFunction('saveCustomerGroup', $erplyGroupAry);
			return true;
		}
		catch(Erply_Exception $e) {
			if($e->getCode() != '1002') {
				return false;
			} else {
				throw $e;
			}
		}
	}

	/**
	 * @param Group $prestaGroupObj
	 * @return array - array ( array $erplyGroupAry, ErplyMapping $mappingObj )
	 */
	protected static function createErplyGroup($prestaGroupObj)
	{

		$erplyGroupAry = array();
		$prestaLocaleId = ErplyFunctions::getPrestaLocaleId();

		ErplyFunctions::log('Creating ERPLY Customer Group. Presta group: '.$prestaGroupObj->name[ $prestaLocaleId ]);

		// Name
		$erplyGroupAry['name'] = $prestaGroupObj->name[ $prestaLocaleId ];

		try {
			// Save
			$apiResp = ErplyFunctions::getErplyApi()->callApiFunction('saveCustomerGroup', $erplyGroupAry);
			$firstRecordAry = $apiResp->getFirstRecord();
			$erplyGroupAry['customerGroupID'] = $firstRecordAry['customerGroupID'];

			// Create mapping.
			$mappingObj = new ErplyMapping();
			$mappingObj->object_type = 'CustomerGroup';
			$mappingObj->local_id = $prestaGroupObj->id;
			$mappingObj->erply_id = $erplyGroupAry['customerGroupID'];
			$mappingObj->add();

			return array($erplyGroupAry, $mappingObj);
		}
		catch(Erply_Exception $e) {
			if($e->getCode() != '1002') {
				return false;
			} else {
				throw $e;
			}
		}
	}

	/**
	 * @param integer $lastModifiedTS - unix_timestamp
	 * @return array
	 */
	private static function getErplyChangedGroupsIds($lastModifiedTS)
	{
		return self::$_erplyChangedGroupsIds;
	}

	/**
	 * Check if ERPLY Group has changed since last sync.
	 * 
	 * @param integer $erplyGroupId
	 * @return boolean
	 */
	private static function erplyGroupHasChanged($erplyGroupId)
	{
		return array_key_exists($erplyGroupId, self::$_erplyChangedGroupsIds);
	}

	/**
	 * @return array
	 */
	private static function getPrestaChangedGroupsIds()
	{
		if(is_null(self::$_prestaChangedGroupsIds))
		{
			// Init
			self::$_prestaChangedGroupsIds = array();

			// Load groups that have changed.
			$lastSyncTS = ErplyFunctions::getLastSyncTS('PRESTA_CUST_GROUPS');
			$sql = '
SELECT g.`id_group` 
FROM `'._DB_PREFIX_.'group` g 
WHERE UNIX_TIMESTAMP(g.`date_upd`) > '.$lastSyncTS.' 
ORDER BY g.`date_upd` ASC';

			$ary = Db::getInstance()->ExecuteS($sql);
			if($ary) {
				foreach($ary as $item)
				{
					self::$_prestaChangedGroupsIds[] = $item['id_group'];
				}
			}
		}

		return self::$_prestaChangedGroupsIds;
	}

	/**
	 * Check if Presta Group has changed since last sync.
	 * 
	 * @param integer $prestaGroupId
	 * @return boolean
	 */
	private static function prestaGroupHasChanged($prestaGroupId)
	{
		return array_key_exists($prestaGroupId, self::$_prestaChangedGroupsIds);
	}

	/**
	 * @param integer $groupId
	 * @return Group
	 */
	private static function getPrestaGroup($groupId)
	{
		foreach(Group::getGroups( ErplyFunctions::getPrestaLocaleId() ) as $prestaGroupAry) {
			if($prestaGroupAry['id_group'] == $groupId)
			{
				return new Group( $groupId );
			}
		}
		return null;
	}

	public static function prestaSafeName($name, $maxLength=32)
	{
		$search = array('<','>',';','=','@','#','{','}');
		$replace = '_';
		$name = str_replace($search, $replace, $name);
		return substr($name, 0, $maxLength);
	}
}

?>