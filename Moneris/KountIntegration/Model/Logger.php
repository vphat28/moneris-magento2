<?php

namespace Moneris\KountIntegration\Model;

use Magento\Framework\Logger\Monolog;
use Moneris\KountIntegration\Model\Logger\Handler;

class Logger extends Monolog
{
    /**
     * Logger constructor.
     * @param $name
     * @param Handler $handler
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        $name,
        Handler $handler,
        array $handlers = [],
        array $processors = []
    )
    {
        $handlers[] = $handler;
        parent::__construct($name, $handlers, $processors);
    }
}