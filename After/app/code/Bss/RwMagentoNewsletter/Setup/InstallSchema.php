<?php
namespace Bss\RwMagentoNewsletter\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @codingStandardsIgnoreStart
    * @SuppressWarnings(PHPMD)
    */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $setup->startSetup();

        $table = $setup->getTable('newsletter_subscriber');

        $setup->getConnection()->addColumn(
            $table,
            'customer_firstname',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'First Name'
            ]
        );
    }
}
