<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\CustomField;
use FunctionalTester;

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
