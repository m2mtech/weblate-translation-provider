<?php
/*
 * This file is part of the weblate-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace M2MTech\WeblateTranslationProvider\Tests\Api\DTO;

use M2MTech\WeblateTranslationProvider\Api\DTO\Unit;
use PHPUnit\Framework\TestCase;

class UnitTest extends TestCase
{
    public function testImport(): void
    {
        $data = DTOFaker::createUnitData();

        $component = new Unit($data + ['ignored' => 'something']);
        foreach ($data as $key => $value) {
            $this->assertSame($value, $component->$key);
        }
    }

    public function testMissing(): void
    {
        if (class_exists('Spatie\DataTransferObject\FlexibleDataTransferObject')) {
            $this->expectError();
        } else {
            $this->assertTrue(true);
        }

        new Unit([]);
    }
}
