<?php
namespace App\Radio\Traits;

use GuzzleHttp\Client;

trait AdapterCommon
{
    /** @var Client */
    protected $http_client;

    /**
     * Fetch a remote URL.
     *
     * @param $url
     * @param null $c_opts
     * @return string
     * @throws \App\Exception
     */
    protected function getUrl($url, $c_opts = null): string
    {
        if (APP_TESTING_MODE) {
            return '';
        }

        $defaults = [
            'timeout' => 4,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2) Gecko/20070219 Firefox/2.0.0.2',
            ],
        ];

        if (\is_array($c_opts)) {
            $defaults = array_merge($defaults, $c_opts);
        }

        try {
            $response = $this->http_client->get($url, $defaults);
        } catch(\GuzzleHttp\Exception\ClientException $e) {
            $app_e = new \App\Exception($e->getMessage(), $e->getCode(), $e);
            $app_e->addLoggingContext('url', $url);
            $app_e->addLoggingContext('station_id', $this->station->getId());
            $app_e->addLoggingContext('station_name', $this->station->getName());
            throw $app_e;
        }

        return $response->getBody()->getContents();
    }

    /**
     * Calculate listener count from unique and current totals.
     *
     * @param int $unique_listeners
     * @param int $current_listeners
     * @return int The likely proper "total" listener count.
     */
    protected function getListenerCount($unique_listeners = 0, $current_listeners = 0): int
    {
        $unique_listeners = (int)$unique_listeners;
        $current_listeners = (int)$current_listeners;

        return ($unique_listeners === 0 || $current_listeners === 0)
            ? max($unique_listeners, $current_listeners)
            : min($unique_listeners, $current_listeners);
    }

    /**
     * Given a single title or array, compose a "now playing" current song result.
     *
     * @param $raw_data
     * @param string $delimiter
     * @return array
     */
    protected function getCurrentSong($raw_data, $delimiter = ' - '): array
    {
        if (!\is_array($raw_data)) {
            $raw_data = ['title' => $raw_data];
        }

        if (!empty($raw_data['artist'])) {
            return [
                'artist' => $raw_data['artist'],
                'title' => $raw_data['title'],
                'text' => $raw_data['artist'] . ' - ' . $raw_data['title'],
            ];
        }

        return $this->getSongFromString($raw_data['title'], $delimiter);
    }

    /**
     * Return the artist and title from a string in the format "Artist - Title"
     *
     * @param $song_string
     * @param string $delimiter
     * @return array
     */
    protected function getSongFromString($song_string, $delimiter = '-'): array
    {
        // Fix ShoutCast 2 bug where 3 spaces = " - "
        $song_string = str_replace('   ', ' - ', $song_string);

        // Remove dashes or spaces on both sides of the name.
        $song_string = trim($song_string, " \t\n\r\0\x0B-");

        $string_parts = explode($delimiter, $song_string);

        // If not normally delimited, return "text" only.
        if (count($string_parts) === 1) {
            return ['text' => $song_string, 'artist' => '', 'title' => $song_string];
        }

        // Title is the last element, artist is all other elements (artists are far more likely to have hyphens).
        $title = trim(array_pop($string_parts));
        $artist = trim(implode($delimiter, $string_parts));

        return [
            'text' => $song_string,
            'artist' => $artist,
            'title' => $title,
        ];
    }
}
