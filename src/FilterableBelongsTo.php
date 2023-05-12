<?php

namespace Sprigs\FilterableBelongsTo;

use Laravel\Nova\Fields\BelongsTo;

class FilterableBelongsTo extends BelongsTo
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'filterable-belongs-to';
}
