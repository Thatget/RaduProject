<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\MultiFees\Setup;

use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Serializer used to convert data in product_options field
 */
class SerializedToJsonDataConverter extends \Magento\Framework\DB\DataConverter\SerializedToJson
{
    /**
     * Convert from serialized to JSON format
     *
     * @param string $value
     * @return string
     */
    public function convert($value)
    {
        if ($value === '') {
            return $value;
        }

        return parent::convert($value);
    }
}
