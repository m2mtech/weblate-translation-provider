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

class Unit extends FlexibleDataTransferObject
{
    /** @var string */
    public $context;

    /** @var string */
    public $url;
}
