<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Magenx\Platform\Model\Collector;

use Magenx\Platform\Model\Metric\Result;

/**
 * One backend's tab.
 *
 * A collector may throw freely — CollectorRunner turns any \Throwable into an
 * "unavailable" tab, so a dead backend costs one red card and never a 500. What
 * it must not do is put a credential into a Result.
 */
interface CollectorInterface
{
    /**
     * Tab label. Product names, so not translated.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * @return Result
     */
    public function collect(): Result;
}
