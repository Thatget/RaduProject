<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bss\OrderInfo\Model\Product\ProductList;

/**
 * Class Toolbar
 *
 * @api
 * @since 100.0.2
 */
class Toolbar extends \Magento\Catalog\Model\Product\ProductList\Toolbar
{
    protected $request;

    /**
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->request = $request;
    }

    /**
     * Get sort order
     *
     * @return string|bool
     */
    public function getOrder()
    {
        return $this->request->getParam('order');
    }
}
