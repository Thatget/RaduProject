<?php

namespace Bss\WebShopAppsMatrixRate\Model\ResourceModel\Carrier;

class Matrixrate extends \WebShopApps\MatrixRate\Model\ResourceModel\Carrier\Matrixrate
{
	public function getRate(\Magento\Quote\Model\Quote\Address\RateRequest $request, $zipRangeSet = false)
    {
        $adapter = $this->getConnection();
        $shippingData=[];
        $postcode = trim($request->getDestPostcode());
        if ($zipRangeSet && is_numeric($postcode)) {
            #  Want to search for postcodes within a range
            // $zipSearchString = ' AND :postcode BETWEEN dest_zip AND dest_zip_to ';
            $zipSearchString = ' AND ' .(int)$postcode. ' BETWEEN dest_zip AND dest_zip_to ';
        } else {
            $zipSearchString = " AND :postcode LIKE dest_zip ";
        }

        for ($j=0; $j<8; $j++) {
            $select = $adapter->select()->from(
                $this->getMainTable()
            )->where(
                'website_id = :website_id'
            )->order(
                ['dest_country_id DESC', 'dest_region_id DESC', 'dest_zip DESC', 'condition_from_value DESC']
            );

            $zoneWhere='';
            $bind=[];
            switch ($j) {
                case 0: // country, region, city, postcode
                    $zoneWhere =  "dest_country_id = :country_id AND dest_region_id = :region_id AND STRCMP(LOWER(dest_city),LOWER(:city))= 0 " .$zipSearchString;
                    $bind = [
                        ':country_id' => $request->getDestCountryId(),
                        ':region_id' => (int)$request->getDestRegionId(),
                        ':city' => $request->getDestCity(),
                        ':postcode' => $postcode,
                    ];
                    break;
                case 1: // country, region, no city, postcode
                    $zoneWhere =  "dest_country_id = :country_id AND dest_region_id = :region_id AND dest_city='' "
                        .$zipSearchString;
                    $bind = [
                        ':country_id' => $request->getDestCountryId(),
                        ':region_id' => (int)$request->getDestRegionId(),
                        ':postcode' => $postcode,
                    ];
                    break;
                case 2: // country, state, city, no postcode
                    $zoneWhere = "dest_country_id = :country_id AND dest_region_id = :region_id AND STRCMP(LOWER(dest_city),LOWER(:city))= 0 AND dest_zip ='*'";
                    $bind = [
                        ':country_id' => $request->getDestCountryId(),
                        ':region_id' => (int)$request->getDestRegionId(),
                        ':city' => $request->getDestCity(),
                    ];
                    break;
                case 3: //country, city, no region, no postcode
                    $zoneWhere =  "dest_country_id = :country_id AND dest_region_id = '0' AND STRCMP(LOWER(dest_city),LOWER(:city))= 0 AND dest_zip ='*'";
                    $bind = [
                        ':country_id' => $request->getDestCountryId(),
                        ':city' => $request->getDestCity(),
                    ];
                    break;
                case 4: // country, postcode
                    $zoneWhere =  "dest_country_id = :country_id AND dest_region_id = '0' AND dest_city ='*' "
                        .$zipSearchString;
                    $bind = [
                        ':country_id' => $request->getDestCountryId(),
                        ':postcode' => (int)$request->getDestPostcode(),
                    ];
                    break;
                case 5: // country, region
                    $zoneWhere =  "dest_country_id = :country_id AND dest_region_id = :region_id  AND dest_city ='*' AND dest_zip ='*'";
                    $bind = [
                        ':country_id' => $request->getDestCountryId(),
                        ':region_id' => (int)$request->getDestRegionId(),
                    ];
                    break;
                case 6: // country
                    $zoneWhere =  "dest_country_id = :country_id AND dest_region_id = '0' AND dest_city ='*' AND dest_zip ='*'";
                    $bind = [
                        ':country_id' => $request->getDestCountryId(),
                    ];
                    break;
                case 7: // nothing
                    $zoneWhere =  "dest_country_id = '0' AND dest_region_id = '0' AND dest_city ='*' AND dest_zip ='*'";
                    break;
            }

            $select->where($zoneWhere);

            $bind[':website_id'] = (int)$request->getWebsiteId();
            $bind[':condition_name'] = $request->getConditionMRName();
            $condition = $request->getData($request->getConditionMRName());
            if ($condition == null || $condition == "") {
                $condition = 0;
            }
            $bind[':condition_value'] = $condition;

            $select->where('condition_name = :condition_name');
            $select->where('condition_from_value <= :condition_value');
            $select->where('condition_to_value >= :condition_value');

            $results = $adapter->fetchAll($select, $bind);

            if (!empty($results)) {
                foreach ($results as $data) {
                    $shippingData[]=$data;
                }
                break;
            }
        }

        return $shippingData;
    }
}
