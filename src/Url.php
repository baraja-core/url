<?php

declare(strict_types=1);

namespace Baraja\Url;


use Nette\Http\Url as NetteUrl;
use Nette\Http\UrlScript;

final class Url
{
	/** @var array<string, string>|null */
	private static ?array $allowedDomains = null;

	private string $currentUrl;

	private string $baseUrl;

	private string $relativeUrl;

	private NetteUrl $netteUrl;

	private UrlScript $urlScript;


	public function __construct(?string $currentUrl = null)
	{
		if (PHP_SAPI === 'cli' && $currentUrl === null) {
			throw new \RuntimeException(
				'URL detection is not available in CLI mode, but only when processing a real request.' . "\n"
				. 'To solve this issue: BaseUrl is automatically detected according to the current HTTP request. '
				. 'In CLI mode (when there is no HTTP request), you need to manually define the BaseUrl by passing an absolute URL in the parameter.',
			);
		}
		$this->currentUrl = $currentUrl ?? $this->detectCurrentUrl();
		$this->netteUrl = new NetteUrl($this->currentUrl);
		$this->urlScript = new UrlScript($this->netteUrl, $this->getScriptPath($this->netteUrl));
		$this->baseUrl = rtrim($this->urlScript->getBaseUrl(), '/');
		$this->relativeUrl = ltrim($this->urlScript->getRelativeUrl(), '/');
	}


	public static function get(?string $currentUrl = null): self
	{
		static $cache;

		return $cache ?? $cache = new self($currentUrl);
	}


	public static function addAllowedDomain(string $domain): void
	{
		$domain = self::idnHostToUnicode(strtolower($domain));
		self::$allowedDomains[$domain] = $domain;
	}


	/**
	 * @return array<string, string>
	 */
	public static function getAllowedDomains(): ?array
	{
		return self::$allowedDomains ?? [];
	}


	/**
	 * Converts IDN ASCII host to UTF-8.
	 */
	public static function idnHostToUnicode(string $host): string
	{
		if (!str_contains($host, '--')) { // host does not contain IDN
			return $host;
		}
		if (function_exists('idn_to_utf8') && defined('INTL_IDNA_VARIANT_UTS46')) {
			$parts = explode(':', $host, 2);
			$rewrittenHost = (string) idn_to_utf8($parts[0], IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
			if ($rewrittenHost !== '') {
				return $rewrittenHost . (isset($parts[1]) && $parts[1] !== '80' && $parts[1] !== '443' ? ':' . $parts[1] : '');
			}
		}
		trigger_error('PHP extension idn is not loaded or is too old', E_USER_WARNING);

		return $host;
	}


	public function getCurrentUrl(): string
	{
		return $this->currentUrl;
	}


	public function getBaseUrl(): string
	{
		return $this->baseUrl;
	}


	public function getRelativeUrl(bool $withParameters = true): string
	{
		return $withParameters === true
			? $this->relativeUrl
			: (string) preg_replace('/^(.*)(\?.*)$/', '$1', $this->relativeUrl);
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
		$host = self::idnHostToUnicode(strtolower($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST']));
		if (self::$allowedDomains !== null && isset(self::$allowedDomains[$host]) === false) {
			trigger_error(sprintf('Domain "%s" is not allowed in ["%s"].', $host, implode('", "', self::$allowedDomains)));
			$host = 'localhost';
		}
		$https =
			(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
			|| (
				isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
				&& strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0
			);

		return ($https ? 'https' : 'http') . '://' . $host . $_SERVER['REQUEST_URI'];
	}


	private function getScriptPath(NetteUrl $url): string
	{
		$path = $url->getPath();
		$lowerPath = strtolower($path);
		$script = strtolower($_SERVER['SCRIPT_NAME'] ?? '');
		if ($lowerPath !== $script) {
			$max = min(strlen($lowerPath), strlen($script));
			$i = 0;
			for (; $i < $max && $lowerPath[$i] === $script[$i]; $i++) {
				continue;
			}
			$path = $i !== 0
				? substr($path, 0, ((int) strrpos($path, '/', $i - strlen($path) - 1)) + 1)
				: '/';
		}

		return $path;
	}
}
