<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use MageWorx\MultiFees\Api\Data\FeeInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            $this->modifyTotalBaseAmount($setup);
        }

        if (version_compare($context->getVersion(), '2.0.2', '<')) {
            $this->addMethodsColumns($setup);
        }

        if (version_compare($context->getVersion(), '2.0.3', '<')) {
            $this->removeSalesMethodsColumn($setup);
        }

        if (version_compare($context->getVersion(), '2.0.5', '<')) {
            $this->addCalculationColumns($setup);
        }

        if (version_compare($context->getVersion(), '2.0.6', '<')) {
            $this->addProductFeesFields($setup);
        }

        if (version_compare($context->getVersion(), '2.0.7', '<')) {
            $this->addUniqueKeyToLanguageTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.8', '<')) {
            $this->addUseBundleQty($setup);
        }

        if (version_compare($context->getVersion(), '2.0.9', '<')) {
            $this->addMinAmount($setup);
        }

        $setup->endSetup();
    }

    /**
     * Remove unused column `sales_methods`
     *
     * @param SchemaSetupInterface $setup
     */
    protected function removeSalesMethodsColumn(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $connection->dropColumn($setup->getTable('mageworx_multifees_fee'), 'sales_methods');
    }

    /**
     * Update total base amount field
     *
     * @param SchemaSetupInterface $setup
     *
     */
    protected function modifyTotalBaseAmount(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $connection->modifyColumn(
            $setup->getTable('mageworx_multifees_fee'),
            'total_base_amount',
            [
                'type'      => Table::TYPE_DECIMAL,
                'scale'     => '2',
                'precision' => '10'
            ]
        );
    }

    /**
     * Add shipping and payment methods columns
     *
     * @param SchemaSetupInterface $setup
     *
     */
    protected function addMethodsColumns(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $connection->addColumn(
            $setup->getTable('mageworx_multifees_fee'),
            FeeInterface::SHIPPING_METHODS,
            [
                'type'    => Table::TYPE_TEXT,
                'length'  => '64k',
                'comment' => 'Shipping Methods'
            ]
        );

        $connection->addColumn(
            $setup->getTable('mageworx_multifees_fee'),
            FeeInterface::PAYMENT_METHODS,
            [
                'type'    => Table::TYPE_TEXT,
                'length'  => '64k',
                'comment' => 'Payment Methods'
            ]
        );
    }

    /**
     * Add columns for calculation fees
     *
     * @param SchemaSetupInterface $setup
     *
     */
    protected function addCalculationColumns(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();

        $connection->addColumn(
            $setup->getTable('mageworx_multifees_fee'),
            FeeInterface::APPLY_PER,
            [
                'type'    => Table::TYPE_TEXT,
                'length'  => 255,
                'comment' => 'Apply Per',
                'default' => FeeInterface::FEE_APPLY_PER_ITEM
            ]
        );

        $connection->addColumn(
            $setup->getTable('mageworx_multifees_fee'),
            FeeInterface::UNIT_COUNT,
            [
                'unsigned' => true,
                'nullable' => false,
                'type'    => Table::TYPE_DECIMAL,
                'length'  => '12,4',
                'comment' => 'Unit Count',
                'default' => 1
            ]
        );

        $connection->addColumn(
            $setup->getTable('mageworx_multifees_fee'),
            FeeInterface::COUNT_PERCENT_FROM,
            [
                'type'    => Table::TYPE_TEXT,
                'length'  => 255,
                'comment' => 'Count Percent From',
                'default' => FeeInterface::FEE_COUNT_PERCENT_FROM_WHOLE_CART
            ]
        );

        $connection->addColumn(
            $setup->getTable('mageworx_multifees_fee'),
            FeeInterface::ACTION_SERIALIZED,
            [
                'type'    => Table::TYPE_TEXT,
                'length'  => '64k',
                'comment' => 'Actions Serialized'
            ]
        );
    }

    /**
     * Add shipping and payment methods columns
     *
     * @param SchemaSetupInterface $setup
     *
     */
    protected function addProductFeesFields(SchemaSetupInterface $setup)
    {
        $this->extendTable($setup, 'quote_address');
        $this->extendTable($setup, 'sales_invoice');
        $this->extendTable($setup, 'sales_creditmemo');
        $this->extendSalesOrderTable($setup);
    }


    /**
     * @param SchemaSetupInterface $setup
     * @return void
     */
    protected function addUniqueKeyToLanguageTable(SchemaSetupInterface $setup)
    {
        $select = $setup->getConnection()
              ->select('t1.fee_lang_id')
              ->from(['l1' => $setup->getTable('mageworx_multifees_fee_language')])
              ->joinInner(
                  ['l2' => $setup->getTable('mageworx_multifees_fee_language')],
                  'l1.id < l2.id AND l1.store_id = l2.store_id AND l1.fee_id = l2.fee_id',
                  ''
              );

        $setup->getConnection()->deleteFromSelect($select, $setup->getTable('mageworx_multifees_fee_language'));

        $setup->getConnection()->addIndex(
            $setup->getTable('mageworx_multifees_fee_language'),
            $setup->getIdxName(
                'mageworx_multifees_fee_language',
                ['fee_id', 'store_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['fee_id', 'store_id'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
        );
    }

    /**
     * @param SchemaSetupInterface $installer
     * @param null|string $tableName
     */
    protected function extendTable($installer, $tableName)
    {
        $installer->getConnection()->addColumn(
            $installer->getTable($tableName),
            'mageworx_product_fee_amount',
            [
                'type'     => Table::TYPE_DECIMAL,
                'length'   => '12,4',
                'nullable' => false,
                'default'  => '0.0000',
                'comment'  => 'MageWorx Product Fee Amount'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable($tableName),
            'base_mageworx_product_fee_amount',
            [
                'type'     => Table::TYPE_DECIMAL,
                'length'   => '12,4',
                'nullable' => false,
                'default'  => '0.0000',
                'comment'  => 'Base MageWorx Product Fee Amount'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable($tableName),
            'mageworx_product_fee_tax_amount',
            [
                'type'     => Table::TYPE_DECIMAL,
                'length'   => '12,4',
                'nullable' => false,
                'default'  => '0.0000',
                'comment'  => 'Mageworx Product Fee Tax Amount'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable($tableName),
            'base_mageworx_product_fee_tax_amount',
            [
                'type'     => Table::TYPE_DECIMAL,
                'length'   => '12,4',
                'nullable' => false,
                'default'  => '0.0000',
                'comment'  => 'Base MageWorx Product Fee Tax Amount'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable($tableName),
            'mageworx_product_fee_details',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => false,
                'default'  => '',
                'comment'  => 'MageWorx Product Fee Details'
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    protected function extendSalesOrderTable($installer)
    {
        $this->extendTable($installer, 'sales_order');

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'mageworx_product_fee_invoiced',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => false,
                'default'  => '',
                'comment'  => 'MageWorx Product Fee Invoiced'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'base_mageworx_product_fee_invoiced',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => false,
                'default'  => '',
                'comment'  => 'Base MageWorx Product Fee Invoiced'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'mageworx_product_fee_refunded',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => false,
                'default'  => '',
                'comment'  => 'MageWorx Product Fee Refunded'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'base_mageworx_product_fee_refunded',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => false,
                'default'  => '',
                'comment'  => 'Base MageWorx Product Fee Refunded'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'mageworx_product_fee_cancelled',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => false,
                'default'  => '',
                'comment'  => 'MageWorx Product Fee Canceled'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'base_mageworx_product_fee_cancelled',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => false,
                'default'  => '',
                'comment'  => 'Base MageWorx Product Fee Canceled'
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    protected function addUseBundleQty($installer)
    {
        $connection = $installer->getConnection();
        $connection->addColumn(
            $installer->getTable('mageworx_multifees_fee'),
            FeeInterface::USE_BUNDLE_QTY,
            [
                'type'    => Table::TYPE_BOOLEAN,
                'default' => 0,
                'comment' => 'Use Bundle Products Qty'
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    protected function addMinAmount($installer)
    {
        $connection = $installer->getConnection();
        $connection->addColumn(
            $installer->getTable('mageworx_multifees_fee'),
            FeeInterface::MIN_AMOUNT,
            [
                'type'    => Table::TYPE_TEXT,
                'default' => '',
                'comment' => 'Min Amount For Percent Option'
            ]
        );
    }
}
