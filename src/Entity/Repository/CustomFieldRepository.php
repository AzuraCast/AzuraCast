<?php
namespace App\Entity\Repository;

use App\Entity;
use Azura\Doctrine\Repository;

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
     * Retrieve a key-value representation of all custom metadata for the specified media.
     *
     * @param Entity\StationMedia $media
     *
     * @return array
     */
    public function getCustomFields(Entity\StationMedia $media): array
    {
        $metadata_raw = $this->em->createQuery(/** @lang DQL */ 'SELECT e 
            FROM App\Entity\StationMediaCustomField e 
            WHERE e.media_id = :media_id')
            ->setParameter('media_id', $media->getId())
            ->getArrayResult();

        $result = [];
        foreach ($metadata_raw as $row) {
            $result[$row['field_id']] = $row['value'];
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
        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\StationMediaCustomField e WHERE e.media_id = :media_id')
            ->setParameter('media_id', $media->getId())
            ->execute();

        foreach ($custom_fields as $field_id => $field_value) {
            /** @var Entity\CustomField $field */
            $field = $this->em->getReference(Entity\CustomField::class, $field_id);

            $record = new Entity\StationMediaCustomField($media, $field);
            $record->setValue($field_value);
            $this->em->persist($record);
        }

        $this->em->flush();
    }
}