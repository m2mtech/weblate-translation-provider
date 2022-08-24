<?php
/*
 * This file is part of the weblate-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace M2MTech\WeblateTranslationProvider\Api;

use M2MTech\WeblateTranslationProvider\Api\DTO\Component;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Translation\Exception\ProviderException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ComponentApi
{
    /** @var array<string,Component> */
    private static $components = [];

    /** @var HttpClientInterface */
    private static $client;

    /** @var LoggerInterface */
    private static $logger;

    /** @var string */
    private static $project;

    /** @var string */
    private static $defaultLocale;

    /** @var bool */
    private static $useHttps;

    public static function setup(
        HttpClientInterface $client,
        LoggerInterface $logger,
        string $project,
        string $defaultLocale,
        bool $useHttps,
    ): void {
        self::$client = $client;
        self::$logger = $logger;
        self::$project = $project;
        self::$defaultLocale = $defaultLocale;
        self::$useHttps = $useHttps;

        self::$components = [];
    }

    /**
     * @throws ExceptionInterface
     *
     * @return array<string,Component>
     */
    public static function getComponents(bool $reload = false): array
    {
        if ($reload) {
            self::$components = [];
        }

        if (self::$components) {
            return self::$components;
        }

        /**
         * GET /api/projects/(string: project)/components/.
         *
         * @see https://docs.weblate.org/en/latest/api.html#get--api-projects-(string-project)-components-
         */
        $response = self::$client->request('GET', 'projects/'.self::$project.'/components/');

        if (200 !== $response->getStatusCode()) {
            self::$logger->debug($response->getStatusCode().': '.$response->getContent(false));
            throw new ProviderException('Unable to get weblate components.', $response);
        }

        $results = $response->toArray()['results'];

        foreach ($results as $result) {
            $component = new Component($result);

            if ('glossary' === $component->slug) {
                continue;
            }

            self::$components[$component->slug] = $component;
            self::$logger->debug('Loaded component '.$component->slug);
        }

        return self::$components;
    }

    /**
     * @throws ExceptionInterface
     */
    public static function hasComponent(string $slug): bool
    {
        self::getComponents();

        if (isset(self::$components[$slug])) {
            return true;
        }

        return false;
    }

    /**
     * @throws ExceptionInterface
     */
    public static function getComponent(string $slug, string $optionalContent = ''): ?Component
    {
        if (self::hasComponent($slug)) {
            return self::$components[$slug];
        }

        if (!$optionalContent) {
            return null;
        }

        return self::addComponent($slug, $optionalContent);
    }

    /**
     * @throws ExceptionInterface
     */
    public static function addComponent(string $domain, string $content): Component
    {
        $content = str_replace('<trans-unit', '<trans-unit xml:space="preserve"', $content);

        /**
         * POST /api/projects/(string: project)/components/.
         *
         * @see https://docs.weblate.org/en/latest/api.html#post--api-projects-(string-project)-components-
         */
        $formFields = [
            'name' => $domain,
            'slug' => $domain,
            'edit_template' => 'true',
            'manage_units' => 'true',
            'source_language' => self::$defaultLocale,
            'file_format' => 'xliff',
            'docfile' => new DataPart($content, $domain.'/'.self::$defaultLocale.'.xlf'),
        ];
        $formData = new FormDataPart($formFields);

        $response = self::$client->request('POST', 'projects/'.self::$project.'/components/', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToString(),
        ]);

        if (201 !== $response->getStatusCode()) {
            self::$logger->debug($response->getStatusCode().': '.$response->getContent(false));
            throw new ProviderException('Unable to add weblate component '.$domain.'.', $response);
        }

        $result = $response->toArray();
        $component = new Component($result);
        $component->created = true;
        self::$components[$component->slug] = $component;

        self::$logger->debug('Added component '.$component->slug);

        return $component;
    }

    /**
     * @throws ExceptionInterface
     */
    public static function deleteComponent(Component $component): void
    {
        /**
         * DELETE /api/components/(string: project)/(string: component)/.
         *
         * @see https://docs.weblate.org/en/latest/api.html#delete--api-components-(string-project)-(string-component)-
         */
        $response = self::$client->request('DELETE', $component->url);

        if (204 !== $response->getStatusCode()) {
            self::$logger->debug($response->getStatusCode().': '.$response->getContent(false));
            throw new ProviderException('Unable to delete weblate component '.$component->slug.'.', $response);
        }

        unset(self::$components[$component->slug]);

        self::$logger->debug('Deleted component '.$component->slug);
    }

    /**
     * @throws ExceptionInterface
     */
    public static function commitComponent(Component $component): void
    {
        /**
         * POST /api/components/(string: project)/(string: component)/repository/.
         *
         * @see https://docs.weblate.org/en/latest/api.html#post--api-components-(string-project)-(string-component)-repository-
         */
        $response = self::$client->request('POST', $component->repository_url, [
            'body' => ['operation' => 'commit'],
        ]);

        if (200 !== $response->getStatusCode()) {
            self::$logger->debug($response->getStatusCode().': '.$response->getContent(false));
            throw new ProviderException('Unable to commit weblate component '.$component->slug.'.', $response);
        }

        self::$logger->debug('Committed component '.$component->slug);
    }
}
