<?php
namespace App\Entity\Fixture;

use App\Entity;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;

class StationMedia extends AbstractFixture implements DependentFixtureInterface
{
    protected Entity\Repository\StationMediaRepository $mediaRepo;

    public function __construct(Entity\Repository\StationMediaRepository $mediaRepo)
    {
        $this->mediaRepo = $mediaRepo;
    }

    public function load(ObjectManager $em)
    {
        $music_skeleton_dir = getenv('INIT_MUSIC_PATH');

        if (empty($music_skeleton_dir) || !is_dir($music_skeleton_dir)) {
            return;
        }

        /** @var Entity\Station $station */
        $station = $this->getReference('station');

        $station_media_dir = $station->getRadioMediaDir();

        /** @var Entity\StationPlaylist $playlist */
        $playlist = $this->getReference('station_playlist');

        $finder = (new Finder())
            ->files()
            ->in($music_skeleton_dir)
            ->name('/^.+\.(mp3|aac|ogg|flac)$/i');

        foreach ($finder as $file) {
            $file_path = $file->getPathname();
            $file_base_name = basename($file_path);

            // Copy the file to the station media directory.
            copy($file_path, $station_media_dir . '/' . $file_base_name);

            $media_row = $this->mediaRepo->getOrCreate($station, $file_base_name);
            $em->persist($media_row);

            // Add the file to the playlist.
            $spm_row = new Entity\StationPlaylistMedia($playlist, $media_row);
            $spm_row->setWeight(1);
            $em->persist($spm_row);
        }

        $em->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies()
    {
        return [
            Station::class,
            StationPlaylist::class,
        ];
    }
}
