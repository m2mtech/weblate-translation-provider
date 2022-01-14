<?php
/*
 * This file is part of the weblate-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace M2MTech\WeblateTranslationProvider\Tests\Api;

use M2MTech\WeblateTranslationProvider\Api\DTO\Translation;
use M2MTech\WeblateTranslationProvider\Api\DTO\Unit;
use M2MTech\WeblateTranslationProvider\Api\UnitApi;
use M2MTech\WeblateTranslationProvider\Tests\Api\DTO\DTOFaker;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class UnitApiTest extends ApiTest
{
    /**
     * @param callable[] $responses
     */
    private function setupFactory(array $responses): void
    {
        UnitApi::setup(
            new MockHttpClient($responses),
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * @param array<array<string,string>> $results
     */
    private function getGetUnitsResponse(Translation $translation, array $results): callable
    {
        return $this->getResponse(
            $translation->units_list_url,
            'GET',
            '',
            (string) json_encode(['results' => $results])
        );
    }

    private function getAddUnitResponse(Translation $translation, string $body): callable
    {
        return $this->getResponse(
            $translation->units_list_url,
            'POST',
            $body,
            ''
        );
    }

    private function getUpdateUnitResponse(Unit $unit, string $body): callable
    {
        return $this->getResponse(
            $unit->url,
            'PATCH',
            $body,
            ''
        );
    }

    private function getDeleteUnitResponse(Unit $unit): callable
    {
        return $this->getResponse(
            $unit->url,
            'DELETE',
            '',
            '',
            204
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function testHasUnitFalse(): void
    {
        $translation = DTOFaker::createTranslation();

        $this->setupFactory([
            $this->getGetUnitsResponse($translation, []),
            $this->getGetUnitsResponse($translation, [DTOFaker::createUnitData()]),
        ]);

        $this->assertFalse(UnitApi::hasUnit($translation, 'notExisting'));

        // calling getUnits a second time because it was empty the first time
        $this->assertFalse(UnitApi::hasUnit($translation, 'notExisting'));

        // not calling getUnits a third time
        $this->assertFalse(UnitApi::hasUnit($translation, 'notExisting'));
    }

    /**
     * @throws ExceptionInterface
     */
    public function testHasUnit(): void
    {
        $translation = DTOFaker::createTranslation();
        $data = DTOFaker::createUnitData();

        $this->setupFactory([
            $this->getGetUnitsResponse($translation, [$data]),
        ]);

        $this->assertTrue(UnitApi::hasUnit($translation, $data['context']));

        // not calling getUnits a second time
        $this->assertTrue(UnitApi::hasUnit($translation, $data['context']));
    }

    /**
     * @throws ExceptionInterface
     */
    public function testAddUnit(): void
    {
        $translation = DTOFaker::createTranslation();
        $faker = DTOFaker::getFaker();
        $key = $faker->slug();
        $value = $faker->sentence();

        $this->setupFactory([
            $this->getAddUnitResponse($translation, 'key='.$key.'&value='.urlencode($value)),
        ]);

        UnitApi::addUnit($translation, $key, $value);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testGetUnit(): void
    {
        $translation = DTOFaker::createTranslation();
        $data = DTOFaker::createUnitData();
        $existingUnit = new Unit($data);

        $this->setupFactory([
            $this->getGetUnitsResponse($translation, [$data]),
        ]);

        $this->assertNull(UnitApi::getUnit($translation, 'notExisting'));

        $unit = UnitApi::getUnit($translation, $existingUnit->context);
        $this->assertEquals($existingUnit, $unit);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testUpdateUnit(): void
    {
        $unit = DTOFaker::createUnit();
        $value = DTOFaker::getFaker()->sentence();

        $this->setupFactory([
            $this->getUpdateUnitResponse($unit, 'target='.urlencode($value).'&state=20'),
        ]);

        UnitApi::updateUnit($unit, $value);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testDeleteUnit(): void
    {
        $unit = DTOFaker::createUnit();

        $this->setupFactory([
            $this->getDeleteUnitResponse($unit),
        ]);

        UnitApi::deleteUnit($unit);
    }
}
