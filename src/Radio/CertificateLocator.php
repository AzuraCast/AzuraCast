<?php

declare(strict_types=1);

namespace App\Radio;

use App\Environment;

class CertificateLocator
{
    public static function findCertificate(): Certificate
    {
        $environment = Environment::getInstance();

        if (!empty($_ENV['VIRTUAL_HOST']) && $environment->isDockerRevisionAtLeast(10)) {
            $vhost = $_ENV['VIRTUAL_HOST'];

            // Check environment variable for a virtual host.
            $certBase = '/etc/nginx/certs';

            if (is_dir($certBase)) {
                $domainKey = $certBase . '/' . $vhost . '.key';
                $domainCert = $certBase . '/' . $vhost . '.crt';

                if (file_exists($domainKey) && file_exists($domainCert)) {
                    return new Certificate($domainKey, $domainCert);
                }

                $defaultKey = $certBase . '/default.key';
                $defaultCert = $certBase . '/default.crt';

                if (file_exists($defaultKey) && file_exists($defaultCert)) {
                    return new Certificate($defaultKey, $defaultCert);
                }
            }
        }

        return self::getDefaultCertificates();
    }

    public static function getDefaultCertificates(): Certificate
    {
        $environment = Environment::getInstance();

        if ($environment->isDocker()) {
            return new Certificate('/etc/nginx/ssl.key', '/etc/nginx/ssl.crt');
        }

        return new Certificate('/etc/nginx/ssl/server.key', '/etc/nginx/ssl/server.crt');
    }
}
