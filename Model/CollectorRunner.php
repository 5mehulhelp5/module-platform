<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Magenx\Platform\Model;

use Magenx\Platform\Model\Collector\CollectorInterface;
use Magenx\Platform\Model\Metric\Status;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * Runs one collector and hands back the payload the metrics endpoint returns.
 *
 * This is where failure isolation and rate limiting live, so that no collector
 * has to reimplement either: any \Throwable becomes an "unavailable" tab, and a
 * snapshot is reused for cache_ttl seconds so a held-down browser refresh
 * cannot turn the dashboard into a load generator against the stack it watches.
 */
class CollectorRunner
{
    public const CACHE_TAG = 'MAGENX_PLATFORM';

    private const CACHE_PREFIX = 'magenx_platform_';

    private CacheInterface $cache;

    private SerializerInterface $serializer;

    private Config $config;

    private LoggerInterface $logger;

    /**
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param string $code
     * @param CollectorInterface $collector
     * @return array
     */
    public function run(string $code, CollectorInterface $collector): array
    {
        $ttl = $this->config->getCacheTtl();
        $cacheKey = self::CACHE_PREFIX . $code;

        if ($ttl > 0) {
            $cached = $this->cache->load($cacheKey);
            if (is_string($cached) && $cached !== '') {
                $payload = $this->serializer->unserialize($cached);
                if (is_array($payload)) {
                    $payload['cached'] = true;

                    return $payload;
                }
            }
        }

        $startedAt = microtime(true);

        try {
            $payload = $collector->collect()->toArray();
        } catch (\Throwable $e) {
            // Never re-throw: one unreachable backend must cost one red tab, not
            // the whole page. The reason is shown to the admin because it is the
            // only diagnostic they get, and backend clients put hosts but not
            // passwords into these messages.
            $this->logger->warning(
                sprintf('Magenx_Platform: collector "%s" failed: %s', $code, $e->getMessage())
            );
            $payload = [
                'status' => Status::UNAVAILABLE,
                'summary' => $e->getMessage(),
                'sections' => [],
            ];
        }

        $payload['code'] = $code;
        $payload['label'] = $collector->getLabel();
        $payload['elapsed_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
        $payload['collected_at'] = gmdate('Y-m-d H:i:s') . ' UTC';
        $payload['cached'] = false;

        if ($ttl > 0) {
            $this->cache->save($this->serializer->serialize($payload), $cacheKey, [self::CACHE_TAG], $ttl);
        }

        return $payload;
    }
}
