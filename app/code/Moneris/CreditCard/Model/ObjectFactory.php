<?php
/**
 * Copyright © 2015 Collins Harper. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Moneris\CreditCard\Model;

class ObjectFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $activeClass;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     *
     * @param string $className
     * @return $this
     */
    public function setClass($className)
    {
        $this->activeClass = $className;
        return $this;
    }

    /**
     * Create new country model
     *
     * @param array $arguments
     * @param $className
     * @return \Magento\Directory\Model\Country
     */
    public function create(array $arguments = [], $className = false)
    {
        if ($className) {
            $this->activeClass = $className;
        }
        return $this->objectManager->get($this->activeClass);
    }
}