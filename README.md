# Weblate Translation Provider

[![Author](https://img.shields.io/badge/author-@m2mtech-blue.svg?style=flat-square)](http://www.m2m.at)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

---

This bundle provides a [Weblate](https://weblate.org) integration for [Symfony Translation](https://symfony.com/doc/current/translation.html).

## Installation

```bash
composer require m2mtech/weblate-translation-provider
```

If you are not using Flex enable the bundle:

```php
// config/bundles.php

return [
    // ...
    M2MTech\WeblateTranslationProvider\WeblateTranslationProviderBundle::class => ['all' => true],
];
```

Enable the translation provider:

```yaml
# config/packages/translation.yaml
framework:
    translator:
        providers:
            weblate:
                dsn: '%env(WEBLATE_DSN)%'
                locales: ['en', 'de']
```

and set the DSN in your .env file:

```dotenv
# .env
WEBLATE_DSN=weblate://PROJECT_NAME:API_TOKEN@WEBLATE_URL
```

If you are using a local weblate instance, you can disable the usage of https and/or the verification of the used certificate:

```yaml
# config/packages/weblate.yaml
weblate_translation_provider:
    https: false
    verify_peer: false
```

## Usage

```bash
bin/console translation:push [options] weblate
bin/console translation:pull [options] weblate
```

## Testing

This package has been developed for php 7.4 with compatibility tested for php 7.2 to 8.2RC2.

```bash
composer test
```

For compatibility tests with Symfony 5.3 to 6.0 including a local weblate instance please use the [symfony-weblate-tests](https://github.com/m2mtech/symfony-weblate-tests) package.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
