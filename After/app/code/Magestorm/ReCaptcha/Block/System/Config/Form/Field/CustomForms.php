<?php
namespace Magestorm\ReCaptcha\Block\System\Config\Form\Field;

/**
 * Backend system config array field renderer
 */
class CustomForms extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;

    /**
     * @var \Magento\Framework\View\Design\Theme\LabelFactory
     */
    protected $_labelFactory;
    protected $managePageRenderer;
    protected $optionRenderers = [];

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param \Magento\Framework\View\Design\Theme\LabelFactory $labelFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Magento\Framework\View\Design\Theme\LabelFactory $labelFactory,
        array $data = []
    ) {
        $this->_elementFactory = $elementFactory;
        $this->_labelFactory = $labelFactory;
        parent::__construct($context, $data);
    }

    /**
     * @var \Magento\Braintree\Block\Adminhtml\Form\Field\Countries
     */
    protected $attributeRenderer = null;

    protected function createOptionRenderer($id, $options)
    {
        $this->optionRenderers[$id] = $this->getLayout()->createBlock(
            '\Magento\Framework\View\Element\Html\Select',
            '',
            ['data' => ['is_render_to_js_template' => true]]
        )->setName($this->_getCellInputElementName($id))
            ->setId($this->_getCellInputElementId('<%- _id %>', $id))
            ->setClass('required')
            ->setOptions($options);

        return $this->optionRenderers[$id];
    }

    protected function getOptionRenderer($id)
    {
        if (array_key_exists($id, $this->optionRenderers)) {
            return $this->optionRenderers[$id];
        }
        return null;
    }

    /**
     * Returns renderer for country element
     *
     * @param $id
     * @return \Magento\Braintree\Block\Adminhtml\Form\Field\Countries
     */
    protected function getManagePageRenderer($id)
    {
        if (!$this->managePageRenderer) {
            $this->managePageRenderer = $this->getLayout()->createBlock(
                '\Magento\Framework\View\Element\Html\Select',
                '',
                ['data' => ['is_render_to_js_template' => true]]
            )->setName($this->_getCellInputElementName($id))
                ->setId($this->_getCellInputElementId('<%- _id %>', $id))
                ->setClass('required')
                ->setOptions([
                    ['value' => '0', 'label' => 'No'],
                    ['value' => '1', 'label' => 'Yes'],
                ]);
        }
        return $this->managePageRenderer;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        parent::_prepareToRender();

        $this->addColumn('url', [
            'label' => __('Url'),
            // 'class' => 'required',
            'style' => 'width: 120px'
        ]);

        $this->addColumn('form_selector', [
            'label' => __('Css Selector Form'),
            'class' => 'required',
            'style' => 'width: 120px'
        ]);

        $this->addColumn('button_selector', [
            'label' => __('Submit Selector'),
            'style' => 'width: 120px'
        ]);

        $this->addColumn('ko_selector', [
            'label' => __('Knockout Selector'),
            'style' => 'width: 120px'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return string
     */
    public function renderCellTemplate($columnName)
    {
        return parent::renderCellTemplate($columnName);
    }

    /**
     * Add a column to array-grid
     *
     * @param string $name
     * @param array $params
     * @return void
     */
    public function addColumn($name, $params)
    {
        parent::addColumn($name, $params);
        if (!empty($params['renderer']) && $params['renderer'] instanceof \Magento\Framework\Data\Form\Element\AbstractElement) {
            $this->_columns[$name]['renderer'] = $params['renderer'];
        }
    }

    /**
     * Prepare existing row data object
     *
     * @param \Magento\Framework\DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $row->setData('option_extra_attrs', []);
        return;
    }
}
