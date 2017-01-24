<?php
namespace Entity\Repository;

use Entity;

class StationMediaRepository extends \App\Doctrine\Repository
{
    /**
     * @param Entity\Station $station
     * @return array
     */
    public function getRequestable(Entity\Station $station)
    {
        return $this->_em->createQuery('SELECT sm FROM ' . $this->_entityName . ' sm WHERE sm.station_id = :station_id ORDER BY sm.artist ASC, sm.title ASC')
            ->setParameter('station_id', $station->id)
            ->getArrayResult();
    }

    /**
     * @param Entity\Station $station
     * @param $artist_name
     * @return array
     */
    public function getByArtist(Entity\Station $station, $artist_name)
    {
        return $this->_em->createQuery('SELECT sm FROM ' . $this->_entityName . ' sm WHERE sm.station_id = :station_id AND sm.artist LIKE :artist ORDER BY sm.title ASC')
            ->setParameter('station_id', $station->id)
            ->setParameter('artist', $artist_name)
            ->getArrayResult();
    }

    /**
     * @param Entity\Station $station
     * @param $query
     * @return array
     */
    public function search(Entity\Station $station, $query)
    {
        $db = $this->_em->getConnection();
        $table_name = $this->_em->getClassMetadata(__CLASS__)->getTableName();

        $stmt = $db->executeQuery('SELECT sm.* FROM ' . $db->quoteIdentifier($table_name) . ' AS sm WHERE sm.station_id = ? AND CONCAT(sm.title, \' \', sm.artist, \' \', sm.album) LIKE ?',
            [$station->id, '%' . addcslashes($query, "%_") . '%']);
        $results = $stmt->fetchAll();

        return $results;
    }

    /**
     * @param Entity\Station $station
     * @param $path
     * @return Entity\StationMedia
     */
    public function getOrCreate(Entity\Station $station, $path)
    {
        $short_path = ltrim(str_replace($station->getRadioMediaDir(), '', $path), '/');

        $record = $this->findOneBy(['station_id' => $station->id, 'path' => $short_path]);

        if (!($record instanceof Entity\StationMedia)) {
            $record = new Entity\StationMedia;
            $record->station = $station;
            $record->path = $short_path;
        }

        try {
            $song_info = $record->loadFromFile();
            if (!empty($song_info)) {
                $record->song = $this->_em->getRepository(Entity\Song::class)->getOrCreate($song_info);
            }
        } catch (\Exception $e) {
            $record->moveToNotProcessed();
            throw $e;
        }

        return $record;
    }
}