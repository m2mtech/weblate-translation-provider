<?php
/*
 * This file is part of the weblate-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace M2MTech\WeblateTranslationProvider\Tests\Api;

use M2MTech\WeblateTranslationProvider\Api\DTO\Component;
use M2MTech\WeblateTranslationProvider\Api\DTO\Translation;
use M2MTech\WeblateTranslationProvider\Api\TranslationApi;
use M2MTech\WeblateTranslationProvider\Tests\Api\DTO\DTOFaker;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class TranslationApiTest extends ApiTest
{
    /**
     * @param callable[] $responses
     */
    private function setupFactory(array $responses): void
    {
        TranslationApi::setup(
            new MockHttpClient($responses, 'https://v5.3.ignores/baseUri'),
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * @param array<array<string,string>> $results
     */
    private function getGetTranslationsResponse(Component $component, array $results): callable
    {
        return $this->getResponse(
            $component->translations_url,
            'GET',
            '',
            (string) json_encode(['results' => $results])
        );
    }

    /**
     * @param array<string,string> $result
     */
    private function getAddTranslationResponse(Component $component, string $body, array $result): callable
    {
        return $this->getResponse(
            $component->translations_url,
            'POST',
            $body,
            (string) json_encode(['data' => $result]),
            201
        );
    }

    private function getUploadTranslationResponse(Translation $translation, string $body): callable
    {
        return $this->getResponse(
            $translation->file_url,
            'POST',
            $body,
            ''
        );
    }

    private function getDownloadTranslationResponse(Translation $translation, string $file): callable
    {
        return $this->getResponse(
            $translation->file_url,
            'GET',
            '',
            $file
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function testHasTranslationFalse(): void
    {
        $component = DTOFaker::createComponent();

        $this->setupFactory([
            $this->getGetTranslationsResponse($component, []),
            $this->getGetTranslationsResponse($component, [DTOFaker::createTranslationData()]),
        ]);

        $this->assertFalse(TranslationApi::hasTranslation($component, 'notExisting'));

        // calling getTranslations a second time because it was empty the first time
        $this->assertFalse(TranslationApi::hasTranslation($component, 'notExisting'));

        // not calling getTranslations a third time
        $this->assertFalse(TranslationApi::hasTranslation($component, 'notExisting'));
    }

    /**
     * @throws ExceptionInterface
     */
    public function testHasTranslation(): void
    {
        $component = DTOFaker::createComponent();
        $data = DTOFaker::createTranslationData();

        $this->setupFactory([
            $this->getGetTranslationsResponse($component, [$data]),
        ]);

        $this->assertTrue(TranslationApi::hasTranslation($component, $data['language_code']));

        // not calling getTranslations a second time
        $this->assertTrue(TranslationApi::hasTranslation($component, $data['language_code']));
    }

    /**
     * @throws ExceptionInterface
     */
    public function testAddTranslation(): void
    {
        $component = DTOFaker::createComponent();

        $data = DTOFaker::createTranslationData();
        $newTranslation = new Translation($data);
        $newTranslation->created = true;

        $this->setupFactory([
            $this->getAddTranslationResponse($component, 'language_code=' . $newTranslation->language_code, $data),
        ]);

        $translation = TranslationApi::addTranslation($component, $newTranslation->language_code);
        $this->assertEquals($newTranslation, $translation);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testGetTranslation(): void
    {
        $component = DTOFaker::createComponent();

        $existingData = DTOFaker::createTranslationData();
        $existingTranslation = new Translation($existingData);

        $newData = DTOFaker::createTranslationData();
        $newTranslation = new Translation($newData);
        $newTranslation->created = true;

        $this->setupFactory([
            $this->getGetTranslationsResponse($component, [$existingData]),
            $this->getAddTranslationResponse($component, 'language_code=' . $newTranslation->language_code, $newData),
        ]);

        $translation = TranslationApi::getTranslation($component, $existingTranslation->language_code);
        $this->assertEquals($existingTranslation, $translation);

        $translation = TranslationApi::getTranslation($component, $newTranslation->language_code);
        $this->assertEquals($newTranslation, $translation);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testUploadTranslation(): void
    {
        $translation = DTOFaker::createTranslation();
        $content = DTOFaker::getFaker()->paragraph();

        $this->setupFactory([
            $this->getUploadTranslationResponse($translation, $content),
        ]);

        TranslationApi::uploadTranslation($translation, $content);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testDownloadTranslation(): void
    {
        $translation = DTOFaker::createTranslation();
        $content = DTOFaker::getFaker()->paragraph();

        $this->setupFactory([
            $this->getDownloadTranslationResponse($translation, $content),
        ]);

        TranslationApi::downloadTranslation($translation);
    }
}
