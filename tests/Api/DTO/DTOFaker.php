<?php
/*
 * This file is part of the weblate-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace M2MTech\WeblateTranslationProvider\Tests\Api\DTO;

use Faker\Factory as FakerFactory;
use Faker\Generator as Faker;
use M2MTech\WeblateTranslationProvider\Api\DTO\Component;
use M2MTech\WeblateTranslationProvider\Api\DTO\Translation;
use M2MTech\WeblateTranslationProvider\Api\DTO\Unit;

class DTOFaker
{
    /** @var ?Faker */
    protected static $faker;

    public static function getFaker(): Faker
    {
        if (!self::$faker) {
            self::$faker = FakerFactory::create();
        }

        return self::$faker;
    }

    /**
     * @return array<string,string>
     */
    public static function createComponentData(string $slug = ''): array
    {
        $faker = self::getFaker();
        if (!$slug) {
            $slug = $faker->unique()->slug();
        }

        return [
            'slug' => $slug,
            'url' => $faker->url().'?component_url',
            'repository_url' => $faker->url().'?component_repository_url',
            'translations_url' => $faker->url().'?component_translations_url',
        ];
    }

    public static function createComponent(): Component
    {
        return new Component(self::createComponentData());
    }

    /**
     * @return array<string,string>
     */
    public static function createTranslationData(string $locale = ''): array
    {
        $faker = self::getFaker();
        if (!$locale) {
            $locale = $faker->unique()->languageCode();
        }

        return [
            'language_code' => $locale,
            'filename' => $faker->unique()->filePath(),
            'file_url' => $faker->url().'?translation_file_url',
            'units_list_url' => $faker->url().'?translation_units_list_url',
        ];
    }

    public static function createTranslation(): Translation
    {
        return new Translation(self::createTranslationData());
    }

    /**
     * @return array<string,string>
     */
    public static function createUnitData(string $slug = ''): array
    {
        $faker = self::getFaker();
        if (!$slug) {
            $slug = $faker->unique()->slug();
        }

        return [
            'context' => $slug,
            'url' => $faker->url().'?unit_url',
        ];
    }

    public static function createUnit(): Unit
    {
        return new Unit(self::createUnitData());
    }
}
