<?php
/*
 * This file is part of the weblate-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $parameters->set(Option::SKIP, [
        OrderedImportsFixer::class => [
            __DIR__.'/src/Resources/config/services.php',
        ],
    ]);

    $containerConfigurator->import(SetList::SYMFONY);
    $containerConfigurator->import(SetList::CLEAN_CODE);

    $parameters->set(Option::CACHE_DIRECTORY, __DIR__.'/.ecs_cache');
    $parameters->set(Option::PARALLEL, true);
};
