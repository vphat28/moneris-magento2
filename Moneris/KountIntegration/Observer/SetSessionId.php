<?php

namespace Moneris\KountIntegration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SetSessionId implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $session = session_id();
        if (!$session) {
            session_start();
        }
    }
}