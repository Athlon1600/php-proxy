<?php

namespace Proxy\Plugin;

use Proxy\Plugin\AbstractPlugin;
use Proxy\Event\ProxyEvent;

class SearchEnginePlugin extends AbstractPlugin {

	private $domainRegex = '/^([a-z0-9][a-z0-9-]{1,61}[a-z0-9]\.){1,}[a-z]{2,}$/i';

	public function onBeforeRequest(ProxyEvent $event) {
		$request = $event['request'];
		$keyword = parse_url($request->getUrl(), PHP_URL_HOST);

		if (!preg_match($this->domainRegex, $keyword))
		{
			$searchEngine = $this->getSearchEngine();
			$url = sprintf('%s/%s?%s=%s%s', $searchEngine['domain'], $searchEngine['path'], $searchEngine['searchKey'], $keyword, $searchEngine['params']);
			$request->setUrl($url);
		}
	}

	private function getSearchEngine() {
		$list = $this->getSearchEngineConfigs();
		$userSearchEngine = $this->getUserSearchEnginePreference();

		return $list[$userSearchEngine];
	}

	private function getUserSearchEnginePreference() {
		return 'google';
	}

	private function getSearchEngineConfigs() {
		return array(
			'google' => array(
				'domain' => 'google.com',
				'path' => 'search',
				'searchKey' => 'q'
			),
			'youtube' => array(
				'domain' => 'youtube.com',
				'path' => 'results',
				'searchKey' => 'search_query'
			),
			'yandex' => array(
				'domain' => 'yandex.com',
				'path' => 'search/',
				'searchKey' => 'text'
			),
			'bing' => array(
				'domain' => 'bing.com',
				'path' => 'search',
				'searchKey' => 'q'
			),
			'yahoo' => array(
				'domain' => 'search.yahoo.com',
				'path' => 'search',
				'searchKey' => 'p'
			)
		);
	}
}
?>
