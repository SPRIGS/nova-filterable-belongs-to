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

    public $hiddenFields = [];

    /**
     * Hide fields.
     *
     * @return $this
     */
    public function hideFields($hiddenFieldsArray = [])
    {
        $this->hiddenFields = $hiddenFieldsArray;
        return $this;
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        /** @phpstan-ignore-next-line */
        return with(app(NovaRequest::class), function ($request) {
            return array_merge([
                'hiddenFields' => $this->hiddenFields,
            ], parent::jsonSerialize());
        });
    }
}
