<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Magenx\Platform\Model\Metric;

/**
 * One label/value line on a tab.
 *
 * The value is always a preformatted string — the collector owns the units, so
 * the template and the JavaScript stay free of per-metric knowledge.
 */
class Row
{
    private string $label;

    private string $value;

    private string $status;

    private string $hint;

    /**
     * @param string $label
     * @param string $value
     * @param string $status
     * @param string $hint
     */
    public function __construct(
        string $label,
        string $value,
        string $status = Status::INFO,
        string $hint = ''
    ) {
        $this->label = $label;
        $this->value = $value;
        $this->status = $status;
        $this->hint = $hint;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
            'status' => $this->status,
            'hint' => $this->hint,
        ];
    }
}
