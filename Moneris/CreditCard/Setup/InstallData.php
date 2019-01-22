<?php
/**
 * Copyright © 2015 Collins Harper. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Moneris\CreditCard\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(array('setup' => $setup));
        /**
         * Add attributes to the eav/attribute table
         */

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'ready_to_ship',
            array(
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Is the Item Ready to Ship?',
                'note' => 'Rate request will be performed as if this item is pre-boxes, it will not be boxed with other items.',
                'input' => 'boolean',
                'class' => '',
                'source' => '\Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'apply_to' => 'simple,bundle,grouped,configurable',
                'used_in_product_listing' => true
            )
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'shipping_length',
            array(
                'type' => 'decimal',
                'backend' => '',
                'frontend' => '',
                'label' => 'Shipping Length',
                'note' => 'Dimension used for Shipping Estimates.',
                'input' => 'text',
                'class' => '',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'apply_to' => 'simple,bundle,grouped,configurable',
                'used_in_product_listing' => true
            )
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'shipping_width',
            array(
                'type' => 'decimal',
                'backend' => '',
                'frontend' => '',
                'label' => 'Shipping Width',
                'note' => 'Dimension used for Shipping Estimates.',
                'input' => 'text',
                'class' => '',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'apply_to' => 'simple,bundle,grouped,configurable',
                'used_in_product_listing' => true
            )
        );
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'shipping_height',
            array(
                'type' => 'decimal',
                'backend' => '',
                'frontend' => '',
                'label' => 'Shipping Height',
                'note' => 'Dimension used for Shipping Estimates.',
                'input' => 'text',
                'class' => '',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'apply_to' => 'simple,bundle,grouped,configurable',
                'used_in_product_listing' => true
            )
        );

    }
}