<?php

declare(strict_types=1);

namespace Baraja\Url;


use Nette\Http\Url as NetteUrl;

final class Url
{
	private string $currentUrl;

	private string $baseUrl;

	private NetteUrl $netteUrl;


	public function __construct(?string $currentUrl = null)
	{
		if (PHP_SAPI === 'cli' && $currentUrl === null) {
			throw new \RuntimeException(
				'URL detection and work is not available in CLI mode, but only when processing a real request.' . "\n"
				. 'You can suppress this message by passing an absolute URL in the parameter.'
			);
		}
		$this->currentUrl = $currentUrl ?? $this->detectCurrentUrl();
		$this->netteUrl = new NetteUrl($this->currentUrl);
		$this->baseUrl = rtrim($this->netteUrl->getBaseUrl(), '/');
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


	/**
	 * Return current absolute URL.
	 * Return null, if current URL does not exist (for example in CLI mode).
	 */
	private function detectCurrentUrl(): string
	{
		if (!isset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])) {
			throw new \RuntimeException('URL detection is not available in CLI mode.');
		}

		return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
			. '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}
}
