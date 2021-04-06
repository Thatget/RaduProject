<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Block\Adminhtml\Fee\Edit;

use Magento\Framework\Registry;
use Magento\Backend\Block\Widget\Tabs as WidgetTabs;

/**
 * @method Tabs setTitle(\string $title)
 */
class Tabs extends WidgetTabs
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * Tabs constructor.
     *
     * @param Registry $coreRegistry
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param array $data
     */
    public function __construct(
        Registry $coreRegistry,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('fee_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Fee Information'));
    }

    /**
     * @return \MageWorx\MultiFees\Block\Adminhtml\Fee\Edit\Tabs|\Magento\Backend\Block\Widget\Tabs
     * @throws \Exception
     */
    protected function _beforeToHtml()
    {
        $this->addTab(
            'main',
            [
                'label'   => __('Main'),
                'title'   => __('Main'),
                'content' => $this->getChildHtml('main'),
                'active'  => true
            ]
        );
        $this->addTab(
            'main_options',
            [
                'label'   => __('Manage Options'),
                'title'   => __('Properties'),
                'content' => $this->getChildHtml('main_options'),
            ]
        );

        $currentFee = $this->coreRegistry->registry('mageworx_multifees_fee');
        if (!($currentFee instanceof \MageWorx\MultiFees\Api\Data\ProductFeeInterface)) {
            $this->addTab(
                'front',
                [
                    'label'   => __('Conditions'),
                    'title'   => __('Conditions'),
                    'content' => $this->getChildHtml('conditions')
                ]
            );
        }

        $this->addTab(
            'actions',
            [
                'label'   => __('Apply to'),
                'title'   => __('Apply to'),
                'content' => $this->getChildHtml('actions')
            ]
        );
        $this->addTab(
            'labels',
            [
                'label'   => __('Manage Labels'),
                'title'   => __('Manage Labels'),
                'content' => $this->getChildHtml('labels')
            ]
        );

        return parent::_beforeToHtml();
    }
}
