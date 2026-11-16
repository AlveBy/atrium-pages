<?php

declare(strict_types=1);

namespace Atrium\Pages\Fields;

use Atrium\Admin\Fields\FieldContext;
use Atrium\Admin\Fields\SlugField;

/**
 * The page code: a slug that also takes capitals and underscores, because
 * pages moved from Bitrix keep their old addresses (/price_list).
 */
final class PageCodeField extends SlugField
{
    public const PATTERN = '/^[A-Za-z0-9_\-]+$/';

    public function getValidationRules(?FieldContext $context = null): array
    {
        return [...$this->baseValidationRules($context), 'string', 'max:255', 'regex:'.self::PATTERN];
    }
}
