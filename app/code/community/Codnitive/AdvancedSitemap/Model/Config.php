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

class Codnitive_AdvancedSitemap_Model_Config
{

	const PATH_NAMESPACE      = 'codnitiveseo';
	const EXTENSION_NAMESPACE = 'advancedsitemap';

	const EXTENSION_NAME    = 'Advanced XML Sitemap';
	const EXTENSION_VERSION = '1.0.10';
	const EXTENSION_EDITION = 'Basic';

	public static function getNamespace()
	{
		return self::PATH_NAMESPACE . '/' . self::EXTENSION_NAMESPACE . '/';
	}

	public function getExtensionName()
	{
		return self::EXTENSION_NAME;
	}

	public function getExtensionVersion()
	{
		return self::EXTENSION_VERSION;
	}

	public function getExtensionEdition()
	{
		return self::EXTENSION_EDITION;
	}

	public function isActive()
	{
		return Mage::getStoreConfigFlag(self::getNamespace() . 'active');
	}

	public function useSitemapIndex()
	{
		if (!$this->isActive()) {
			return false;
		}
		return Mage::getStoreConfigFlag(self::getNamespace() . 'use_sitemapindex');
	}

	public function getIndexFileName()
	{
		return Mage::getStoreConfig(self::getNamespace() . 'index_file_name');
	}

	public function getSitemapFileName()
	{
		return Mage::getStoreConfig(self::getNamespace() . 'sitemap_file_name');
	}

	public function getMaxFileUrls()
	{
		return Mage::getStoreConfig(self::getNamespace() . 'max_file_urls');
	}

	public function useGenerationTimeDate()
	{
		return Mage::getStoreConfigFlag(self::getNamespace() . 'generation_date');
	}

}
