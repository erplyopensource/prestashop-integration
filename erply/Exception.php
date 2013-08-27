<?php

class Erply_Exception extends Exception
{
	protected $_data = array();


	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getData($key=null)
	{
		if(!is_null($key))
		{
			if(strtolower($key) == 'message')
			{
				return $this->getMessage();
			}
			elseif(strtolower($key) == 'code')
			{
				return $this->getCode();
			}
			else
			{
				return isset($this->_data[$key]) ? $this->_data[$key] : null;
			}
		}
		else
		{
			return $this->_data;
		}
	}

	/**
	 * @param string | array $key
	 * @param mixed $val
	 * @return Erply_Exception
	 */
	public function setData($key, $val=null)
	{
		if(!is_array($key))
		{
			if(strtolower($key) == 'message')
			{
				$this->message = $val;
			}
			elseif(strtolower($key) == 'code')
			{
				$this->code = $val;
			}
			else
			{
				$this->_data[$key] = $val;
			}
		}
		else
		{
			if(array_key_exists('message', $key))
			{
				$this->message = $key['message'];
				unset($key['message']);
			}
			if(array_key_exists('code', $key))
			{
				$this->code = $key['code'];
				unset($key['code']);
			}
			if(count($key) > 0)
			{
				$this->_data = array_merge( $this->_data, $key );
			}
		}
		return $this;
	}
}

?>