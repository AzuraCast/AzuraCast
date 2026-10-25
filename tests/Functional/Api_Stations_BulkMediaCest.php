<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\CustomField;
use App\Entity\Repository\CustomFieldRepository;
use App\Entity\StationMedia;
use App\Entity\StationMediaMetadata;
use App\Utilities\File;
use Codeception\Util\Shared\Asserts;
use FunctionalTester;
use League\Csv\Reader;
use Symfony\Component\Filesystem\Filesystem;

class Api_Stations_BulkMediaCest extends CestAbstract
{
    use Asserts;

    /**
     * @before setupComplete
     * @before login
     */
    public function downloadEscapesFormulaCells(FunctionalTester $I): void
    {
        $I->wantTo('Download a bulk media CSV with formula cells escaped via API.');

        $customField = $this->createCustomField();

        $media = $this->uploadSongWithFormulaCells($customField);
        $media->extra_metadata = [StationMediaMetadata::AMPLIFY => -1.5];
        $this->em->persist($media);
        $this->em->flush();

        $csvReader = Reader::fromString($this->downloadBulkMediaCsv($I));
        $csvReader->setHeaderOffset(0);
        $mediaRow = $csvReader->first();

        $this->assertSame("'=1+1", $mediaRow['title']);
        $this->assertSame("'-M-", $mediaRow['artist']);
        $this->assertSame("'@SUM(A1)", $mediaRow["custom_field_{$customField->short_name}"]);
        $this->assertSame('-1.5', $mediaRow[StationMediaMetadata::AMPLIFY]);
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function uploadRestoresEscapedFormulaCells(FunctionalTester $I): void
    {
        $I->wantTo('Preview an unchanged bulk media CSV with formula cells via API.');

        $this->uploadSongWithFormulaCells($this->createCustomField());

        $csvPath = File::generateTempPath('bulk_media.csv');
        (new Filesystem())->dumpFile($csvPath, $this->downloadBulkMediaCsv($I));

        $I->sendPost("{$this->bulkMediaUrl()}/preview", [], ['file' => $csvPath]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['totalChanges' => 0]);
    }

    private function createCustomField(): CustomField
    {
        $customField = new CustomField();
        $customField->name = 'Formula Field';

        $this->em->persist($customField);
        $this->em->flush();

        return $customField;
    }

    private function uploadSongWithFormulaCells(CustomField $customField): StationMedia
    {
        $media = $this->uploadTestSong();
        $media->title = '=1+1';
        $media->artist = '-M-';

        $this->em->persist($media);
        $this->em->flush();

        $this->di->get(CustomFieldRepository::class)->setCustomFields(
            $media,
            [$customField->id => '@SUM(A1)']
        );

        return $media;
    }

    private function downloadBulkMediaCsv(FunctionalTester $I): string
    {
        $I->sendGet($this->bulkMediaUrl());
        $I->seeResponseCodeIs(200);

        return $I->grabResponse();
    }

    private function bulkMediaUrl(): string
    {
        return "/api/station/{$this->getTestStation()->id}/files/bulk";
    }
}
