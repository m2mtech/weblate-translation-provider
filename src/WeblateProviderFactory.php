<?php
/*
 * This file is part of the weblate-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace M2MTech\WeblateTranslationProvider;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\Exception\UnsupportedSchemeException;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Provider\AbstractProviderFactory;
use Symfony\Component\Translation\Provider\Dsn;
use Symfony\Component\Translation\Provider\ProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeblateProviderFactory extends AbstractProviderFactory
{
    /** @var HttpClientInterface */
    private $client;

    /** @var LoaderInterface */
    private $loader;

    /** @var LoggerInterface */
    private $logger;

    /** @var XliffFileDumper */
    private $xliffFileDumper;

    /** @var string */
    private $defaultLocale;

    /** @var array<string,string|bool> */
    private $bundleConfig;

    /**
     * @param array<string,string|bool> $bundleConfig
     */
    public function __construct(
        HttpClientInterface $client,
        LoaderInterface $loader,
        LoggerInterface $logger,
        XliffFileDumper $xliffFileDumper,
        string $defaultLocale,
        array $bundleConfig
    ) {
        $this->client = $client;
        $this->loader = $loader;
        $this->logger = $logger;
        $this->xliffFileDumper = $xliffFileDumper;

        $this->defaultLocale = $defaultLocale;

        $this->bundleConfig = $bundleConfig;
    }

    protected function getSupportedSchemes(): array
    {
        return ['weblate'];
    }

    public function create(Dsn $dsn): ProviderInterface
    {
        if ('weblate' !== $dsn->getScheme()) {
            throw new UnsupportedSchemeException($dsn, 'weblate', $this->getSupportedSchemes());
        }

        $endpoint = $dsn->getHost();
        $endpoint .= $dsn->getPort() ? ':'.$dsn->getPort() : '';
        $path = trim($dsn->getPath(), '/');
        if (strlen($path) > 0) {
            $path = '/'.$path;
        }
        $api = $this->bundleConfig['https'] ? 'https://' : 'http://';
        $api .= $endpoint.$path.'/api/';

        $client = ScopingHttpClient::forBaseUri(
            $this->client,
            $api,
            [
                'headers' => [
                    'Authorization' => 'Token '.$this->getPassword($dsn),
                ],
                'verify_peer' => $this->bundleConfig['verify_peer'],
            ],
            preg_quote($api, '/')
        );

        return new WeblateProvider(
            $client,
            $this->loader,
            $this->logger,
            $this->xliffFileDumper,
            $this->defaultLocale,
            $endpoint,
            $this->getUser($dsn)
        );
    }
}
