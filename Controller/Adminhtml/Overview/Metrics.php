<?php
/**
 * Copyright © MagenX. All rights reserved.
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace Magenx\Platform\Controller\Adminhtml\Overview;

use Magenx\Platform\Model\CollectorPool;
use Magenx\Platform\Model\CollectorRunner;
use Magenx\Platform\Model\Config;
use Magenx\Platform\Model\Metric\Status;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * One collector, one request.
 *
 * The page asks for each tab separately and in parallel, which is what keeps a
 * single unresponsive backend from delaying the other five. Read-only and GET,
 * so there is no form key to carry; the ACL check inherited from
 * Magento\Backend\App\Action, plus the secret key already in the URL, is the
 * whole gate.
 */
class Metrics extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Magenx_Platform::platform';

    private JsonFactory $resultJsonFactory;

    private CollectorPool $pool;

    private CollectorRunner $runner;

    private Config $config;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CollectorPool $pool
     * @param CollectorRunner $runner
     * @param Config $config
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CollectorPool $pool,
        CollectorRunner $runner,
        Config $config
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->pool = $pool;
        $this->runner = $runner;
        $this->config = $config;
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();
        $code = (string) $this->getRequest()->getParam('collector', '');

        if (!$this->config->isEnabled()) {
            return $result->setData($this->problem($code, 'Platform Overview is switched off in configuration.'));
        }

        $collector = $this->pool->get($code);
        if ($collector === null || !in_array($code, $this->config->getEnabledCollectors(), true)) {
            return $result->setData($this->problem($code, 'That tab is not enabled.'));
        }

        return $result->setData($this->runner->run($code, $collector));
    }

    /**
     * A refusal shaped like a collector payload, so the page renders it the
     * same way it renders an unreachable backend.
     *
     * @param string $code
     * @param string $message
     * @return array
     */
    private function problem(string $code, string $message): array
    {
        return [
            'code' => $code,
            'label' => $code,
            'status' => Status::UNAVAILABLE,
            'summary' => $message,
            'sections' => [],
            'cached' => false,
            'elapsed_ms' => 0,
        ];
    }
}
