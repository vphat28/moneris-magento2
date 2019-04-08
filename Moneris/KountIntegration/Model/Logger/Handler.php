<?php

namespace Moneris\KountIntegration\Model\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    protected $fileName = '/var/log/kountintegration.log';
    protected $loggerType = Logger::DEBUG;
}