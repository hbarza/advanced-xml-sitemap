<?php
/**
 * CODNITIVE
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE_EULA.html.
 * It is also available through the world-wide-web at this URL:
 * http://www.codnitive.com/en/terms-of-service-softwares/
 * http://www.codnitive.com/fa/terms-of-service-softwares/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future.
 *
 * @category   Codnitive
 * @package    Codnitive_AdvancedSitemap
 * @author     Hassan Barza <support@codnitive.com>
 * @copyright  Copyright (c) 2012 CODNITIVE Co. (http://www.codnitive.com)
 * @license    http://www.codnitive.com/en/terms-of-service-softwares/ End User License Agreement (EULA 1.0)
 */

/**
 * Sitemap cms page collection model
 *
 * @category   Codnitive
 * @package    Codnitive_AdvancedSitemap
 * @author     Hassan Barza <support@codnitive.com>
 */
class Codnitive_AdvancedSitemap_Model_Resource_Sitemap_Cms_Page extends Mage_Sitemap_Model_Resource_Cms_Page
{
    
    protected $_config;
    
    public function __construct(array $arguments)
    {
        parent::__construct();
        $this->_config = $arguments['config'];
    }
    
    public function getCollection($storeId)
    {
        $pages = array();
        $fields = array($this->getIdFieldName(), 'identifier AS url');
        if (!$this->_config->useGenerationTimeDate()) {
            $fields = array_merge($fields, array('creation_time', 'update_time'));
        }
        
        $select = $this->_getWriteAdapter()->select()
            ->from(array('main_table' => $this->getMainTable()), $fields)
            ->join(
                array('store_table' => $this->getTable('cms/page_store')),
                'main_table.page_id=store_table.page_id',
                array()
            )
            ->where('main_table.is_active=1')
            ->where('store_table.store_id IN(?)', array(0, $storeId));
        $query = $this->_getWriteAdapter()->query($select);
        while ($row = $query->fetch()) {
            if ($row['url'] == Mage_Cms_Model_Page::NOROUTE_PAGE_ID) {
                continue;
            }
            $page = $this->_prepareObject($row);
            $pages[$page->getId()] = $page;
        }

        return $pages;
    }

    protected function _prepareObject(array $data)
    {
        $object = new Varien_Object();
        $object->setId($data[$this->getIdFieldName()]);
        $object->setUrl($data['url']);
        
        if ($this->_config->useGenerationTimeDate()) {
            $object->setUpdateTime(Mage::getSingleton('core/date')->gmtDate('Y-m-d'));
        }
        else {
            $object->setCreationTime($data['creation_time']);
            $object->setUpdateTime($data['update_time']);
        }
        
        return $object;
    }
}
