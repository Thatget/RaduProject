<?php
/**
 * Catalog super product configurable part block
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bss\CatalogHelper\Block\Product\View\Type;

// use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
// use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Session;
// use Magento\Framework\App\ObjectManager;
use Magento\Framework\Locale\Format;
// use Magento\Framework\Pricing\PriceCurrencyInterface;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product as CatalogProduct;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\ConfigurableProduct\Helper\Data;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Store\Model\ScopeInterface;
use Magento\Swatches\Helper\Data as SwatchData;
use Magento\Swatches\Helper\Media;
use Magento\Swatches\Model\Swatch;
use Magento\Framework\App\ObjectManager;
use Magento\Swatches\Model\SwatchAttributesProvider;
use Magento\Catalog\Model\ProductFactory;

/**
 * Confugurable product view type
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 */
class Configurable extends \Magento\Swatches\Block\Product\Renderer\Configurable
{

    /**
     * Path to template file with Swatch renderer.
     */
    const SWATCH_RENDERER_TEMPLATE = 'Magento_Swatches::product/view/renderer.phtml';

    /**
     * Path to default template file with standard Configurable renderer.
     */
    const CONFIGURABLE_RENDERER_TEMPLATE = 'Magento_ConfigurableProduct::product/view/type/options/configurable.phtml';

    /**
     * Action name for ajax request
     */
    const MEDIA_CALLBACK_ACTION = 'swatches/ajax/media';

    /**
     * Name of swatch image for json config
     */
    const SWATCH_IMAGE_NAME = 'swatchImage';

    /**
     * Name of swatch thumbnail for json config
     */
    const SWATCH_THUMBNAIL_NAME = 'swatchThumb';

    /**
     * @var Product
     */
    protected $product;

    protected $productFactory;
    /**
     * @var SwatchData
     */
    protected $swatchHelper;

    /**
     * @var Media
     */
    protected $swatchMediaHelper;

    /**
     * Indicate if product has one or more Swatch attributes
     *
     * @deprecated 100.1.0 unused
     *
     * @var boolean
     */
    protected $isProductHasSwatchAttribute;

    /**
     * @var SwatchAttributesProvider
     */
    private $swatchAttributesProvider;

    /**
     * @var UrlBuilder
     */
    private $imageUrlBuilder;

    private $localeFormat;

    private $customerSession;

    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices
     */
    private $variationPrices;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param EncoderInterface $jsonEncoder
     * @param Data $helper
     * @param CatalogProduct $catalogProduct
     * @param CurrentCustomer $currentCustomer
     * @param PriceCurrencyInterface $priceCurrency
     * @param ConfigurableAttributeData $configurableAttributeData
     * @param SwatchData $swatchHelper
     * @param Media $swatchMediaHelper
     * @param array $data
     * @param SwatchAttributesProvider|null $swatchAttributesProvider
     * @param UrlBuilder|null $imageUrlBuilder
     */
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        EncoderInterface $jsonEncoder,
        Data $helper,
        CatalogProduct $catalogProduct,
        CurrentCustomer $currentCustomer,
        PriceCurrencyInterface $priceCurrency,
        ConfigurableAttributeData $configurableAttributeData,
        SwatchData $swatchHelper,
        Media $swatchMediaHelper,
        array $data = [],
        SwatchAttributesProvider $swatchAttributesProvider = null,
        UrlBuilder $imageUrlBuilder = null,
        Format $localeFormat = null,
        Session $customerSession = null,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices $variationPrices = null,
        ProductFactory $productFactory
    ) {
        $this->swatchHelper = $swatchHelper;
        $this->swatchMediaHelper = $swatchMediaHelper;
        $this->swatchAttributesProvider = $swatchAttributesProvider
            ?: ObjectManager::getInstance()->get(SwatchAttributesProvider::class);
        $this->imageUrlBuilder = $imageUrlBuilder ?? ObjectManager::getInstance()->get(UrlBuilder::class);
        $this->productFactory = $productFactory;
        parent::__construct(
            $context,
            $arrayUtils,
            $jsonEncoder,
            $helper,
            $catalogProduct,
            $currentCustomer,
            $priceCurrency,
            $configurableAttributeData,
            $swatchHelper,
            $swatchMediaHelper,
            $data,
            $swatchAttributesProvider,
            $imageUrlBuilder
        );
        $this->localeFormat = $localeFormat ?: ObjectManager::getInstance()->get(Format::class);
        $this->customerSession = $customerSession ?: ObjectManager::getInstance()->get(Session::class);
        $this->variationPrices = $variationPrices ?: ObjectManager::getInstance()->get(
            \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices::class
        );
    }

    protected function getOptionPrices()
    {
        $prices = [];
        foreach ($this->getAllowProducts() as $product) {
            $tierPrices = [];
            $product = $this->productFactory->create()->load($product->getId());
            $priceInfo = $product->getPriceInfo();
            $tierPriceModel =  $priceInfo->getPrice('tier_price');
            $tierPricesList = $tierPriceModel->getTierPriceList();
            foreach ($tierPricesList as $tierPrice) {
                $tierPrices[] = [
                    'qty' => $this->localeFormat->getNumber($tierPrice['price_qty']),
                    'price' => $this->localeFormat->getNumber($tierPrice['price']->getValue()),
                    'percentage' => $this->localeFormat->getNumber(
                        $tierPriceModel->getSavePercent($tierPrice['price'])
                    ),
                ];
            }

            $prices[$product->getId()] =
                [
                    'oldPrice' => [
                        'amount' => $this->localeFormat->getNumber(
                            $priceInfo->getPrice('regular_price')->getAmount()->getValue()
                        ),
                    ],
                    'rrpprice' => $product->getData('price_rrp'),
                    'basePrice' => [
                        'amount' => $this->localeFormat->getNumber(
                            $priceInfo->getPrice('final_price')->getAmount()->getBaseAmount()
                        ),
                    ],
                    'finalPrice' => [
                        'amount' => $this->localeFormat->getNumber(
                            $priceInfo->getPrice('final_price')->getAmount()->getValue()
                        ),
                    ],
                    'tierPrices' => $tierPrices,
                    'msrpPrice' => [
                        'amount' => $this->localeFormat->getNumber(
                            $product->getMsrp()
                        ),
                    ],
                 ];
        }
        return $prices;
    }
}
