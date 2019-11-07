<?php

namespace Proxymarket;


class Proxymarket {

	protected $curl;

	protected $serverUrl = 'https://proxy.market/dev-api/';
	protected $listUrl = 'list/';
	protected $buyUrl = 'buy-proxy/';
	protected $apiKey;

	const PROXY_TYPE = [
		'ipv4' => 100,
		'ipv6' => 101,
	];

	const ALL_TYPES = 'all';

	/** 0 - newest at the top; 1 - oldest at the top */
	const SORT = [0, 1];

	const AVAILABLE_SUBNET = [32, 29];

	const AVAILABLE_DURATIONS_IPV4 = [30, 60, 90, 180, 360];
	const AVAILABLE_DURATIONS_IPV6 = [3, 7, 14, 30, 60, 90, 180, 360];

	const AVAILABLE_COUNTRY = ['ru'];

	const INTERNAL_SERVER_ERROR = 500;

	const IPV6 = 'ipv6';
	const IPV4 = 'ipv4';

	/**
	 * @param int $count
	 * @param string $type
	 * @param int $duration
	 * @param string $country
	 * @param string $promocode
	 * @param int $subnet
	 * @return array
	 * @throws Exception\ProxymarketInvalidParameterException
	 */
	private static function createBuyParamsArray($count, $type, $duration , $country , $promocode, $subnet) {
		$params = [];
		$count = (int)$count;
		if ($count <= 0) {
			throw new Exception\ProxymarketInvalidParameterException('Count must be more than zero');
		}
		$params['count'] = $count;

		if (!array_key_exists($type, self::PROXY_TYPE)) {
			throw new Exception\ProxymarketInvalidParameterException('Type must be ipv4 or ipv6');
		}
		$params['type'] = self::PROXY_TYPE[$type];

		$duration = (int)$duration;
		if ((self::IPV4 === $type && !in_array($duration, self::AVAILABLE_DURATIONS_IPV4, true)) ||
			(self::IPV6 === $type && !in_array($duration, self::AVAILABLE_DURATIONS_IPV6, true))) {
			throw new Exception\ProxymarketInvalidParameterException('Available duration for ipv4: 30, 60, 90, 180, 360 and for ipv6: 7, 14, 30, 60, 90, 180, 360');
		}
		$params['duration'] = $duration;

		if (!in_array($country, self::AVAILABLE_COUNTRY, true)) {
			throw new Exception\ProxymarketInvalidParameterException('Country must be "ru"');
		}
		$params['country'] = $country;

		$promocode = (string)$promocode;
		if ('' !== $promocode) {
			$params['promocode'] = $promocode;
		}

		$subnet = (int)$subnet;
		if (self::IPV6 === $type && in_array($subnet, self::AVAILABLE_SUBNET)) {
			$params['subnet'] = $subnet;
		}

		return ['PurchaseBilling' => $params];
	}


	/**
	 * @param $type
	 * @param $page
	 * @param $pageSize
	 * @param $sort
	 * @return array
	 * @throws Exception\ProxymarketInvalidParameterException
	 */
	private static function createListParamsArray($type, $page, $pageSize, $sort) {
		$params = [];
		if (self::ALL_TYPES !== $type && !array_key_exists($type, self::PROXY_TYPE)) {
			throw new Exception\ProxymarketInvalidParameterException('Type must be ipv4, ipv6 or all');
		}
		$params['type'] = $type;

		if ($page < 0) {
			throw new Exception\ProxymarketInvalidParameterException('Page must more ore equivalent zero');
		}
		$params['page'] = $page;

		if ($pageSize < 0) {
			throw new Exception\ProxymarketInvalidParameterException('Page must more ore equivalent zero');
		}
		$params['page_size'] = $pageSize;

		if (!in_array($sort, self::SORT, true)) {
			throw new Exception\ProxymarketInvalidParameterException('Page must more ore equivalent zero');
		}
		$params['sort'] = $sort;

		return $params;
	}

	/**
	 * Proxymarket constructor.
	 * @param $apiKey
	 */
	public function __construct($apiKey) {
		$this->curl = new HttpClient\Curl();
		$this->apiKey = $apiKey;
	}

	/**
	 * @param $count
	 * @param string $type
	 * @param int $duration
	 * @param string $country
	 * @param string $promocode
	 * @param null $subnet
	 * @return array
	 * @throws Exception\ProxymarketError
	 * @throws Exception\ProxymarketInvalidApikey
	 * @throws Exception\ProxymarketInvalidParameterException
	 * @throws Exception\ProxymarketUnsuccessResponse
	 */
	public function buyProxy($count, $type = 'ipv4', $duration = 30, $country = 'ru', $promocode = '', $subnet = null) {
		if (null === $this->apiKey) {
			throw new Exception\ProxymarketInvalidApikey('Apikey must be not null');
		}
		$url = $this->serverUrl.$this->buyUrl.$this->apiKey;
		$params = static::createBuyParamsArray($count, $type, $duration , $country , $promocode, $subnet);
		$response = $this->curl->request($url, $params);
		$response = json_decode($response, true);

		if (array_key_exists('status', $response) && 500 === $response['status']) {
			throw new Exception\ProxymarketUnsuccessResponse($response['message']);
		}
		return ['PurchaseBilling' => $response];
	}

	/**
	 * @param string $type
	 * @param int $page
	 * @param int $pageSize
	 * @param int $sort
	 * @return array
	 * @throws Exception\ProxymarketError
	 * @throws Exception\ProxymarketInvalidParameterException
	 * @throws Exception\ProxymarketUnsuccessResponse
	 */
	public function listProxy($type = 'all', $page=0, $pageSize=0, $sort = 0) {
		$url = $this->serverUrl.$this->listUrl.$this->apiKey;
		$params = static::createListParamsArray($type, $page, $pageSize, $sort);
		$response = $this->curl->request($url, $params);
		$response = json_decode($response, true);
		if (!array_key_exists('success', $response) || (array_key_exists('success', $response) && !$response['success'])) {
			throw new Exception\ProxymarketUnsuccessResponse('Invalid or unsuccess response');
		}

		if (array_key_exists('list', $response)) {
			if (array_key_exists('error', $response['list']) && $response['list']['error'] && array_key_exists('message', $response['list'])) {
				throw new Exception\ProxymarketError($response['list']['message']);
			}

			if (array_key_exists('data', $response['list'])) {
				return $response['list']['data'];
			}
		}
		throw new Exception\ProxymarketError('Unknown API error');

	}
}
