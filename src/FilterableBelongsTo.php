<?php

namespace SPRIGS\FilterableBelongsTo;

use Laravel\Nova\Http\Requests\NovaRequest;
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
