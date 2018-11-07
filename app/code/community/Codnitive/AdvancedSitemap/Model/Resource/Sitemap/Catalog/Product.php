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
 * Sitemap resource product collection model
 *
 * @category   Codnitive
 * @package    Codnitive_AdvancedSitemap
 * @author     Hassan Barza <support@codnitive.com>
 */
class Codnitive_AdvancedSitemap_Model_Resource_Sitemap_Catalog_Product extends Mage_Sitemap_Model_Resource_Catalog_Product
{

	protected $_config;

	public function __construct(array $arguments)
	{
		parent::__construct();
		$this->_config = $arguments['config'];
	}

	public function getCollection($storeId)
	{
		$products = array();

		$store = Mage::app()->getStore($storeId);

		if (!$store) {
			return false;
		}

		$urCondions = array(
			'e.entity_id=ur.product_id',
			'ur.category_id IS NULL',
			$this->_getWriteAdapter()->quoteInto('ur.store_id=?', $store->getId()),
			$this->_getWriteAdapter()->quoteInto('ur.is_system=?', 1),
		);

		$fields = array($this->getIdFieldName());
		if (!$this->_config->useGenerationTimeDate()) {
			$fields = array_merge($fields, array('created_at', 'updated_at'));
		}

		$this->_select = $this->_getWriteAdapter()->select()
			->from(array('e' => $this->getMainTable()), $fields)
			->join(
				array('w' => $this->getTable('catalog/product_website')),
				'e.entity_id=w.product_id',
				array()
			)
			->where('w.website_id=?', $store->getWebsiteId())
			->joinLeft(
				array('ur' => $this->getTable('core/url_rewrite')),
				join(' AND ', $urCondions),
				array('url' => 'request_path')
			);

		$this->_addFilter($storeId, 'visibility', Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds(), 'in');
		$this->_addFilter($storeId, 'status', Mage::getSingleton('catalog/product_status')->getVisibleStatusIds(), 'in');

		$query = $this->_getWriteAdapter()->query($this->_select);
		while ($row = $query->fetch()) {
			$product = $this->_prepareProduct($row);
			$products[$product->getId()] = $product;
		}

		return $products;
	}

	protected function _prepareProduct(array $productRow)
	{
		$product = new Varien_Object();
		$product->setId($productRow[$this->getIdFieldName()]);
		$productUrl = !empty($productRow['url']) ? $productRow['url'] : 'catalog/product/view/id/' . $product->getId();
		$product->setUrl($productUrl);

		if ($this->_config->useGenerationTimeDate()) {
			$product->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate('Y-m-d'));
		}
		else {
			$product->setCreatedAt($productRow['created_at']);
			$product->setUpdatedAt($productRow['updated_at']);
		}

		return $product;
	}

	protected function _addFilter($storeId, $attributeCode, $value, $type = '=')
	{
		if (!isset($this->_attributesCache[$attributeCode])) {
			$this->_loadAttribute($attributeCode);
		}

		$attribute = $this->_attributesCache[$attributeCode];

		if (!$this->_select instanceof Zend_Db_Select) {
			return false;
		}

		switch ($type) {
			case '=':
				$conditionRule = '=?';
				break;
			case 'in':
				$conditionRule = ' IN(?)';
				break;
			default:
				return false;
				break;
		}

		if ($attribute['backend_type'] == 'static') {
			$this->_select->where('e.' . $attributeCode . $conditionRule, $value);
		} else {
			$this->_select->join(
				array('t1_' . $attributeCode => $attribute['table']),
				'e.entity_id=t1_' . $attributeCode . '.entity_id AND t1_' . $attributeCode . '.store_id=0',
				array()
			)
				->where('t1_' . $attributeCode . '.attribute_id=?', $attribute['attribute_id']);

			if ($attribute['is_global']) {
				$this->_select->where('t1_' . $attributeCode . '.value' . $conditionRule, $value);
			} else {
				$ifCase = $this->_select->getAdapter()->getCheckSql('t2_' . $attributeCode . '.value_id > 0',
					't2_' . $attributeCode . '.value', 't1_' . $attributeCode . '.value'
				);
				$this->_select->joinLeft(
					array('t2_' . $attributeCode => $attribute['table']),
					$this->_getWriteAdapter()->quoteInto(
						't1_' . $attributeCode . '.entity_id = t2_' . $attributeCode . '.entity_id AND t1_'
							. $attributeCode . '.attribute_id = t2_' . $attributeCode . '.attribute_id AND t2_'
							. $attributeCode . '.store_id = ?', $storeId
					),
					array()
				)
				->where('(' . $ifCase . ')' . $conditionRule, $value);
			}
		}

		return $this->_select;
	}
}
