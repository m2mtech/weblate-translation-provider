<?php
/*
 * This file is part of the weblate-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace M2MTech\WeblateTranslationProvider\Tests\Api;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class ApiTest extends TestCase
{
    protected function getResponse(
        string $expectedUrl,
        string $expectedMethod,
        string $expectedBody,
        string $result,
        int $statusCode = 200
    ): callable {
        return function (string $method, string $url, array $options = []) use ($expectedUrl, $expectedMethod, $expectedBody, $result, $statusCode): ResponseInterface {
            $this->assertStringEndsWith($expectedUrl, $url);
            $this->assertSame($expectedMethod, $method);
            if ($expectedBody) {
                $this->assertStringContainsString($expectedBody, $options['body']);
            }

            return new MockResponse($result, ['http_code' => $statusCode]);
        };
    }
}
