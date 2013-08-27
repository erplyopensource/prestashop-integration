<?php

if (!defined('_CAN_LOAD_FILES_'))
	exit;

class ErplyMapping extends ObjectModel
{
	public		$id;
	
	/** @var integer Mapping id */
	public		$erply_mapping_id;
	
	/** @var string Erply Customer Code */
	public		$erply_code;
	
	/** @var string Object type */
	public		$object_type;
	
	/** @var integer Local object id */
	public		$local_id;
	
	/** @var integer ERPLY object id */
	public		$erply_id;
	
	/** @var text Validate */
	public 		$info;
	
 	protected 	$fieldsRequired = array('erply_code', 'object_type', 'local_id', 'erply_id');
	protected 	$table = 'erply_mapping';
	protected 	$identifier = 'erply_mapping_id';


	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return integer
	 */
	public function getPrestaId()
	{
		return $this->local_id;
	}

	/**
	 * @return integer
	 */
	public function getErplyId()
	{
		return $this->erply_id;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getInfo($key=null)
	{
		if(is_string($this->info))
		{
			$this->info = unserialize($this->info);
		}
		if(!is_array($this->info))
		{
			$this->info = array();
		}
		if(is_null($key)) {
			return $this->info;
		}
		return isset($this->info[ $key ]) ? $this->info[ $key ] : null;
	}

	/**
	 * @param string | array $key
	 * @param mixed $val
	 * @return ErplyMapping
	 */
	public function setInfo($key, $val=null)
	{
		if(is_array($key))
		{
			$this->info = $key;
			return $this;
		}
		if(!is_array($this->info))
		{
			$this->info = array();
		}
		$this->info[ $key ] = $val;
		return $this;
	}

	/**
	 * @param array
	 */
	public function getFields()
	{
		parent::validateFields();
		if (isset($this->id))
			$fields['erply_mapping_id'] = intval($this->id);
		$fields['erply_code'] = strval($this->erply_code);
		$fields['object_type'] = strval($this->object_type);
		$fields['local_id'] = intval($this->local_id);
		$fields['erply_id'] = intval($this->erply_id);
		$fields['info'] = strval($this->info);
		return $fields;
	}

	/**
	 * @return boolean
	 */
	public function add()
	{
		if(empty($this->erply_code)) {
			$this->erply_code = ErplyFunctions::getErplyCustomerCode();
		}
		$this->info = serialize($this->info);
		return parent::add();
	}

	/**
	 * @return bool
	 */
	public function update()
	{
		$this->info = serialize($this->info);
		return parent::update();
	}

	/*
	 * Static methods
	 */


	/**
	 * Get Mapping
	 *
	 * @param string $objectType
	 * @param string $fieldName - local_id or erply_id
	 * @param integer $fieldValue
	 * @return ErplyMapping
	 */
	public static function getMapping($objectType, $fieldName, $fieldValue)
	{
		$sql = '
SELECT 
	* 
FROM 
	`'._DB_PREFIX_.'erply_mapping` 
WHERE 
	`erply_code` = \''.ErplyFunctions::getErplyCustomerCode().'\'
	AND `object_type` = \''.$objectType.'\' 
	AND `'.$fieldName.'` = '.$fieldValue.' 
';

		$row = Db::getInstance()->getRow($sql);
		if(is_array($row))
		{
			$resp = new ErplyMapping();
			$resp->id = $row['erply_mapping_id'];
			$resp->erply_mapping_id = $row['erply_mapping_id'];
			$resp->erply_code = $row['erply_code'];
			$resp->object_type = $row['object_type'];
			$resp->local_id = $row['local_id'];
			$resp->erply_id = $row['erply_id'];
			$resp->info = unserialize($row['info']);
			return $resp;
		}

		return null;
	}

	public static function deleteByObjectType($objectType)
	{
		$table = _DB_PREFIX_.'erply_mapping';
		$where = '`erply_code` = \''.ErplyFunctions::getErplyCustomerCode().'\' AND `object_type` = \''.$objectType.'\'';
		return Db::getInstance()->delete($table, $where);
	}

	public static function deleteAll()
	{
		$table = _DB_PREFIX_.'erply_mapping';
		return Db::getInstance()->delete($table);
	}
};
