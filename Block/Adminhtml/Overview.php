<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Magenx\Platform\Block\Adminhtml;

use Magenx\Platform\Model\CollectorPool;
use Magenx\Platform\Model\Config;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Builds the tab strip and the JavaScript payload for the dashboard.
 *
 * The per-tab URLs are built with getUrl(), which is what stamps the admin
 * secret key into them. Assembling these by hand would produce links the
 * backend rejects the moment secret keys are on.
 */
class Overview extends Template
{
    private CollectorPool $pool;

    private Config $config;

    private Json $json;

    /**
     * @param Context $context
     * @param CollectorPool $pool
     * @param Config $config
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectorPool $pool,
        Config $config,
        Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->pool = $pool;
        $this->config = $config;
        $this->json = $json;
    }

    /**
     * @return bool
     */
    public function isPlatformEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * @return array
     */
    public function getTabs(): array
    {
        $enabled = $this->config->getEnabledCollectors();
        $tabs = [];

        foreach ($this->pool->getAll() as $code => $collector) {
            if (!in_array($code, $enabled, true)) {
                continue;
            }
            $tabs[] = [
                'code' => $code,
                'label' => $collector->getLabel(),
                'url' => $this->getUrl('magenx_platform/overview/metrics', ['collector' => $code]),
            ];
        }

        return $tabs;
    }

    /**
     * The data-mage-init payload for the dashboard container.
     *
     * @return string
     */
    public function getJsInit(): string
    {
        return $this->json->serialize(
            [
                'Magenx_Platform/js/overview' => [
                    'tabs' => $this->getTabs(),
                    'autoRefresh' => $this->config->getAutoRefresh(),
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getConfigUrl(): string
    {
        return $this->getUrl('adminhtml/system_config/edit', ['section' => 'magenx_platform']);
    }
}
