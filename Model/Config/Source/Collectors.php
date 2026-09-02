<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Magenx\Platform\Model\Config\Source;

use Magenx\Platform\Model\CollectorPool;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * The multiselect in system.xml is generated from the collector pool, so a new
 * backend appears in admin config the moment it is registered in etc/di.xml.
 */
class Collectors implements OptionSourceInterface
{
    private CollectorPool $pool;

    /**
     * @param CollectorPool $pool
     */
    public function __construct(CollectorPool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->pool->getAll() as $code => $collector) {
            $options[] = ['value' => $code, 'label' => $collector->getLabel()];
        }

        return $options;
    }
}
