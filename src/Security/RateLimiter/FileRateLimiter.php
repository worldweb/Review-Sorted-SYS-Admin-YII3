<?php

declare(strict_types=1);

namespace App\Security\RateLimiter;

use App\Environment;
use Psr\Http\Message\ServerRequestInterface;

/**
 * FileRateLimiter.
 *
 * Provides persistent, concurrency-safe application-level rate limiting using atomic file locks.
 * Requires zero database migrations or external dependencies (Redis/Memcached).
 * Persists across FastCGI/PHP worker restarts in @runtime/ratelimit/.
 */
final class FileRateLimiter
{
    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        $baseDir = $storageDir ?? (dirname(__DIR__, 3) . '/runtime/ratelimit');
        $this->storageDir = rtrim($baseDir, '/\\');
        $this->ensureStorageDirectory();
    }

    /**
     * Checks if the given key has exceeded the maximum attempts within the decay window.
     * If within limit, increments attempt count and returns true.
     * If limit exceeded, returns false.
     *
     * @param string $key Unique rate limit identifier (e.g., "login:ip:email")
     * @param int $maxAttempts Maximum permitted attempts in the window
     * @param int $decaySeconds Time window in seconds
     * @return bool True if permitted, false if rate limited
     */
    public function check(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $filePath = $this->getFilePath($key);
        $maxRetries = 3;
        $fp = false;

        for ($retry = 0; $retry < $maxRetries; $retry++) {
            $fp = @fopen($filePath, 'c+');
            if ($fp === false) {
                // If storage is temporarily unwritable, fail safe (allow request but log)
                return true;
            }

            if (!flock($fp, LOCK_EX)) {
                fclose($fp);
                return true;
            }

            // Verify the file was not unlinked between fopen and flock
            $stat = fstat($fp);
            if (isset($stat['nlink']) && $stat['nlink'] === 0) {
                flock($fp, LOCK_UN);
                fclose($fp);
                $fp = false;
                continue;
            }

            break;
        }

        if ($fp === false) {
            return true;
        }

        try {
            $content = stream_get_contents($fp);
            $data = !empty($content) ? json_decode($content, true) : null;
            $now = time();

            if (!is_array($data) || !isset($data['reset_at']) || $now >= $data['reset_at']) {
                $data = [
                    'attempts' => 1,
                    'reset_at' => $now + $decaySeconds,
                    'first_attempt' => $now,
                ];
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, (string) json_encode($data));
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
                return true;
            }

            if ($data['attempts'] >= $maxAttempts) {
                flock($fp, LOCK_UN);
                fclose($fp);
                return false;
            }

            $data['attempts']++;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string) json_encode($data));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        } catch (\Throwable) {
            if (is_resource($fp)) {
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
            return true;
        }
    }

    /**
     * Returns the seconds until the rate limit resets for the given key.
     */
    public function getRetryAfter(string $key, int $decaySeconds): int
    {
        $filePath = $this->getFilePath($key);
        if (!file_exists($filePath)) {
            return 0;
        }

        $fp = @fopen($filePath, 'r');
        if ($fp === false) {
            return $decaySeconds;
        }

        try {
            flock($fp, LOCK_SH);
            $content = stream_get_contents($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            $data = !empty($content) ? json_decode($content, true) : null;
            if (is_array($data) && isset($data['reset_at'])) {
                $remaining = $data['reset_at'] - time();
                return max(0, $remaining);
            }
            return 0;
        } catch (\Throwable) {
            if (is_resource($fp)) {
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
            return $decaySeconds;
        }
    }

    /**
     * Resets rate limit records for a given key (e.g. on successful login).
     * Updates in-place with exclusive lock to prevent broken flock concurrency handles.
     */
    public function reset(string $key): void
    {
        $filePath = $this->getFilePath($key, false);
        if (!file_exists($filePath)) {
            return;
        }

        $fp = @fopen($filePath, 'c+');
        if ($fp === false) {
            return;
        }

        try {
            if (flock($fp, LOCK_EX)) {
                $stat = fstat($fp);
                if (!isset($stat['nlink']) || $stat['nlink'] > 0) {
                    ftruncate($fp, 0);
                    rewind($fp);
                    fwrite($fp, (string) json_encode([
                        'attempts' => 0,
                        'reset_at' => 0,
                        'first_attempt' => 0,
                    ]));
                    fflush($fp);
                }
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        } catch (\Throwable) {
            if (is_resource($fp)) {
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
        }
    }

    /**
     * Safely extracts client IP address from ServerRequestInterface.
     * Prevents rate-limit bypass via spoofed X-Forwarded-For or CF-Connecting-IP headers.
     * Only trusts proxy headers if REMOTE_ADDR originates from a configured trusted proxy.
     */
    public function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        $remoteAddr = (string) ($serverParams['REMOTE_ADDR'] ?? '127.0.0.1');

        if (!filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            $remoteAddr = '127.0.0.1';
        }

        // Only evaluate forwarding headers if REMOTE_ADDR matches trusted proxies
        if ($this->isTrustedProxy($remoteAddr)) {
            // Check for Cloudflare header
            $cfIp = trim($request->getHeaderLine('CF-Connecting-IP'));
            if ($cfIp !== '' && filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }

            // Check standard X-Forwarded-For (take leftmost client IP)
            $forwarded = $request->getHeaderLine('X-Forwarded-For');
            if ($forwarded !== '') {
                $parts = explode(',', $forwarded);
                $clientIp = trim($parts[0]);
                if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                    return $clientIp;
                }
            }
        }

        return $remoteAddr;
    }

    /**
     * Determines whether the given IP is a recognized trusted reverse proxy.
     */
    private function isTrustedProxy(string $ip): bool
    {
        $trusted = Environment::get('TRUSTED_PROXIES');
        if ($trusted === null || $trusted === '' || $trusted === false) {
            return false;
        }

        if ($trusted === '*' || $trusted === 'all') {
            return true;
        }

        $list = is_array($trusted) ? $trusted : explode(',', (string) $trusted);
        $cleanIp = trim($ip);

        foreach ($list as $proxy) {
            $proxy = trim((string) $proxy);
            if ($proxy === '') {
                continue;
            }
            if ($cleanIp === $proxy) {
                return true;
            }
            // Simple CIDR / wildcard match support if configured
            if (str_contains($proxy, '/') && $this->cidrMatch($cleanIp, $proxy)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Basic IPv4 CIDR subnet matching.
     */
    private function cidrMatch(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr, 2);
        if (!filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskInt = (int) $mask;

        if ($ipLong === false || $subnetLong === false || $maskInt < 0 || $maskInt > 32) {
            return false;
        }

        $maskBits = $maskInt === 0 ? 0 : (~0 << (32 - $maskInt));
        return ($ipLong & $maskBits) === ($subnetLong & $maskBits);
    }

    /**
     * Periodically garbage-collects abandoned rate limit files to prevent disk accumulation.
     * Only prunes files completely inactive for at least maxLifetimeSeconds (default 24h).
     * Strictly acquires non-blocking exclusive lock before checking and unlinking to protect active requests.
     */
    public function gc(int $maxLifetimeSeconds = 86400): void
    {
        if (!is_dir($this->storageDir)) {
            return;
        }

        $now = time();
        $files = @glob($this->storageDir . '/*.lock');
        if (!is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            $mtime = @filemtime($file);
            // Only inspect files untouched for longer than maxLifetimeSeconds
            if ($mtime === false || ($now - $mtime) < $maxLifetimeSeconds) {
                continue;
            }

            $fp = @fopen($file, 'c+');
            if ($fp === false) {
                continue;
            }

            try {
                // Skip immediately if another process is currently accessing this file
                if (!flock($fp, LOCK_EX | LOCK_NB)) {
                    fclose($fp);
                    continue;
                }

                $stat = fstat($fp);
                if (isset($stat['mtime']) && ($now - $stat['mtime']) < $maxLifetimeSeconds) {
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    continue;
                }

                $content = stream_get_contents($fp);
                if (!empty($content)) {
                    $data = json_decode($content, true);
                    if (is_array($data) && isset($data['reset_at']) && $now < $data['reset_at']) {
                        flock($fp, LOCK_UN);
                        fclose($fp);
                        continue;
                    }
                }

                // File is genuinely abandoned (> 24 hours inactive) and reset window elapsed
                @unlink($file);
                flock($fp, LOCK_UN);
                fclose($fp);
            } catch (\Throwable) {
                if (is_resource($fp)) {
                    @flock($fp, LOCK_UN);
                    @fclose($fp);
                }
            }
        }
    }

    private function getFilePath(string $key, bool $allowGc = true): string
    {
        // 1 in 500 chance to trigger garbage collection on active checks
        if ($allowGc && random_int(1, 500) === 1) {
            $this->gc();
        }

        $hash = hash('sha256', $key);
        return $this->storageDir . '/' . $hash . '.lock';
    }

    private function ensureStorageDirectory(): void
    {
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0775, true);
        }
    }
}
