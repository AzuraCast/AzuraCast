<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\CustomField;
use App\Entity\Repository\StorageLocationRepository;
use FunctionalTester;

use const JSON_THROW_ON_ERROR;

class Api_Stations_FilesCest extends CestAbstract
{
    /**
     * @before setupComplete
     * @before login
     */
    public function editOnlyRewritesTheFileWhenFileBackedFieldsChange(FunctionalTester $I): void
    {
        $I->wantTo('Only rewrite the media file when a save changes something stored in the file.');

        $station = $this->getTestStation();
        $media = $this->uploadTestSong();

        $localPath = $station->media_storage_location->path . '/' . $media->path;
        $editUrl = '/api/station/' . $station->id . '/file/' . $media->id;

        $mtimeBefore = $media->mtime = time() - 3600;
        $this->em->persist($media);
        $this->em->flush();

        $hashBefore = md5_file($localPath);

        $I->haveHttpHeader('Content-Type', 'application/json');

        $I->sendPut($editUrl, ['playlists' => []]);
        $I->seeResponseCodeIsSuccessful();

        $media = $this->em->refetch($media);

        $I->assertSame($hashBefore, md5_file($localPath));
        $I->assertSame($mtimeBefore, $media->mtime);

        $I->sendPut($editUrl, ['title' => 'Renamed Title']);
        $I->seeResponseCodeIsSuccessful();

        $media = $this->em->refetch($media);

        $I->assertNotSame($hashBefore, md5_file($localPath));
        $I->assertGreaterThan($mtimeBefore, $media->mtime);
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function editCustomFieldRewritesTheFile(FunctionalTester $I): void
    {
        $I->wantTo('Rewrite the media file when a custom field linked to a file tag changes.');

        $this->assertCustomFieldEditRewritesTheFile($I, 'composer');
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function editCustomFieldWithCustomTagRewritesTheFile(FunctionalTester $I): void
    {
        $I->wantTo('Rewrite the media file when a custom field linked to a custom tag name changes.');

        $this->assertCustomFieldEditRewritesTheFile($I, 'Likes');
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function searchListsMediaWhoseFileIsMissing(FunctionalTester $I): void
    {
        $I->wantTo('List media in search results when its file is missing from the media storage.');

        $station = $this->getTestStation();
        $media = $this->uploadTestSong();

        $this->di->get(StorageLocationRepository::class)
            ->getAdapter($station->media_storage_location)
            ->getFilesystem()
            ->delete($media->path);

        $I->sendGet(
            "/api/station/{$station->id}/files/list",
            ['currentDirectory' => '', 'searchPhrase' => '*', 'flushCache' => 1]
        );
        $I->seeResponseCodeIsSuccessful();

        /** @var list<array{path: string, timestamp: int, size: ?int}> $rows */
        $rows = json_decode($I->grabResponse(), true, flags: JSON_THROW_ON_ERROR);

        $I->assertGreaterThan(0, $media->size);
        $I->assertSame([$media->path], array_column($rows, 'path'));
        $I->assertSame($media->mtime, $rows[0]['timestamp']);
        $I->assertSame($media->size, $rows[0]['size']);
    }

    /**
     * @before setupComplete
     * @before login
     */
    public function searchShowsTheStoredMediaSize(FunctionalTester $I): void
    {
        $I->wantTo('Show the stored media size in search results instead of reading it from the media storage.');

        $station = $this->getTestStation();
        $media = $this->uploadTestSong();

        $this->em->createQuery(
            <<<'DQL'
                UPDATE App\Entity\StationMedia sm
                SET sm.size = 1234
                WHERE sm.id = :id
            DQL
        )->setParameter('id', $media->id)
            ->execute();

        $I->sendGet(
            "/api/station/{$station->id}/files/list",
            ['currentDirectory' => '', 'searchPhrase' => '*', 'flushCache' => 1]
        );
        $I->seeResponseCodeIsSuccessful();

        /** @var list<array{size: ?int, media: array{size: int}}> $rows */
        $rows = json_decode($I->grabResponse(), true, flags: JSON_THROW_ON_ERROR);

        $I->assertSame(1234, $rows[0]['size']);
        $I->assertSame(1234, $rows[0]['media']['size']);
    }

    private function assertCustomFieldEditRewritesTheFile(FunctionalTester $I, string $autoAssign): void
    {
        $station = $this->getTestStation();
        $media = $this->uploadTestSong();

        $customField = new CustomField();
        $customField->name = "Linked {$autoAssign}";
        $customField->auto_assign = $autoAssign;
        $this->em->persist($customField);

        $localPath = $station->media_storage_location->path . '/' . $media->path;
        $editUrl = '/api/station/' . $station->id . '/file/' . $media->id;

        $mtimeBefore = $media->mtime = time() - 3600;
        $this->em->persist($media);
        $this->em->flush();

        $hashBefore = md5_file($localPath);

        $I->haveHttpHeader('Content-Type', 'application/json');

        $I->sendPut($editUrl, ['custom_fields' => [$customField->short_name => 'Custom Value']]);
        $I->seeResponseCodeIsSuccessful();

        $media = $this->em->refetch($media);

        $I->assertNotSame($hashBefore, md5_file($localPath));
        $I->assertGreaterThan($mtimeBefore, $media->mtime);

        $this->em->remove($this->em->refetch($customField));
        $this->em->flush();
    }
}
