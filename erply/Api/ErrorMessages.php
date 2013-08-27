<?php

class Erply_Api_ErrorMessages
{
	private static $_messages = array(
		  '1000' => 'API is in maintenance, try again in a couple of minutes'
		, '1001' => 'Access has not been set up for this account'
		, '1002' => 'Number of allowed requests exceeded for this account'
		, '1003' => 'Cannot access system (problem interpreting API key and identifying customer\'s account)'
		, '1004' => 'API version number not specified'
		, '1005' => 'Unknown function name'
		, '1006' => 'Function not implemented yet'
		, '1007' => 'Unknown format requested (must be JSON or XML)'

		, '1010' => 'Required parameters are missing. (Attribute "errorField" indicates the missing input parameter.)'
		, '1011' => 'Invalid classifier ID, there is no such item. (Attribute "errorField" indicates the invalid input parameter.)'
		, '1012' => 'A parameter must have a unique value. (Attribute "errorField" indicates the invalid input parameter.)'
		, '1013' => 'Inconsistent parameter set (for example, both product and service IDs specified for an invoice row)'
		, '1014' => 'Incorrect data type or format. (Attribute "errorField" indicates the invalid input parameter.)'
		, '1015' => 'Malformed request (eg. parameters containing invalid characters)'

		, '1050' => 'Username/password missing'
		, '1051' => 'Login failed'
		, '1052' => 'User has been temporarily blocked because of unsuccessful login attempts'
		, '1053' => 'Login has not been enabled for this user'
		, '1054' => 'API session has expired. Call function verifyUser() again (with correct credentials) to receive a new session key.'
		, '1055' => 'Session not found'

		, '1060' => 'No viewing rights (in this module/for this item)'
		, '1061' => 'No adding rights (in this module)'
		, '1062' => 'No editing rights (in this module/for this item)'
		, '1063' => 'No deleting rights (in this module/for this item)'
		, '1064' => 'User does not have access to this warehouse'

		, '1071' => 'Sales to this client are blocked'

		, '1080' => 'Printing service is not running at the moment. (User can turn printing service on from their Erply account).'
		, '1081' => 'E-mail sending failed'
		, '1082' => 'E-mail sending has been incorrectly set up, review settings in ERPLY. (Missing sender\'s address or empty message content)'

		, '1090' => 'No file attached'
		, '1091' => 'Attached file is not encoded with Base64'
		, '1092' => 'Attached file\'s limit is exceeded'
	);

	/**
	 * Get error message by code. Translation not implemented.
	 * 
	 * @param string $code
	 * @return string
	 */
	public static function getMessage($code)
	{
		return isset(self::$_messages[$code]) ? self::$_messages[$code] : '';
	}
}

?>