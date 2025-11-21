<?php

namespace Reach\StatamicEasyForms\Tags\Concerns;

use Statamic\Facades\Dictionary;

trait HandlesDictionaries
{
    /**
     * Get dictionary options.
     *
     * @param  string|array  $dictionary
     * @return array
     */
    protected function getDictionaryOptions($dictionary): array
    {
        $dictionaryHandle = is_array($dictionary) ? $dictionary['type'] : $dictionary;
        $dictionaryInstance = Dictionary::find($dictionaryHandle);

        if (! $dictionaryInstance) {
            return [];
        }

        return collect($dictionaryInstance->optionItems())
            ->map(fn ($item) => $item->toArray())
            ->values()
            ->all();
    }
}
