<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\CustomField;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationMediaCustomField;

/**
 * @extends Repository<CustomField>
 */
final class CustomFieldRepository extends Repository
{
    protected string $entityClass = CustomField::class;

    /**
     * @return CustomField[]
     */
    public function getAutoAssignableFields(): array
    {
        $fields = [];

        foreach ($this->repository->findAll() as $field) {
            /** @var CustomField $field */
            if (empty($field->auto_assign)) {
                continue;
            }

            $fields[$field->auto_assign] = $field;
        }

        return $fields;
    }

    /**
     * @return string[]
     */
    public function getFieldIds(): array
    {
        static $fields;

        if (!isset($fields)) {
            $fields = [];
            $fieldsRaw = $this->em->createQuery(
                <<<'DQL'
                    SELECT cf.id, cf.name, cf.short_name
                    FROM App\Entity\CustomField cf
                    ORDER BY cf.name ASC
                DQL
            )->getArrayResult();

            foreach ($fieldsRaw as $row) {
                $fields[$row['id']] = $row['short_name'] ?? Station::generateShortName($row['name']);
            }
        }

        return $fields;
    }

    /**
     * Retrieve a key-value representation of all custom metadata for the specified media.
     *
     * @param StationMedia $media
     *
     * @return mixed[]
     */
    public function getCustomFields(StationMedia $media): array
    {
        $metadataRaw = $this->em->createQuery(
            <<<'DQL'
                SELECT cf.short_name, e.value
                FROM App\Entity\StationMediaCustomField e JOIN e.field cf
                WHERE e.media = :media
            DQL
        )->setParameter('media', $media)
            ->getArrayResult();

        return array_column($metadataRaw, 'value', 'short_name');
    }

    /**
     * Set the custom metadata for a specified station based on a provided key-value array.
     *
     * @param StationMedia $media
     * @param array $customFields
     */
    public function setCustomFields(StationMedia $media, array $customFields): void
    {
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationMediaCustomField e WHERE e.media = :media
            DQL
        )->setParameter('media', $media)
            ->execute();

        /** @var array<array-key, CustomField> $customFieldLookup */
        $customFieldLookup = [];
        foreach ($this->getRepository()->findAll() as $customField) {
            $customFieldLookup[$customField->id] = $customField;
            $customFieldLookup[$customField->short_name] = $customField;
        }

        foreach ($customFields as $fieldId => $fieldValue) {
            if (isset($customFieldLookup[$fieldId])) {
                $record = new StationMediaCustomField(
                    $media,
                    $customFieldLookup[$fieldId]
                );
                $record->value = $fieldValue;

                $this->em->persist($record);
            }
        }

        $this->em->flush();
    }
}
