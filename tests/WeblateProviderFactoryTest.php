<?php
/*
 * This file is part of the weblate-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace M2MTech\WeblateTranslationProvider\Tests;

use M2MTech\WeblateTranslationProvider\WeblateProviderFactory;
use Symfony\Component\Translation\Provider\ProviderFactoryInterface;

class WeblateProviderFactoryTest extends ProviderFactoryTestCase
{
    public function createFactory(): ProviderFactoryInterface
    {
        return new WeblateProviderFactory(
            $this->getClient(),
            $this->getLoader(),
            $this->getLogger(),
            $this->getXliffFileDumper(),
            $this->getDefaultLocale(),
            ['https' => true, 'verify_peer' => true]
        );
    }

    public function supportsProvider(): iterable
    {
        yield [true, 'weblate://project:key@server'];
        yield [false, 'somethingElse://project:key@server'];
    }

    public function unsupportedSchemeProvider(): iterable
    {
        yield ['somethingElse://project:key@server', 'scheme is not supported'];
    }

    public function createProvider(): iterable
    {
        yield [
            'weblate://server',
            'weblate://project:key@server',
        ];

        yield [
            'weblate://server/path',
            'weblate://project:key@server/path',
        ];

        yield [
            'weblate://server/bla/bla/bla',
            'weblate://project:key@server/bla/bla/bla/',
        ];
    }

    public function incompleteDsnProvider(): iterable
    {
        yield ['weblate://project@default', 'Password is not set'];
        yield ['weblate://default', 'Password is not set'];
    }
}
