Baraja safe URL
===============

![Integrity check](https://github.com/baraja-core/url/workflows/Integrity%20check/badge.svg)

Simple manager to get parts of the current URL. URL resolving is done in a secure way.

How to use
----------

An easy-to-use library for obtaining and managing current URLs.

You will get the current URL:

```php
echo \Baraja\Url\Url::get()->getCurrentUrl();
```

A base url:

```php
echo \Baraja\Url\Url::get()->getBaseUrl();
```

Nette Url or Script Url can also be obtained for robust work with URL parts:

```php
$netteUrl = \Baraja\Url\Url::get()->getNetteUrl();

echo $netteUrl->getDomain();
echo $netteUrl->getPort();
echo $netteUrl->getQuery();
```

And many other getters, [see the documentation](https://github.com/nette/http) for more.

📄 License
-----------

`baraja-core/url` is licensed under the MIT license. See the [LICENSE](https://github.com/baraja-core/url/blob/master/LICENSE) file for more details.
