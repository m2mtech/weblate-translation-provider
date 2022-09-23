<?php
/*
 * This file is part of the weblate-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace M2MTech\WeblateTranslationProvider\Tests;

use M2MTech\WeblateTranslationProvider\Tests\Api\DTO\DTOFaker;
use M2MTech\WeblateTranslationProvider\WeblateProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Provider\ProviderInterface;
use Symfony\Component\Translation\TranslatorBag;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class WeblateProviderTest extends ProviderTestCase
{
    public function createProvider(
        HttpClientInterface $client,
        LoaderInterface $loader,
        LoggerInterface $logger,
        string $defaultLocale,
        string $endpoint
    ): ProviderInterface {
        return new WeblateProvider(
            $client,
            $loader,
            $logger,
            $this->getXliffFileDumper(),
            $defaultLocale,
            $endpoint,
            'project'
        );
    }

    /**
     * @param callable[] $responses
     */
    private function getProvider(array $responses): ProviderInterface
    {
        return $this->createProvider((new MockHttpClient($responses))->withOptions([
            'base_uri' => 'https://weblate.com/api/',
            'auth_bearer' => 'API_TOKEN',
        ]), $this->getLoader(), $this->getLogger(), $this->getDefaultLocale(), 'weblate.com');
    }

    private function getResponse(
        string $expectedUrl,
        string $expectedMethod,
        string $expectedBody,
        string $result,
        int $statusCode = 200
    ): callable
    {
        return function (string $method, string $url, array $options = []) use ($expectedUrl, $expectedMethod, $expectedBody, $result, $statusCode): ResponseInterface {
            $this->assertSame($expectedMethod.' '.$expectedUrl, $method.' '.$url);
            $this->assertSame('Authorization: Bearer API_TOKEN', $options['normalized_headers']['authorization'][0]);
            if ($expectedBody) {
                $this->assertStringContainsString($expectedBody, $options['body']);
            }

            return new MockResponse($result, ['http_code' => $statusCode]);
        };
    }

    /**
     * @param array<array<string,string>> $results
     */
    private function getGetComponentsResponse(array $results): callable
    {
        return $this->getResponse(
            'https://weblate.com/api/projects/project/components/',
            'GET',
            '',
            (string) json_encode(['results' => $results])
        );
    }

    /**
     * @param array<string,string> $result
     */
    private function getAddComponentResponse(string $fileContent, array $result): callable
    {
        return $this->getResponse(
            'https://weblate.com/api/projects/project/components/',
            'POST',
            $fileContent,
            (string) json_encode($result),
            201
        );
    }

    /**
     * @param array<array<string,string>> $results
     */
    private function getGetTranslationsResponse(string $url, array $results): callable
    {
        return $this->getResponse(
            $url,
            'GET',
            '',
            (string) json_encode(['results' => $results])
        );
    }

    /**
     * @param array<string,string> $result
     */
    private function getAddTranslationResponse(string $url, string $body, array $result): callable
    {
        return $this->getResponse(
            $url,
            'POST',
            $body,
            (string) json_encode(['data' => $result]),
            201
        );
    }

    private function getUploadTranslationResponse(string $url, string $body): callable
    {
        return $this->getResponse(
            $url,
            'POST',
            $body,
            ''
        );
    }

    private function getDownloadTranslationResponse(string $url, string $file): callable
    {
        return $this->getResponse(
            $url,
            'GET',
            '',
            $file
        );
    }

    /**
     * @param array<array<string,string>> $results
     */
    private function getGetUnitsResponse(string $url, array $results): callable
    {
        return $this->getResponse(
            $url,
            'GET',
            '',
            (string) json_encode(['results' => $results])
        );
    }

    private function getDeleteUnitResponse(string $url): callable
    {
        return $this->getResponse(
            $url,
            'DELETE',
            '',
            '',
            204
        );
    }

    public function toStringProvider(): iterable
    {
        yield [
            $this->createProvider($this->getClient(), $this->getLoader(), $this->getLogger(), $this->getDefaultLocale(), 'server'),
            'weblate://server',
        ];
    }

    /**
     * @return array{TranslatorBag, MessageCatalogue}
     */
    private function getTranslationBag(string $locale): array
    {
        $faker = DTOFaker::getFaker();
        $translatorBag = new TranslatorBag();
        $catalogue = new MessageCatalogue($locale, [
            'messages' => [
                $faker->unique()->slug() => $faker->sentence(),
                $faker->unique()->slug() => $faker->sentence(),
            ],
            'validators' => [$faker->slug() => $faker->sentence()],
        ]);
        $translatorBag->addCatalogue($catalogue);

        return [$translatorBag, $catalogue];
    }

    public function testWriteCreateComponents(): void
    {
        $this->xliffFileDumper = new XliffFileDumper();

        [$translatorBag, $catalogue] = $this->getTranslationBag('en');

        $responses = [
            $this->getGetComponentsResponse([]),
            $this->getAddComponentResponse(
                str_replace(
                    '<trans-unit',
                    '<trans-unit xml:space="preserve"',
                    $this->xliffFileDumper->formatCatalogue(
                        $catalogue,
                        'messages',
                        ['default_locale' => $this->getDefaultLocale()]
                    )
                ),
                [
                    'slug' => 'messages',
                    'url' => 'https://weblate.com/api/components/project/messages/',
                    'repository_url' => 'https://weblate.com/api/components/project/messages/repository/',
                    'translations_url' => 'https://weblate.com/api/components/project/messages/translations/',
                ]
            ),
            $this->getAddComponentResponse(
                str_replace(
                    '<trans-unit',
                    '<trans-unit xml:space="preserve"',
                    $this->xliffFileDumper->formatCatalogue(
                        $catalogue,
                        'validators',
                        ['default_locale' => $this->getDefaultLocale()]
                    )
                ),
                [
                    'slug' => 'validators',
                    'url' => 'https://weblate.com/api/components/project/validators/',
                    'repository_url' => 'https://weblate.com/api/components/project/validators/repository/',
                    'translations_url' => 'https://weblate.com/api/components/project/validators/translations/',
                ]
            ),
        ];

        $provider = $this->getProvider($responses);

        $provider->write($translatorBag);
    }

    public function testWriteAddAndUploadTranslation(): void
    {
        $this->xliffFileDumper = new XliffFileDumper();

        [$translatorBag, $catalogue] = $this->getTranslationBag('de');
        $messagesComponentData = DTOFaker::createComponentData('messages');
        $messagesTranslationData = DTOFaker::createTranslationData('de');
        $validatorsComponentData = DTOFaker::createComponentData('validators');
        $validatorsTranslationData = DTOFaker::createTranslationData('de');

        $responses = [
            $this->getGetComponentsResponse([$messagesComponentData, $validatorsComponentData]),
            $this->getGetTranslationsResponse($messagesComponentData['translations_url'], []),
            $this->getAddTranslationResponse(
                $messagesComponentData['translations_url'],
                'language_code=de',
                $messagesTranslationData
            ),
            $this->getUploadTranslationResponse(
                $messagesTranslationData['file_url'],
                str_replace(
                    '<trans-unit',
                    '<trans-unit xml:space="preserve"',
                    $this->xliffFileDumper->formatCatalogue(
                        $catalogue,
                        'messages',
                        ['default_locale' => $this->getDefaultLocale()]
                    )
                )
            ),
            $this->getGetTranslationsResponse($validatorsComponentData['translations_url'], []),
            $this->getAddTranslationResponse(
                $validatorsComponentData['translations_url'],
                'language_code=de',
                $validatorsTranslationData
            ),
            $this->getUploadTranslationResponse(
                $validatorsTranslationData['file_url'],
                str_replace(
                    '<trans-unit',
                    '<trans-unit xml:space="preserve"',
                    $this->xliffFileDumper->formatCatalogue(
                        $catalogue,
                        'validators',
                        ['default_locale' => $this->getDefaultLocale()]
                    )
                )
            ),
        ];

        $provider = $this->getProvider($responses);

        $provider->write($translatorBag);
    }

    public function testWriteAddAndUpdateUnit(): void
    {
        $this->xliffFileDumper = new XliffFileDumper();
        $this->loader = new XliffFileLoader();

        [$translatorBag, $catalogue] = $this->getTranslationBag('en');
        $messagesComponentData = DTOFaker::createComponentData('messages');
        $messagesTranslationData = DTOFaker::createTranslationData('en');
        $validatorsComponentData = DTOFaker::createComponentData('validators');
        $validatorsTranslationData = DTOFaker::createTranslationData('en');

        $faker = DTOFaker::getFaker();
        $messages = [];
        foreach ($catalogue->all('messages') as $key => $message) {
            $messages['messages'][$key] = $faker->sentence();
        }
        foreach ($catalogue->all('validators') as $key => $message) {
            $messages['validators'][$key] = $faker->sentence();
            break;
        }
        $messages['messages']['only.on.server'] = $faker->sentence();
        $catalogue->add(['only.on.server' => $messages['messages']['only.on.server']], 'messages');
        $providerCatalogue = new MessageCatalogue('en', $messages);

        $responses = [
            $this->getGetComponentsResponse([$messagesComponentData, $validatorsComponentData]),
            $this->getGetTranslationsResponse($messagesComponentData['translations_url'], [
                $messagesTranslationData,
            ]),
            $this->getDownloadTranslationResponse(
                $messagesTranslationData['file_url'],
                $this->xliffFileDumper->formatCatalogue($providerCatalogue, 'messages', ['default_locale' => $this->getDefaultLocale()])
            ),
            $this->getUploadTranslationResponse(
                $messagesTranslationData['file_url'],
                str_replace(
                    '<trans-unit',
                    '<trans-unit xml:space="preserve"',
                    $this->xliffFileDumper->formatCatalogue(
                        $catalogue,
                        'messages',
                        ['default_locale' => $this->getDefaultLocale()]
                    )
                )
            ),
            $this->getGetTranslationsResponse($validatorsComponentData['translations_url'], [
                $validatorsTranslationData,
            ]),
            $this->getDownloadTranslationResponse(
                $validatorsTranslationData['file_url'],
                $this->xliffFileDumper->formatCatalogue($providerCatalogue, 'validators', ['default_locale' => $this->getDefaultLocale()])
            ),
            $this->getUploadTranslationResponse(
                $validatorsTranslationData['file_url'],
                str_replace(
                    '<trans-unit',
                    '<trans-unit xml:space="preserve"',
                    $this->xliffFileDumper->formatCatalogue(
                        $catalogue,
                        'validators',
                        ['default_locale' => $this->getDefaultLocale()]
                    )
                )
            ),
        ];

        $provider = $this->getProvider($responses);

        $provider->write($translatorBag);
    }

    public function testRead(): void
    {
        $this->xliffFileDumper = new XliffFileDumper();
        $this->loader = new XliffFileLoader();

        [, $catalogue] = $this->getTranslationBag('en');
        $messagesComponentData = DTOFaker::createComponentData('messages');
        $messagesTranslationData = DTOFaker::createTranslationData('en');
        $validatorsComponentData = DTOFaker::createComponentData('validators');
        $validatorsTranslationData = DTOFaker::createTranslationData('en');

        $responses = [
            $this->getGetComponentsResponse([$messagesComponentData, $validatorsComponentData]),
            $this->getGetTranslationsResponse($messagesComponentData['translations_url'], [
                $messagesTranslationData,
            ]),
            $this->getDownloadTranslationResponse(
                $messagesTranslationData['file_url'],
                $this->xliffFileDumper->formatCatalogue($catalogue, 'messages', ['default_locale' => $this->getDefaultLocale()])
            ),
            $this->getGetTranslationsResponse($validatorsComponentData['translations_url'], [
                $validatorsTranslationData,
            ]),
            $this->getDownloadTranslationResponse(
                $validatorsTranslationData['file_url'],
                $this->xliffFileDumper->formatCatalogue($catalogue, 'validators', ['default_locale' => $this->getDefaultLocale()])
            ),
        ];

        $provider = $this->getProvider($responses);

        $provider->read(['messages', 'validators'], ['en']);
    }

    public function testDelete(): void
    {
        $this->xliffFileDumper = new XliffFileDumper();

        [$translatorBag, $catalogue] = $this->getTranslationBag('en');
        $messagesComponentData = DTOFaker::createComponentData('messages');
        $messagesTranslationData = DTOFaker::createTranslationData('en');
        $validatorsComponentData = DTOFaker::createComponentData('validators');
        $validatorsTranslationData = DTOFaker::createTranslationData('en');

        $messagesUnitData = [];
        foreach ($catalogue->all('messages') as $key => $message) {
            $messagesUnitData[] = DTOFaker::createUnitData($key);
        }

        $responses = [
            $this->getGetComponentsResponse([$messagesComponentData, $validatorsComponentData]),
            $this->getGetTranslationsResponse($messagesComponentData['translations_url'], [
                $messagesTranslationData,
            ]),
            $this->getGetUnitsResponse($messagesTranslationData['units_list_url'], $messagesUnitData),
            $this->getDeleteUnitResponse($messagesUnitData[0]['url']),
            $this->getDeleteUnitResponse($messagesUnitData[1]['url']),
            $this->getGetTranslationsResponse($validatorsComponentData['translations_url'], [
                $validatorsTranslationData,
            ]),
            $this->getGetUnitsResponse($validatorsTranslationData['units_list_url'], []),
        ];

        $provider = $this->getProvider($responses);

        $provider->delete($translatorBag);
    }
}
