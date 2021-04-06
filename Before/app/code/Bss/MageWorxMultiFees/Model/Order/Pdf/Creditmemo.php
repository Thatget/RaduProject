<?php

namespace Bss\MageWorxMultiFees\Model\Order\Pdf;

class Creditmemo extends \Magento\Sales\Model\Order\Pdf\Creditmemo
{
	private $feeHelper;

	public function __construct(
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Sales\Model\Order\Pdf\Config $pdfConfig,
        \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory,
        \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \MageWorx\MultiFees\Helper\Data $feeHelper,
        array $data = []
    ) {
    	$this->feeHelper = $feeHelper;
        parent::__construct(
            $paymentData,
            $string,
            $scopeConfig,
            $filesystem,
            $pdfConfig,
            $pdfTotalFactory,
            $pdfItemsFactory,
            $localeDate,
            $inlineTranslation,
            $addressRenderer,
            $storeManager,
            $localeResolver,
            $data
        );
    }

	public function getPdf($creditmemos = [])
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('creditmemo');

        $pdf = new \Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new \Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        foreach ($creditmemos as $creditmemo) {
            if ($creditmemo->getStoreId()) {
                $this->_localeResolver->emulate($creditmemo->getStoreId());
                $this->_storeManager->setCurrentStore($creditmemo->getStoreId());
            }
            $page = $this->newPage();
            $order = $creditmemo->getOrder();
            /* Add image */
            $this->insertLogo($page, $creditmemo->getStore());
            /* Add address */
            $this->insertAddress($page, $creditmemo->getStore());
            /* Add head */
            $this->insertOrder(
                $page,
                $order,
                $this->_scopeConfig->isSetFlag(
                    self::XML_PATH_SALES_PDF_CREDITMEMO_PUT_ORDER_ID,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $order->getStoreId()
                )
            );
            /* Add document text and number */
            $this->insertDocumentNumber($page, __('Credit Memo # ') . $creditmemo->getIncrementId());

            /* Add MageWorx_MultiFees table */
            $this->insertMageWorxMultiFees($page, $order);

            /* Add table head */
            $this->_drawHeader($page);
            /* Add body */
            foreach ($creditmemo->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                /* Draw item */
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
            }
            /* Add totals */
            $this->insertTotals($page, $creditmemo);
        }
        $this->_afterGetPdf();
        if ($creditmemo->getStoreId()) {
            $this->_localeResolver->revert();
        }
        return $pdf;
    }

    private function insertMageWorxMultiFees(&$page, $order)
    {
    	if ($mageworxFeeDetails = $order->getMageworxFeeDetails()) {
    		$displayTaxInSalesMode = $this->feeHelper->getTaxInSales();
    		$feesAsArray = json_decode($mageworxFeeDetails, true);
	    	$this->y -= -8;
	        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
	        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
	        $page->setLineWidth(0.5);
	        $page->drawRectangle(25, $this->y, 570, $this->y - 20);
	        $page->setFillColor(new \Zend_Pdf_Color_Rgb(1, 1, 1));
	        $page->drawRectangle(25, $this->y - 20, 570, $this->y - 77);

	        $page->setFillColor(new \Zend_Pdf_Color_RGB(0.1, 0.1, 0.1));
	        $this->_setFontBold($page, 12);

	        $page->drawText(__('Extra Fees'), 35, $this->y - 13, 'UTF-8');
	        $this->_setFontRegular($page, 10);
	        $position = 0;
	        foreach ($feesAsArray as $fees) {
	        	$page->drawText($fees['title'], 33, $this->y - 33, 'UTF-8');
	        	foreach ($fees['options'] as $feeItem) {
	        		switch ($displayTaxInSalesMode) {
	                    case \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX:
	                        $amount   = $this->formatAmountValue($feeItem['price'], $order);
	                        break;
	                    case \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH:
	                        $amount = $this->formatAmountValue((float)$feeItem['price'] - (float)$feeItem['tax'], $order);
	                        break;
	                    case \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX:
	                        $amount = $this->formatAmountValue((float)$feeItem['price'] - (float)$feeItem['tax'], $order);
	                        break;
	                    default:
	                        break;
	                }
	                $title = $feeItem['title'];
	                $title .= ' - ';
	                $title .= $amount;
	        		$page->drawText($title, 53, $this->y - 53, 'UTF-8');
	        		$position += 17;
	        	}
	        }

	        $this->y -= 84 + $position;
	    }
    }

    private function formatAmountValue($amount, $order)
    {
        $amount = $order->formatPriceTxt($amount);
        if ($this->getAmountPrefix()) {
            $amount = $this->getAmountPrefix() . $amount;
        }

        return $amount;
    }
}
