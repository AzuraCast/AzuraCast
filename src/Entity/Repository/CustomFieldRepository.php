<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;

class CustomFieldRepository extends Repository
{
    /**
     * @return Entity\CustomField[]
     */
    public function getAutoAssignableFields(): array
    {
        $fields = [];

        foreach ($this->repository->findAll() as $field) {
            /** @var Entity\CustomField $field */
            if (!$field->hasAutoAssign()) {
                continue;
            }

            $fields[$field->getAutoAssign()] = $field;
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
                $fields[$row['id']] = $row['short_name'] ?? Entity\Station::getStationShortName($row['name']);
            }
        }

        return $fields;
    }

    /**
     * Retrieve a key-value representation of all custom metadata for the specified media.
     *
     * @param Entity\StationMedia $media
     *
     * @return mixed[]
     */
    public function getCustomFields(Entity\StationMedia $media): array
    {
        $metadata_raw = $this->em->createQuery(
            <<<'DQL'
                SELECT cf.short_name, e.value
                FROM App\Entity\StationMediaCustomField e JOIN e.field cf
                WHERE e.media_id = :media_id
            DQL
        )->setParameter('media_id', $media->getId())
            ->getArrayResult();

        $result = [];
        foreach ($metadata_raw as $row) {
            $result[$row['short_name']] = $row['value'];
        }

        return $result;
    }

    /**
     * Set the custom metadata for a specified station based on a provided key-value array.
     *
     * @param Entity\StationMedia $media
     * @param array $custom_fields
     */
    public function setCustomFields(Entity\StationMedia $media, array $custom_fields): void
    {
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\StationMediaCustomField e WHERE e.media_id = :media_id
            DQL
        )->setParameter('media_id', $media->getId())
            ->execute();

        foreach ($custom_fields as $field_id => $field_value) {
            $field = is_numeric($field_id)
                ? $this->em->find(Entity\CustomField::class, $field_id)
                : $this->em->getRepository(Entity\CustomField::class)->findOneBy(['short_name' => $field_id]);

            if ($field instanceof Entity\CustomField) {
                $record = new Entity\StationMediaCustomField($media, $field);
                $record->setValue($field_value);
                $this->em->persist($record);
            }
        }

        $this->em->flush();
    }
}
