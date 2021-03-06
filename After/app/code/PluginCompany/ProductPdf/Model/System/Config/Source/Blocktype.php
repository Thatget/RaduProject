<?php
/**
 *
 * Created by:  Milan Simek
 * Company:     Plugin Company
 *
 * LICENSE: http://plugin.company/docs/magento-extensions/magento-extension-license-agreement
 *
 * YOU WILL ALSO FIND A PDF COPY OF THE LICENSE IN THE DOWNLOADED ZIP FILE
 *
 * FOR QUESTIONS AND SUPPORT
 * PLEASE DON'T HESITATE TO CONTACT US AT:
 *
 * SUPPORT@PLUGIN.COMPANY
 *
 */

namespace PluginCompany\ProductPdf\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Blocktype extends AbstractOption implements ArrayInterface
{

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'link' => 'Text',
            'icon' => 'Icon',
            'icon_and_link' => 'Icon & Text',
       );
    }
}