<?php
namespace NexForm;

/**
 * RateLimiter – limits how many times a given IP can submit a form
 * within a rolling time window using a flat JSON file (no extra DB table needed).
 */
class RateLimiter
{
    private string $storePath;

    public function __construct()
    {
        $dir = dirname(NF_DB_SQLITE_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->storePath = $dir . '/rate_limit.json';
    }

    /**
     * Check whether the given IP is within allowed limits.
     *
     * @param  string $ip
     * @param  string $formId
     * @return bool   true = allowed, false = rate-limited
     */
    public function check(string $ip, string $formId = 'default'): bool
    {
        if (!NF_RATE_LIMIT_ENABLED) return true;

        $store  = $this->load();
        $key    = md5($ip . '|' . $formId);
        $now    = time();
        $window = NF_RATE_LIMIT_WINDOW;
        $max    = NF_RATE_LIMIT_MAX;

        // Prune old timestamps
        $timestamps = array_filter(
            $store[$key] ?? [],
            fn($t) => ($now - $t) < $window
        );

        if (count($timestamps) >= $max) {
            return false;
        }

        // Record this attempt
        $timestamps[] = $now;
        $store[$key]  = array_values($timestamps);
        $this->save($store);

        return true;
    }

    /** How many seconds until the oldest entry in the window expires. */
    public function retryAfter(string $ip, string $formId = 'default'): int
    {
        $store = $this->load();
        $key   = md5($ip . '|' . $formId);
        $now   = time();

        $timestamps = array_filter(
            $store[$key] ?? [],
            fn($t) => ($now - $t) < NF_RATE_LIMIT_WINDOW
        );

        if (empty($timestamps)) return 0;
        $oldest = min($timestamps);
        return max(0, NF_RATE_LIMIT_WINDOW - ($now - $oldest));
    }

    private function load(): array
    {
        if (!file_exists($this->storePath)) return [];
        $json = file_get_contents($this->storePath);
        return json_decode($json, true) ?? [];
    }

    private function save(array $data): void
    {
        file_put_contents($this->storePath, json_encode($data), LOCK_EX);
    }
}
