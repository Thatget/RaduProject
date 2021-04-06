<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bss\OrderInfo\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Bss\OrderInfo\Model\PostFactory;

class Index extends \Magento\Framework\App\Action\Action
{

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $_postFactory;

    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        PostFactory $postFactory
    )
    {
        $this->productRepository = $productRepository;
        $this->_postFactory = $postFactory;
        parent::__construct($context);
    }

    /**
     * Index action
     */
    public function execute()
    {
        $post = $this->_postFactory->create();
        $collection = $post->getCollection();

        foreach($collection as $item) {
            $data = $item->getData();
            if ($data['attribute_id'] == 223) {
                if ($data['value_id'] > 141692 && $data['value_id'] < 143694) {
                    $product = $this->productRepository
                        ->getById($data['entity_id'], false, $data['store_id'], true);
                    if ($product) {
                        if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                            $product->addAttributeUpdate('price_rrp', $data['value'], $data['store_id']);
                        }
                    }
                }
            }
        }
    }
}
