<?php
namespace App\Entity\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity;

class StationMedia extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $em)
    {
        $music_skeleton_dir = getenv('INIT_MUSIC_PATH');

        if (!empty($music_skeleton_dir) && is_dir($music_skeleton_dir)) {

            /** @var Entity\Station $station */
            $station = $this->getReference('station');

            $station_media_dir = $station->getRadioMediaDir();

            /** @var Entity\StationPlaylist $playlist */
            $playlist = $this->getReference('station_playlist');

            /** @var Entity\Repository\StationMediaRepository $media_repo */
            $media_repo = $em->getRepository(StationMedia::class);

            $directory = new \RecursiveDirectoryIterator($music_skeleton_dir);
            $iterator = new \RecursiveIteratorIterator($directory);

            $i = 1;

            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $file_path = $file->getPathname();
                $file_base_name = basename($file_path);

                // Copy the file to the station media directory.
                copy($file_path, $station_media_dir . '/' . $file_base_name);

                $media_row = $media_repo->getOrCreate($station, $file_base_name);
                $em->persist($media_row);

                // Add the file to the playlist.
                $spm_row = new Entity\StationPlaylistMedia($playlist, $media_row);
                $spm_row->setWeight($i);
                $em->persist($spm_row);

                $i++;
            }
        }

        $em->flush();
    }

    public function getDependencies()
    {
        return [
            Station::class,
            StationPlaylist::class,
        ];
    }
}
