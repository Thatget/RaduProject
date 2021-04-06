<?php
namespace Bss\RwMagentoNewsletter\Plugin\Subscriber;

use Magento\Framework\App\Response\Http as responseHttp;
use Magento\Framework\UrlInterface as UrlInterface;

class Redirect
{
    protected $response;
    protected $_url;
    public function __construct(
        responseHttp $response,
        UrlInterface $url
    ) {
        $this->response = $response;
        $this->_url = $url;
    }
    public function afterExecute(\Magento\Newsletter\Controller\Subscriber\NewAction $subject, $result) // @codingStandardsIgnoreLine
    {
        $subject = $subject;
        $url = $this->_url->getUrl('newsletter-subscription-success');
        $this->response->setRedirect($url);
        return $result;
    }

}