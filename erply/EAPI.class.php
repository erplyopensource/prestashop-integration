<?php

class EAPI {

    public $url;
    public $clientCode;
    public $username;
    public $password;
    public $runProfiler = false;
    protected $_timeDifference = 0; // Time difference between local and ERPLY server.

    // Sends POST request to API
    public function sendRequest($request, $parameters = array())
    {
    	$profilerStart = time();

        // Check if all nessecary parameters are set up
        if(!$this->url OR !$this->clientCode OR !$this->username OR !$this->password)
            return false;

        // Include clientcode and request name to POST parameters
        $parameters['request'] = $request;		
        $parameters['clientCode'] = $this->clientCode;

        // Get session KEY
        if($request != "verifyUser"){

            $parameters['sessionKey'] = $this->getSessionKey($keyRequestResult);

            // Instead of a KEY we got an array which contains error code, let's return in
            if(!$parameters['sessionKey'])
                return $keyRequestResult;
        }

        // Prepare POST request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HEADER,1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST,true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);

        // call POST request
        if(curl_exec($ch) === false)
            return false;

        // get response content
        $content = curl_multi_getcontent($ch);
        curl_close($ch);

        // remove heders
        list($header1,$header2,$body) = explode("\r\n\r\n",$content,3);

    	// Log request into profiler
		if($this->runProfiler === true)
		{
			// Log request
			$logRand = rand(1000, 9999);
			$profilerLog = sprintf("%s %s: %s Duration: %s sec. Params: %s\n"
				, date('Y-m-d H:i:s')
				, $logRand
				, $request
				, (time() - $profilerStart)
				, serialize($parameters)
			);
			$profilerFile = dirname(__FILE__).'/profiler_log.txt';
			file_put_contents($profilerFile, $profilerLog, FILE_APPEND);

			// Make sure response logs dir exists.
			$logsDir = dirname(__FILE__).'/logs';
			if(!is_dir($logsDir)) {
				mkdir($logsDir, 0777, true);
			}

			// Log response
			$responseLogFile = $logsDir.'/'.sprintf('%s_%s_%s.txt'
				, date('Y.m.d-H.i.s')
				, $request
				, $logRand
			);
			file_put_contents($responseLogFile, "request: $request\n\r");
			file_put_contents($responseLogFile, "parameters: $request".print_r($parameters, true)."\n\r", FILE_APPEND);
			file_put_contents($responseLogFile, "response: ".print_r(json_decode($body, true), true), FILE_APPEND);
		}

        // return response body
        return $body;

    }

    /**
     * Returns time difference between local server and ERPLY server.
     * 
     * @return int
     */
	public function getTimeDifference()
	{
		return $this->_timeDifference;
	}

    private function getSessionKey(&$result) {

        // Session KEY is active, return active KEY
        global $_SESSION;
        if($_SESSION['EAPISessionKey'][$this->username]
                AND $_SESSION['EAPISessionKeyExpires'][$this->username] > time())
            return $_SESSION['EAPISessionKey'][$this->username];

        // New session KEY must be obtained
        else
        {
            // Perform API request to get session KEY
            $result = $this->sendRequest("verifyUser",
                    array("username" => $this->username, "password" => $this->password) );

            // Json response into PHP array
            $response = json_decode($result, true);

			// Set time difference
			$this->_timeDifference = (int)$response['status']['requestUnixTime'] - time();

            // Session KEY was successfully received
            if(isset($response['records']) && is_array($response['records'][0]) && !empty($response['records'][0]['sessionKey'])) {

                // Set session KEY in client session and set KEY expiration time
                $_SESSION['EAPISessionKey'][$this->username] =
                        $response['records'][0]['sessionKey'];
                $_SESSION['EAPISessionKeyExpires'][$this->username] =
                        time() + $response['records'][0]['sessionLength'] - 30;

                // Return obtained new session KEY
                return $_SESSION['EAPISessionKey'][$this->username];
            }

            // Session KEY was not received
            else {

                // Return API response which includes error code
                unset($_SESSION['EAPISessionKey'][$this->username]);
                return false;
            }
        }
    }
}

?>