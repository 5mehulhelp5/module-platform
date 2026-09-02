<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Magenx\Platform\Model;

use Magenx\Platform\Model\Collector\CollectorInterface;
use Magento\Framework\Exception\ConfigurationMismatchException;

/**
 * Registry of collectors, keyed by the code declared in etc/di.xml.
 */
class CollectorPool
{
    /**
     * @var CollectorInterface[]
     */
    private array $collectors;

    /**
     * @param CollectorInterface[] $collectors
     * @throws ConfigurationMismatchException
     */
    public function __construct(array $collectors = [])
    {
        foreach ($collectors as $code => $collector) {
            if (!$collector instanceof CollectorInterface) {
                throw new ConfigurationMismatchException(
                    __('Platform collector "%1" must implement CollectorInterface.', $code)
                );
            }
        }

        $this->collectors = $collectors;
    }

    /**
     * @param string $code
     * @return CollectorInterface|null
     */
    public function get(string $code): ?CollectorInterface
    {
        return $this->collectors[$code] ?? null;
    }

    /**
     * @return CollectorInterface[]
     */
    public function getAll(): array
    {
        return $this->collectors;
    }
}
