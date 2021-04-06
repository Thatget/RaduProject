<?php
/**
 * Magestorm_Base
 *
 * @category    Magestorm
 * @package     Magestorm_Base
 * @author      Magestorm Team <magestormteam@gmail.com>
 * @copyright   Magestorm (http://magestormweb.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magestorm\Base\Block\Adminhtml\System\Config\Fieldset;

class Hint extends \Magento\Backend\Block\Template implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    /**
     * @var string
     */
    protected $_template = 'Magestorm_Base::system/config/fieldset/hint.phtml';

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $_metaData;

    /**
     * @var \Magento\Framework\Module\ModuleList\Loader
     */
    private $_loader;

    /**
     * @var array
     */
    private $_modulesInstall;

    /**
     * @var \Magento\Framework\Module\ModuleList\Loader
     */
    static $_module = ['mstbuynow' => 'Magestorm_Buynow'];
    
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetaData
     * @param \Magento\Framework\Module\ModuleList\Loader $loader
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\App\ProductMetadataInterface $productMetaData,
        \Magento\Framework\Module\ModuleList\Loader $loader,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_metaData = $productMetaData;
        $this->_loader = $loader;
    }
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return mixed
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->toHtml();
    }
    public function getPxParams($moduleName)
    {

        $extension = $moduleName.";{$this->getModuleVersion()}";
        $mageEdition = $this->_metaData->getEdition();
        switch ($mageEdition) {
            case 'Community':
                $mageEdition = 'CE';
                break;
            case 'Enterprise':
                $mageEdition = 'EE';
                break;
        }
        $mageVersion = $this->_metaData->getVersion();
        $mage = "Magento {$mageEdition};{$mageVersion}";
        $hash = md5($extension . '_' . $mage . '_' . $extension);
        return "ext=$extension&mage={$mage}&ctrl={$hash}";
    }

    public function getModuleVersion($moduleName)
    {
        $modules = $this->getListModules();
        $v = "";
        if (isset($modules[$moduleName])) {
            $v = $modules[$moduleName]['setup_version'];
        }
        return $v;
    }

    public function getModuleMSTName()
    {
        $pathInfo = $this->getRequest()->getPathInfo();
        foreach (self::$_module as $k => $v) {
            if (strpos($pathInfo, $k) !== false) {
                return $v;
            }
        }
        $modules = $this->getListModules();
        foreach ($modules as $k => $v) {
            if ((strpos($pathInfo, strtolower($k)) !== false)
                && (strpos($pathInfo, 'magestorm_') !== false)) {
                return $k;
            }
        }
        return null;
    }

    protected function getListModules()
    {
        if (!isset($this->_modulesInstall)) {
            $this->_modulesInstall = $this->_loader->load();
        }

        return $this->_modulesInstall;
    }
}
