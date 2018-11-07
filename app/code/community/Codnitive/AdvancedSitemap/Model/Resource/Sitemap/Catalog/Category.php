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
 * Sitemap resource catalog collection model
 *
 * @category   Codnitive
 * @package    Codnitive_AdvancedSitemap
 * @author     Hassan Barza <support@codnitive.com>
 */
class Codnitive_AdvancedSitemap_Model_Resource_Sitemap_Catalog_Category extends Mage_Sitemap_Model_Resource_Catalog_Category
{

	protected $_config;

	public function __construct(array $arguments)
	{
		parent::__construct();
		$this->_config = $arguments['config'];
	}

	public function getCollection($storeId)
	{
		$categories = array();

		$store = Mage::app()->getStore($storeId);

		if (!$store) {
			return false;
		}

		$this->_select = $this->_getWriteAdapter()->select()
			->from($this->getMainTable())
			->where($this->getIdFieldName() . '=?', $store->getRootCategoryId());
		$categoryRow = $this->_getWriteAdapter()->fetchRow($this->_select);

		if (!$categoryRow) {
			return false;
		}

		$urConditions = array(
			'e.entity_id=ur.category_id',
			$this->_getWriteAdapter()->quoteInto('ur.store_id=?', $store->getId()),
			'ur.product_id IS NULL',
			$this->_getWriteAdapter()->quoteInto('ur.is_system=?', 1),
		);

		$fields = array($this->getIdFieldName());
		if (!$this->_config->useGenerationTimeDate()) {
			$fields = array_merge($fields, array('created_at', 'updated_at'));
		}

		$this->_select = $this->_getWriteAdapter()->select()
			->from(array('e' => $this->getMainTable()), $fields)
			->joinLeft(
				array('ur' => $this->getTable('core/url_rewrite')),
				join(' AND ', $urConditions),
				array('url'=>'request_path')
			)
			->where('e.path LIKE ?', $categoryRow['path'] . '/%');

		$this->_addFilter($storeId, 'is_active', 1);

		$query = $this->_getWriteAdapter()->query($this->_select);
		while ($row = $query->fetch()) {
			$category = $this->_prepareCategory($row);
			$categories[$category->getId()] = $category;
		}

		return $categories;
	}

	protected function _prepareCategory(array $categoryRow)
	{
		$category = new Varien_Object();
		$category->setId($categoryRow[$this->getIdFieldName()]);
		$categoryUrl = !empty($categoryRow['url']) ? $categoryRow['url'] : 'catalog/category/view/id/' . $category->getId();
		$category->setUrl($categoryUrl);

		if ($this->_config->useGenerationTimeDate()) {
			$category->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate('Y-m-d'));
		}
		else {
			$category->setCreatedAt($categoryRow['created_at']);
			$category->setUpdatedAt($categoryRow['updated_at']);
		}

		return $category;
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
