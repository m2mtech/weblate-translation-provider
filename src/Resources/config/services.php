<?php
/*
 * This file is part of the weblate-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use M2MTech\WeblateTranslationProvider\WeblateProvider;
use M2MTech\WeblateTranslationProvider\WeblateProviderFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set('m2mtech.translation.provider_factory.weblate', WeblateProviderFactory::class)
        ->args([
            service('http_client'),
            service('translation.loader.xliff'),
            service('logger'),
            service('translation.dumper.xliff'),
            param('kernel.default_locale'),
            abstract_arg('bundle config'),
        ])
        ->tag('translation.provider_factory');

    $services->set('m2mtech.translation.provider.weblate', WeblateProvider::class);
};
