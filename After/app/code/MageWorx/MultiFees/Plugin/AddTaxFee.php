<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Plugin;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Tax\Api\OrderTaxManagementInterface;

class AddTaxFee
{
    /**
     * @var string
     */
    protected $source;

    /**
     * @var OrderTaxManagementInterface
     */
    protected $orderTaxManagement;

    /**
     * AddTaxFee constructor.
     *
     * @param OrderTaxManagementInterface $orderTaxManagement
     */
    public function __construct(
        OrderTaxManagementInterface $orderTaxManagement
    ) {
        $this->orderTaxManagement = $orderTaxManagement;
    }

    /**
     * @param \Magento\Tax\Helper\Data $subject
     * @param $source
     * @return mixed
     */
    public function beforeGetCalculatedTaxes(\Magento\Tax\Helper\Data $subject, $source)
    {
        $this->source = $source;
        return [$source];
    }

    public function afterGetCalculatedTaxes(\Magento\Tax\Helper\Data $subject, $result)
    {
        if ($this->source instanceof Invoice || $this->source instanceof Creditmemo) {
            $baseMageworxFeeTaxAmount = $this->source->getBaseMageworxFeeTaxAmount();
            $feeTax = [];
            if ($baseMageworxFeeTaxAmount > 0) {
                $order = $this->source->getOrder();
                $orderTaxDetails = $this->orderTaxManagement->getOrderTaxDetails($order->getId());
                $itemTaxDetails = $orderTaxDetails->getItems();
                foreach ($itemTaxDetails as $itemTaxDetail) {
                    if ($itemTaxDetail->getType() == 'mageworx_fee_tax') {
                        $feeTax = $itemTaxDetail->getAppliedTaxes();
                    }
                }
            }

            if (!empty($feeTax)) {
                foreach ($feeTax as $feeTaxTitle => $feeTaxData) {
                    $taxExist = false;
                    foreach ($result as $sourceTaxId => $sourceTax) {
                        if ($sourceTax['title'] == $feeTaxTitle) {
                            $result[$sourceTaxId]['tax_amount'] += $feeTaxData->getAmount();
                            $result[$sourceTaxId]['base_tax_amount'] += $feeTaxData->getBaseAmount();
                            $taxExist = true;
                        }
                    }

                    if (!$taxExist) {
                        $result[] = [
                            'title' => $feeTaxTitle,
                            'percent'=> $feeTaxData->getPercent(),
                            'tax_amount' => $feeTaxData->getAmount(),
                            'base_tax_amount' =>$feeTaxData->getBaseAmount()
                        ];
                    }
                }

            }
        }

        return $result;
    }
}