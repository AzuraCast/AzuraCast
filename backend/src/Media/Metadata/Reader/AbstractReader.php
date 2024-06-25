<?php

declare(strict_types=1);

namespace App\Media\Metadata\Reader;

use App\Media\Enums\MetadataTags;
use App\Media\MetadataInterface;
use App\Utilities\Arrays;
use App\Utilities\Strings;

abstract class AbstractReader
{
    protected function aggregateMetaTags(MetadataInterface $metadata, array $toProcess): void
    {
        $knownTags = [];
        $extraTags = [];

        foreach ($toProcess as $tagSet) {
            if (empty($tagSet)) {
                continue;
            }

            foreach ($tagSet as $tagName => $tagContents) {
                // Skip pictures
                if (isset($tagContents[0]['data'])) {
                    continue;
                }

                $tagContents = (array)$tagContents;

                // Most metadata is in numbered lists, but some fields (i.e. "text" are hashmaps).
                if (array_is_list($tagContents)) {
                    $tagName = mb_strtolower((string)$tagName);
                    $tagEnum = MetadataTags::getTag($tagName);

                    $tagValues = null !== $tagEnum
                        ? $knownTags[$tagEnum->value] ?? []
                        : $extraTags[$tagName] ?? [];

                    $newTagValues = Arrays::flattenArray($tagContents);
                    foreach ($newTagValues as $newTagValue) {
                        if (0 === count($tagValues) || !in_array($newTagValue, $tagValues, true)) {
                            if (null !== $tagEnum) {
                                $knownTags[$tagEnum->value][] = $newTagValue;
                            } else {
                                $extraTags[$tagName][] = $newTagValue;
                            }
                        }
                    }
                } else {
                    foreach ($tagContents as $tagSubKey => $tagSubValue) {
                        $tagSubKey = mb_strtolower((string)$tagSubKey);
                        $tagSubEnum = MetadataTags::getTag($tagSubKey);

                        $tagValues = null !== $tagSubEnum
                            ? $knownTags[$tagSubEnum->value] ?? []
                            : $extraTags[$tagSubKey] ?? [];

                        if (0 === count($tagValues) || !in_array($tagSubValue, $tagValues, true)) {
                            if (null !== $tagSubEnum) {
                                $knownTags[$tagSubEnum->value][] = $tagSubValue;
                            } else {
                                $extraTags[$tagSubKey][] = $tagSubValue;
                            }
                        }
                    }
                }
            }
        }

        $metadata->setKnownTags(
            array_map(
                fn(array $tagValues) => Strings::stringToUtf8(implode('; ', $tagValues)),
                $knownTags
            )
        );

        $metadata->setExtraTags(
            array_map(
                fn(array $tagValues) => Strings::stringToUtf8(implode('; ', $tagValues)),
                $extraTags
            )
        );
    }
}
