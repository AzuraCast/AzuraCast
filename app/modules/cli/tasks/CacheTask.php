<?php
use \DF\Phalcon\Cli\Task;
use \PVL\SyncManager;

class CacheTask extends Task
{
    public function clearAction()
    {
        // Flush local cache.
        \DF\Cache::clean();

        $this->printLn('Local cache flushed.');

        // Flush CloudFlare cache.
        if (DF_APPLICATION_ENV != 'production') {
            $apis = $this->config->apis->toArray();
            if (isset($apis['cloudflare'])) {
                $url_base = 'https://www.cloudflare.com/api_json.html';
                $url_params = array(
                    'tkn' => $apis['cloudflare']['api_key'],
                    'email' => $apis['cloudflare']['email'],
                    'a' => 'fpurge_ts',
                    'z' => $apis['cloudflare']['domain'],
                    'v' => '1',
                );

                $url = $url_base . '?' . http_build_query($url_params);
                $result_raw = @file_get_contents($url);

                $result = @json_decode($result_raw, true);

                if ($result['result'] == 'success')
                    $this->printLn('CloudFlare cache flushed successfully.');
                else
                    $this->printLn('CloudFlare error: ' . $result['msg']);
            }
        }
    }
}