<?php

declare(strict_types=1);

namespace App\Media\Metadata\Reader;

use App\Media\Enums\MetadataTags;
use App\Media\MetadataInterface;
use App\Utilities\Arrays;
use App\Utilities\Strings;

abstract class AbstractReader
{
    protected function aggregateMetaTags(MetadataInterface $metadata, array $toProcessRaw): void
    {
        $toProcess = [];

        // Restructure the incoming array to handle nested hashmaps.
        foreach ($toProcessRaw as $tagSet) {
            if (empty($tagSet)) {
                continue;
            }

            foreach ($tagSet as $tagName => $tagContents) {
                $tagContents = (array)$tagContents;

                if (empty($tagContents)) {
                    continue;
                }

                // Skip pictures
                if (isset($tagContents[0]['data'])) {
                    continue;
                }

                if (array_is_list($tagContents)) {
                    $toProcess[$tagName][] = $tagContents;
                } else {
                    foreach ($tagContents as $tagSubKey => $tagSubValue) {
                        if (empty($tagSubValue)) {
                            continue;
                        }

                        $toProcess[$tagSubKey][] = $tagSubValue;
                    }
                }
            }
        }

        $knownTags = [];
        $extraTags = [];

        foreach ($toProcess as $tagName => $tagContents) {
            $tagName = mb_strtolower((string)$tagName);
            $tagEnum = MetadataTags::getTag($tagName);

            $newTagValues = Strings::stringToUtf8(
                implode('; ', array_unique(Arrays::flattenArray($tagContents)))
            );
            if (null !== $tagEnum) {
                $knownTags[$tagEnum->value] = $newTagValues;
            } else {
                $extraTags[$tagName] = $newTagValues;
            }
        }

        $metadata->setKnownTags($knownTags);
        $metadata->setExtraTags($extraTags);
    }
}
