<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Model\Fee\Condition\PaymentFee;

use MageWorx\MultiFees\Exception\ValidationException;

/**
 * Class Address
 *
 * @method string getAttribute()
 * @method array getAttributeOption()
 * @method Address setInputType($string)
 * @method Address setOperator($string)
 * @method Address setValue($string)
 */
class Address extends \MageWorx\MultiFees\Model\Fee\Condition\Address
{
    /**
     * Additional attributes, specific for each entity, which should be set in that condition only
     *
     * @var array
     */
    protected $attributesAdditional = [
        'postcode'   => 'Billing Postcode',
        'region'     => 'Billing Region',
        'region_id'  => 'Billing State/Province',
        'country_id' => 'Billing Country',
        'shipping_method' => 'Shipping Method'
    ];

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Address constructor.
     *
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Directory\Model\Config\Source\Country $directoryCountry
     * @param \Magento\Directory\Model\Config\Source\Allregion $directoryAllregion
     * @param \Magento\Shipping\Model\Config\Source\Allmethods $shippingAllmethods
     * @param \Magento\Payment\Model\Config\Source\Allmethods $paymentAllmethods
     * @param \Magento\Framework\App\RequestInterface $request
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Directory\Model\Config\Source\Country $directoryCountry,
        \Magento\Directory\Model\Config\Source\Allregion $directoryAllregion,
        \Magento\Shipping\Model\Config\Source\Allmethods $shippingAllmethods,
        \Magento\Payment\Model\Config\Source\Allmethods $paymentAllmethods,
        \Magento\Framework\App\RequestInterface $request,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $directoryCountry,
            $directoryAllregion,
            $shippingAllmethods,
            $paymentAllmethods,
            $data
        );

        $this->request = $request;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return \Magento\Framework\Model\AbstractModel
     * @throws ValidationException
     */
    protected function resolveAddressEntity(\Magento\Framework\Model\AbstractModel $model)
    {
        $address = $model;
        if (!$address instanceof \Magento\Quote\Model\Quote\Address ||
            !$address->getAddressType() == \Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_BILLING
        ) {
            $this->validateModelEntity($model);
            $address = $model->getQuote()->getBillingAddress();
        }

        if (!$address) {
            throw new ValidationException(__('Empty billing address'));
        }

        return $address;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address $address
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return \Magento\Quote\Model\Quote\Address
     */
    protected function modifyBeforeLoadAddress(
        \Magento\Quote\Model\Quote\Address $address,
        \Magento\Framework\Model\AbstractModel $model
    ) {
        $dataTransferredFromShippingAddress = $this->getData('dataTransferredFromShippingAddress');
        if (!empty($dataTransferredFromShippingAddress)) {
            $shippingAddress = $address->getQuote()->getShippingAddress();
            foreach ($dataTransferredFromShippingAddress as $key) {
                $address->setData($key, $shippingAddress->getData($key));
            }
        }

        $shippingMethod = $this->request->getParam('shipping_method');
        if ($shippingMethod) {
            $address->setShippingMethod($shippingMethod);
        }

        $address = parent::modifyBeforeLoadAddress($address, $model);

        return $address;
    }
}
