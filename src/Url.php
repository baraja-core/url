<?php

declare(strict_types=1);

namespace Baraja\Url;


use Nette\Http\Url as NetteUrl;
use Nette\Http\UrlScript;

final class Url
{
	private string $currentUrl;

	private string $baseUrl;

	private NetteUrl $netteUrl;

	private UrlScript $urlScript;


	public function __construct(?string $currentUrl = null)
	{
		if (PHP_SAPI === 'cli' && $currentUrl === null) {
			throw new \RuntimeException(
				'URL detection is not available in CLI mode, but only when processing a real request.' . "\n"
				. 'To solve this issue: BaseUrl is automatically detected according to the current HTTP request. '
				. 'In CLI mode (when there is no HTTP request), you need to manually define the BaseUrl by passing an absolute URL in the parameter.'
			);
		}
		$this->currentUrl = $currentUrl ?? $this->detectCurrentUrl();
		$this->netteUrl = new NetteUrl($this->currentUrl);
		$this->urlScript = new UrlScript($this->netteUrl, $this->getScriptPath($this->netteUrl));
		$this->baseUrl = rtrim($this->urlScript->getBaseUrl(), '/');
	}


	public static function get(?string $currentUrl = null): self
	{
		static $cache;

		return $cache ?? $cache = new self($currentUrl);
	}


	public function getCurrentUrl(): string
	{
		return $this->currentUrl;
	}


	public function getBaseUrl(): string
	{
		return $this->baseUrl;
	}


	public function getNetteUrl(): NetteUrl
	{
		return $this->netteUrl;
	}


	public function getUrlScript(): UrlScript
	{
		return $this->urlScript;
	}


	private function detectCurrentUrl(): string
	{
		if (!isset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])) {
			throw new \RuntimeException('URL detection is not available in CLI mode.');
		}

		return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
			. '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}


	private function getScriptPath(NetteUrl $url): string
	{
		if (($lowerPath = strtolower($path = $url->getPath())) !== ($script = strtolower($_SERVER['SCRIPT_NAME'] ?? ''))) {
			$max = min(strlen($lowerPath), strlen($script));
			for ($i = 0; $i < $max && $lowerPath[$i] === $script[$i]; $i++) {
				continue;
			}
			$path = $i
				? substr($path, 0, strrpos($path, '/', $i - strlen($path) - 1) + 1)
				: '/';
		}

		return $path;
	}
}
