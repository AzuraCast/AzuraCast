<?php

declare(strict_types=1);

namespace App\Entity\Enums;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

enum IpSources: string
{
    case Local = 'local';
    case XForwardedFor = 'xff';
    case Cloudflare = 'cloudflare';

    public static function default(): self
    {
        return self::Local;
    }

    public function getIp(ServerRequestInterface $request): string
    {
        if (self::Cloudflare === $this) {
            $ip = $request->getHeaderLine('CF-Connecting-IP');
            if (!empty($ip)) {
                return $this->parseIp($ip);
            }
        }

        if (self::XForwardedFor === $this) {
            $ip = $request->getHeaderLine('X-Forwarded-For');
            if (!empty($ip)) {
                return $this->parseIp($ip);
            }
        }

        $serverParams = $request->getServerParams();
        $ip = $serverParams['REMOTE_ADDR'] ?? null;

        if (empty($ip)) {
            throw new RuntimeException('No IP address attached to this request.');
        }

        return $this->parseIp($ip);
    }

    private function parseIp(string $ip): string
    {
        // Handle the IP being separated by commas.
        if (str_contains($ip, ',')) {
            $ipParts = explode(',', $ip);
            $ip = array_shift($ipParts);
        }

        return trim($ip);
    }
}
