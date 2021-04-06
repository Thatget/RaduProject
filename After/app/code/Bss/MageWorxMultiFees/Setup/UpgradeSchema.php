<?php

namespace Bss\MageWorxMultiFees\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_status_history'),
            'automatic',
            [
                'type' => Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Comment Automatic',
            ]
        );

        $installer->endSetup();
    }
}
