<?php

namespace Moneris\CreditCard\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    protected $fileName = '/var/log/ch_moneris.log';
    protected $loggerType = Logger::DEBUG;
}