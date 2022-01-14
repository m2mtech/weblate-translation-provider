<?php
/*
 * This file is part of the weblate-translation-provider package.
 *
 * (c) 2022 m2m server software gmbh <tech@m2m.at>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace M2MTech\WeblateTranslationProvider\Api\DTO;

use Spatie\DataTransferObject\FlexibleDataTransferObject;

class Component extends FlexibleDataTransferObject
{
    /** @var string */
    public $slug;

    /** @var string */
    public $url;

    /** @var string */
    public $repository_url;

    /** @var string */
    public $translations_url;

    /** @var bool */
    public $created = false;
}
