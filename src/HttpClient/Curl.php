<?php

namespace Proxymarket\HttpClient;

/**
 * HybridAuth default Http client
 */
class Curl {
	/**
	* Default curl options
	*
	* These defaults options can be overwritten when sending requests.
	*
	* See setCurlOptions()
	*
	* @var array
	*/
	protected $curlOptions = [
		CURLOPT_TIMEOUT	=> 30,
		CURLOPT_CONNECTTIMEOUT => 30,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 5,
		CURLINFO_HEADER_OUT	=> true,
		CURLOPT_ENCODING => 'identity',
	];

	/**
	* Method request() arguments
	*
	* This is used for debugging.
	*
	* @var array
	*/
	protected $requestArguments = [];

	/**
	* Default request headers
	*
	* @var array
	*/
	protected $requestHeader = [
		'Accept' => 'application/json',
		'Content-Type' => 'application/json',
	];

	/**
	* Raw response returned by server
	*
	* @var string
	*/
	protected $responseBody = '';

	/**
	* Headers returned in the response
	*
	* @var array
	*/
	protected $responseHeader = [];

	/**
	* Response HTTP status code
	*
	* @var integer
	*/
	protected $responseHttpCode = 0;

	/**
	* Last curl error number
	*
	* @var mixed
	*/
	protected $responseClientError = null;

	/**
	* Information about the last transfer
	*
	* @var mixed
	*/
	protected $responseClientInfo = [];

	/**
	 * Send request to the remote server
	 *
	 * Returns the result (Raw response from the server) on success, FALSE on failure
	 * 
	 * Attention! In this case only POST requests have been used
	 *
	 * @param string $uri
	 * @param string $method
	 * @param array  $parameters
	 * @param array  $headers
	 *
	 * @return mixed
	 */
	public function request($uri, $parameters = [], $headers = [])
	{
		$this->requestHeader = array_replace($this->requestHeader, (array) $headers);

		$this->requestArguments = [
			'uri' => $uri,
			'method' => 'POST',
			'parameters' => $parameters,
			'headers' => $this->requestHeader,
		];

		$curl = curl_init();

		$body_content = json_encode($parameters);
		var_dump($body_content);

		$this->curlOptions[CURLOPT_POST] = true;
		$this->curlOptions[CURLOPT_POSTFIELDS] = $body_content;
		$this->curlOptions[CURLOPT_URL] = $uri;
		$this->curlOptions[CURLOPT_HTTPHEADER] = $this->prepareRequestHeaders();
		$this->curlOptions[CURLOPT_HEADERFUNCTION] = [ $this, 'fetchResponseHeader' ];

		foreach ($this->curlOptions as $opt => $value) {
			curl_setopt($curl, $opt, $value);
		}

		$response = curl_exec($curl);

		$this->responseBody = $response;
		$this->responseHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$this->responseClientError = curl_error($curl);
		$this->responseClientInfo = curl_getinfo($curl);
		
		curl_close($curl);

		return $this->responseBody;
	}

	/**
	* Returns structurated response
	*
	* @return array
	*/
	public function getResponse() {
		$curlOptions = $this->curlOptions;

		$curlOptions[CURLOPT_HEADERFUNCTION] = '*omitted';

		return [
			'request' => $this->getRequestArguments(),
			'response' => [
				'code'	=> $this->getResponseHttpCode(),
				'headers' => $this->getResponseHeader(),
				'body'	=> $this->getResponseBody(),
			],
			'client' => [
				'error' => $this->getResponseClientError(),
				'info'  => $this->getResponseClientInfo(),
				'opts'  => $curlOptions,
			],
		];
	}

	/**
	* Reset curl options
	*
	* @param array $curlOptions
	*/
	public function setCurlOptions($curlOptions) {
		foreach ($curlOptions as $opt => $value) {
			$this->curlOptions[ $opt ] = $value;
		}
	}

	/**
	 * Returns raw response from the server on success, FALSE on failure
	 *
	 * @return mixed
	 */
	public function getResponseBody() {
		return $this->responseBody;
	}

	/**
	 * Retriever the headers returned in the response
	 *
	 * @return array
	*/
	public function getResponseHeader() {
		return $this->responseHeader;
	}

	/**
	* Returns latest request HTTP status code
	*
	* @return integer
	*/
	public function getResponseHttpCode() {
		return $this->responseHttpCode;
	}

	/**
	* Returns latest error encountered by the client
	* This can be either a code or error message
	*
	* @return mixed
	*/
	public function getResponseClientError() {
		return $this->responseClientError;
	}

	/**
	* @return array
	*/
	protected function getResponseClientInfo() {
		return $this->responseClientInfo;
	}

	/**
	* Returns method request() arguments
	*
	* This is used for debugging.
	*
	* @return array
	*/
	protected function getRequestArguments() {
		return $this->requestArguments;
	}

	/**
	* Fetch server response headers
	*
	* @param mixed  $curl
	* @param string $header
	*
	* @return integer
	*/
	protected function fetchResponseHeader($curl, $header) {
		$pos = strpos($header, ':');

		if (false !== $pos) {
			$key   = str_replace('-', '_', strtolower(substr($header, 0, $pos)));

			$value = trim(substr($header, $pos + 2));

			$this->responseHeader[ $key ] = $value;
		}

		return strlen($header);
	}

	/**
	* Convert request headers to the expect curl format
	*
	* @return array
	*/
	protected function prepareRequestHeaders() {
		$headers = [];

		foreach ($this->requestHeader as $header => $value) {
			$headers[] = trim($header) .': '. trim($value);
		}

		return $headers;
	}
}
