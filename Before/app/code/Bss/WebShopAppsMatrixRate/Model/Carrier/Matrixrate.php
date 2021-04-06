<?php

namespace Bss\WebShopAppsMatrixRate\Model\Carrier;

use WebShopApps\MatrixRate\Model\Carrier\Matrixrate as WebShopAppsMatrixrate;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Checkout\Model\CartFactory;

class Matrixrate extends WebShopAppsMatrixrate
{
	private $cartFactory;

	public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $resultMethodFactory,
        \WebShopApps\MatrixRate\Model\ResourceModel\Carrier\MatrixrateFactory $matrixrateFactory,
        CartFactory $cartFactory,
        array $data = []
    ) {
    	$this->cartFactory = $cartFactory;
        parent::__construct(
        	$scopeConfig,
        	$rateErrorFactory,
        	$logger,
        	$rateResultFactory,
        	$resultMethodFactory,
        	$matrixrateFactory,
        	$data
        );
    }

	public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // exclude Virtual products price from Package value if pre-configured
        if (!$this->getConfigFlag('include_virtual_price') && $request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getProduct()->isVirtual()) {
                            $request->setPackageValue($request->getPackageValue() - $child->getBaseRowTotal());
                        }
                    }
                } elseif ($item->getProduct()->isVirtual()) {
                    $request->setPackageValue($request->getPackageValue() - $item->getBaseRowTotal());
                }
            }
        }

        // Free shipping by qty
        $freeQty = 0;
        if ($request->getAllItems()) {
            $freePackageValue = 0;
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $freeShipping = is_numeric($child->getFreeShipping()) ? $child->getFreeShipping() : 0;
                            $freeQty += $item->getQty() * ($child->getQty() - $freeShipping);
                        }
                    }
                } elseif ($item->getFreeShipping()) {
                    $freeShipping = is_numeric($item->getFreeShipping()) ? $item->getFreeShipping() : 0;
                    $freeQty += $item->getQty() - $freeShipping;
                    $freePackageValue += $item->getBaseRowTotal();
                }
            }
            $oldValue = $request->getPackageValue();
            $request->setPackageValue($oldValue - $freePackageValue);
        }

        if (!$request->getConditionMRName()) {
            $conditionName = $this->getConfigData('condition_name');
            $request->setConditionMRName($conditionName ? $conditionName : $this->defaultConditionName);
        }

        // Package weight and qty free shipping
        $oldWeight = $request->getPackageWeight();
        $oldQty = $request->getPackageQty();

        if ($this->getConfigData('minimum_select') && $request->getDestCountryId() == "AU") {
        	$enableAtrrFreeShippingWeight = number_format($this->getEnableAtrrFreeShippingWeight(), 2, '.', '');
            $toTalCartWeight = number_format($this->getToTalCartWeight(), 2, '.', '');
            $oldWeight = $toTalCartWeight - $enableAtrrFreeShippingWeight;
            $request->setPackageWeight($oldWeight);
        } else {
        	$request->setPackageWeight($request->getFreeMethodWeight());
        }
        
        $request->setPackageQty($oldQty - $freeQty);

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();
        $zipRange = $this->getConfigData('zip_range');
        $rateArray = $this->getRate($request, $zipRange);
        $request->setPackageWeight($oldWeight);
        $request->setPackageQty($oldQty);
        $foundRates = false;

        foreach ($rateArray as $rate) {
            if (!empty($rate) && $rate['price'] >= 0) {
                /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
                $method = $this->resultMethodFactory->create();

                $method->setCarrier('matrixrate');
                $method->setCarrierTitle($this->getConfigData('title'));

                $method->setMethod('matrixrate_' . $rate['pk']);
                $method->setMethodTitle(__($rate['shipping_method']));

                if ($request->getFreeShipping() === true || $request->getPackageQty() == $freeQty) {
                    $shippingPrice = 0;
                } else {
                    $shippingPrice = $this->getFinalPriceWithHandlingFee($rate['price']);
                }

                $method->setPrice($shippingPrice);
                $method->setCost($rate['cost']);

                $result->append($method);
                $foundRates = true; // have found some valid rates
            }
        }

        if (!$foundRates) {
            /** @var \Magento\Quote\Model\Quote\Address\RateResult\Error $error */
            $error = $this->_rateErrorFactory->create(
                [
                    'data' => [
                        'carrier' => $this->_code,
                        'carrier_title' => $this->getConfigData('title'),
                        'error_message' => $this->getConfigData('specificerrmsg'),
                    ],
                ]
            );
            $result->append($error);
        }

        return $result;
    }

    private function getEnableAtrrFreeShippingWeight() {
    	$items = $this->cartFactory->create()->getQuote()->getAllVisibleItems();
        $totalWeight = 0;
        $totalWeightCartFree = 0;
        $totalCartPriceFree = 0;
        foreach ($items as $item) {
            $product = $item->getProduct();
            if ($product->getAttributeText('free_shipping') !== 'No Free Shipping') {
                
                $qty = $item->getQty();
                $weight = $item->getWeight();
                $priceFree = $product->getFinalPrice();
                $totalPriceFree = $priceFree * $qty;
                $priceWithDiscount = $item->getDiscountAmount();
                if ($priceWithDiscount) {
                    $totalCartPriceFree = $totalCartPriceFree + $totalPriceFree - $priceWithDiscount;
                } else {
                    $totalCartPriceFree = $totalCartPriceFree + $totalPriceFree;
                }

				$totalWeight += $weight * $qty;
                if ($totalCartPriceFree >= (float) $this->getConfigData('minimum_input')) {
                	$totalWeightCartFree += $totalWeight;
                }
            }
        }
        return $totalWeightCartFree;
    }

    private function getToTalCartWeight() {
        $items = $this->cartFactory->create()->getQuote()->getAllVisibleItems();
        $totalWeight = 0;
        foreach ($items as $item) {
            $product = $item->getProduct();
            $qty = $item->getQty();
            $weight = $item->getWeight();
        	$totalWeight += ($weight * $qty);
        }
        return $totalWeight;
    }
}
