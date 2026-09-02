<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Magenx\Platform\Model\Metric;

/**
 * The severity vocabulary shared by rows, sections and tabs.
 *
 * INFO exists so that a plain fact — a version string, a hostname — does not
 * have to claim it is "ok". Only a value that was actually measured against a
 * threshold gets OK, and only those drive a tab's colour.
 */
class Status
{
    public const INFO = 'info';
    public const OK = 'ok';
    public const WARN = 'warn';
    public const UNAVAILABLE = 'unavailable';
    public const ERROR = 'error';

    /**
     * Ranking used to roll rows up into a section and sections up into a tab.
     */
    private const SEVERITY = [
        self::INFO => 0,
        self::OK => 0,
        self::WARN => 1,
        self::UNAVAILABLE => 2,
        self::ERROR => 3,
    ];

    /**
     * Return whichever of the two statuses is the more serious.
     *
     * @param string $left
     * @param string $right
     * @return string
     */
    public function worst(string $left, string $right): string
    {
        $leftRank = self::SEVERITY[$left] ?? 0;
        $rightRank = self::SEVERITY[$right] ?? 0;

        return $rightRank > $leftRank ? $right : $left;
    }

    /**
     * Pick a status from a measured ratio against a warn and an error threshold.
     *
     * @param float $value
     * @param float $warnAt
     * @param float $errorAt
     * @return string
     */
    public function forCeiling(float $value, float $warnAt, float $errorAt): string
    {
        if ($value >= $errorAt) {
            return self::ERROR;
        }

        return $value >= $warnAt ? self::WARN : self::OK;
    }

    /**
     * Same as forCeiling(), for metrics where a LOW number is the bad one.
     *
     * @param float $value
     * @param float $warnBelow
     * @param float $errorBelow
     * @return string
     */
    public function forFloor(float $value, float $warnBelow, float $errorBelow): string
    {
        if ($value <= $errorBelow) {
            return self::ERROR;
        }

        return $value <= $warnBelow ? self::WARN : self::OK;
    }
}
