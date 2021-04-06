<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Controller\Adminhtml\Fee\Shipping;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;
use MageWorx\MultiFees\Api\ShippingFeeRepositoryInterface as FeeRepository;
use MageWorx\MultiFees\Controller\Adminhtml\Fee\ShippingFee as FeeAbstractController;
use Magento\Framework\Exception\LocalizedException;
use MageWorx\MultiFees\Model\ShippingFee as FeeModel;
use MageWorx\MultiFees\Model\ShippingFeeFactory as FeeFactory;
use MageWorx\MultiFees\Model\ResourceModel\Fee\ShippingFeeCollectionFactory as CollectionFactory;

abstract class MassAction extends FeeAbstractController
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * MassAction constructor.
     *
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param Registry $registry
     * @param FeeFactory $feeFactory
     * @param FeeRepository $feeRepository
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $jsonFactory
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\SalesRule\Model\Rule $rule,
        Registry $registry,
        FeeFactory $feeFactory,
        FeeRepository $feeRepository,
        PageFactory $resultPageFactory,
        JsonFactory $jsonFactory,
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($rule, $registry, $feeFactory, $feeRepository, $resultPageFactory, $jsonFactory, $context);
        $this->filter            = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param FeeModel $fee
     * @return mixed
     */
    abstract protected function executeAction(FeeModel $fee);

    /**
     * @return \Magento\Framework\Phrase
     */
    abstract protected function getErrorMessage();

    /**
     * @param int $collectionSize
     * @return \Magento\Framework\Phrase
     */
    abstract protected function getSuccessMessage($collectionSize);

    /**
     * execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        try {
            $collection     = $this->filter->getCollection($this->collectionFactory->create());
            $collectionSize = $collection->getSize();
            foreach ($collection as $fee) {
                $fee->load($fee->getId());
                $this->executeAction($fee);
            }
            $this->messageManager->addSuccessMessage($this->getSuccessMessage($collectionSize));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $this->getErrorMessage());
        }
        $redirectResult = $this->resultRedirectFactory->create();
        $redirectResult->setPath('mageworx_multifees/*/index');

        return $redirectResult;
    }
}
