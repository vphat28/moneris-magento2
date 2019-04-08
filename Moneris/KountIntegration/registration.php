<?php

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Moneris_KountIntegration',
    __DIR__
);

require_once __DIR__ . '/kountClasses.php';