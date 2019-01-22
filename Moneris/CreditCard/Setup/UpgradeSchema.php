<?php
/**
* Copyright © 2016 Collinsharper. All rights reserved.
* See COPYING.txt for license details.
*/

namespace Moneris\CreditCard\Setup;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
class UpgradeSchema implements  UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $installer, ModuleContextInterface $context)
    {
        $installer->startSetup();
        if (version_compare($context->getVersion(), '2.1.1', '<')) {
            $installer->getConnection()->dropTable($installer->getTable('collinsharper_moneris_payment_vault'));
            $table = $installer->getTable('collinsharper_moneris_payment_vault');
            if ($installer->getConnection()->isTableExists($table) != true) {
               $table = $installer->getConnection()->newTable($table)
                   ->addColumn(
                       'vault_id',
                       \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                       null,
                       array(
                           'identity' => true,
                           'unsigned' => true,
                           'nullable' => false,
                           'primary' => true
                       ),
                       'Vault Id'
                   )
                   ->addColumn(
                       'created_date',
                       \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                       null,
                       array(
                           'nullable' => true
                       ),
                       'Created Date'
                   )
                   ->addColumn(
                       'customer_id',
                       \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                       null,
                       array(
                           'nullable' => false,
                           'default' => '0'
                       ),
                       'Customer Id'
                   )
                   ->addColumn(
                       'data_key',
                       \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                       null,
                       array(
                           'nullable' => false,
                       ),
                       'Data Key'
                   )
                   ->setComment('Vault Table')
                   ->setOption('type', 'InnoDB')
                   ->setOption('charset', 'utf8');
                   $installer->getConnection()->createTable($table);
            }
            
            $installer->endSetup();
        }
        
        if (version_compare($context->getVersion(), '2.1.2', '<')) {
            $table = $installer->getTable('collinsharper_moneris_payment_vault');
            if ($installer->getConnection()->isTableExists($table) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $table,
                    'customer_email', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
                    array('nullable' => false),
                    'Customer Email'
                );
            }
            
            $installer->endSetup();
        }
        
        if (version_compare($context->getVersion(), '2.1.2', '<')) {
            $table = $installer->getTable('collinsharper_moneris_payment_vault');
            if ($installer->getConnection()->isTableExists($table) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $table,
                    'store_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
                    array('nullable' => false),
                    'Store Id'
                );
                $connection->addColumn(
                    $table,
                    'card_default', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null,
                    array(
                        'nullable' => false,
                        'default' => '0'
                    ),
                    'Cart Default'
                );
                $connection->addColumn(
                    $table,
                    'card_expire', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
                    array(
                        'nullable' => false,
                    ),
                    'Cart Expire'
                );
                $connection->addColumn(
                    $table,
                    'cc_exp_month', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
                    array(
                        'nullable' => false,
                    ),
                    'Cart Expire Month'
                );
                $connection->addColumn(
                    $table,
                    'cc_exp_year', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
                    array(
                        'nullable' => false,
                    ),
                    'Cart Expire Year'
                );
                $connection->addColumn(
                    $table,
                    'cardholder', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
                    array(
                        'nullable' => false,
                    ),
                    'Cart Card Holder'
                );
                $connection->addColumn(
                    $table,
                    'card_type', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
                    array(
                        'nullable' => false,
                    ),
                    'Cart Type'
                );
                $connection->addColumn(
                    $table,
                    'cc_last', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
                    array(
                        'nullable' => false,
                    ),
                    'Cc Last 4'
                );
                $connection->addColumn(
                    $table,
                    'updated_date', \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME, null,
                    array(
                        'nullable' => false,
                    ),
                    'Updated Date'
                );
                ;
            }
        }
        if (version_compare($context->getVersion(), '2.1.3', '<')) {
            $quote = 'quote';
            $installer->getConnection()
            ->addColumn(
                $installer->getTable($quote),
                'bank_transaction_id',
                array(
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' =>'Bank Transaction Id'
                )
            );
            $installer->endSetup();
        }

        if (version_compare($context->getVersion(), '3.0.0', '<')) {
            $table = $installer->getTable('collinsharper_moneris_recurring_payment');

            if ($installer->getConnection()->isTableExists($table) != true) {
                $table = $installer->getConnection()->newTable($table)
                    ->addColumn(
                        'entity_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        array(
                            'identity' => true,
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => true
                        ),
                        'Payment Id'
                    )
                    ->addColumn(
                        'created_date',
                        \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                        null,
                        array(
                            'nullable' => true
                        ),
                        'Created Date'
                    )
                    ->addColumn(
                        'last_payment_date',
                        \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                        null,
                        array(
                            'nullable' => true
                        ),
                        'Last Payment Occurrence Date'
                    )
                    ->addColumn(
                        'next_payment_date',
                        \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                        null,
                        array(
                            'nullable' => true
                        ),
                        'Next Payment Date'
                    )
                    ->addColumn(
                        'customer_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        array(
                            'nullable' => false,
                            'default' => '0'
                        ),
                        'Customer Id'
                    )
                    ->addColumn(
                        'amount',
                        \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
                        null,
                        array(
                            'nullable' => false,
                            'default' => '0'
                        ),
                        'Total Amount'
                    )
                    ->addColumn(
                        'order_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        array(
                            'nullable' => true
                        ),
                        'Order ID'
                    )
                    ->addColumn(
                        'data_key',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        array(
                            'nullable' => false,
                        ),
                        'Data Key'
                    )
                    ->setComment('Recurring payments Table')
                    ->setOption('type', 'InnoDB')
                    ->setOption('charset', 'utf8');
                $installer->getConnection()->createTable($table);
            }

            $table = $installer->getTable('collinsharper_moneris_payment_queue');

            if ($installer->getConnection()->isTableExists($table) != true) {
                $table = $installer->getConnection()->newTable($table)
                    ->addColumn(
                        'entity_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        array(
                            'identity' => true,
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => true
                        ),
                        'Id'
                    )
                    ->addColumn(
                        'moneris_order_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        array(
                            'nullable' => true
                        ),
                        'Moneris Order ID'
                    )
                    ->addColumn(
                        'order_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        array(
                            'nullable' => true
                        ),
                        'Magento Order ID'
                    )
                    ->addColumn(
                        'data_key',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        null,
                        array(
                            'nullable' => false,
                        ),
                        'Data Key'
                    )
                    ->setComment('Recurring payment queue Table')
                    ->setOption('type', 'InnoDB')
                    ->setOption('charset', 'utf8');
                $installer->getConnection()->createTable($table);
            }
        }

        if (version_compare($context->getVersion(), '3.0.1', '<')) {
            $table = $installer->getTable('collinsharper_moneris_recurring_payment');

            $installer->getConnection()
                ->addColumn(
                    $table,
                    'recurring_term',
                    array(
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'nullable' => false,
                        'comment' =>'Recurring term'
                    )
                );
        }

        if (version_compare($context->getVersion(), '3.0.2', '<')) {
            $table = $installer->getTable('collinsharper_moneris_payment_vault');
            if ($installer->getConnection()->isTableExists($table) == true) {
                $connection = $installer->getConnection();
                $connection->addColumn(
                    $table,
                    'issuer_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null,
                    array('nullable' => true),
                    'Issuer ID'
                );
            }
        }

        $installer->endSetup();
    }
}
