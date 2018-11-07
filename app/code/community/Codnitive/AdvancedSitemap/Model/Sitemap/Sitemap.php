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

class Codnitive_AdvancedSitemap_Model_Sitemap_Sitemap extends Mage_Sitemap_Model_Sitemap
{
    
    protected $_config;
    
    protected $_baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->_config = Mage::getModel('advancedsitemap/config');
    }
    
    public function generateXml()
    {
        if (!$this->_config->isActive()) {
            return parent::generateXml();
        }
        
        $filesCount  = 1;
        $urlsCount   = 1;
        $useSitemapIndex = $this->_config->useSitemapIndex();
        $filesName       = $this->_config->getSitemapFileName();
        
        $storeId = $this->getStoreId();
        $this->_baseUrl = $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        
        $this->setSitemapFilename($this->getSitemapFilename());
        
        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $this->getPath()));

        if ($io->fileExists($this->getSitemapFilename()) && !$io->isWriteable($this->getSitemapFilename())) {
            Mage::throwException(Mage::helper('advancedsitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $this->getSitemapFilename(), $this->getPath()));
        }

        $io->streamOpen($this->getSitemapFilename());

        $io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        if ($useSitemapIndex) {
            $io->streamWrite('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
            $files = $this->_creatNewFile($io, $filesName, $filesCount);
        }
        else {
            $io->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
        }

        $changefreq = (string)Mage::getStoreConfig('sitemap/category/changefreq', $storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/category/priority', $storeId);
        $collection = Mage::getModel('advancedsitemap/resource_sitemap_catalog_category', array('config' => $this->_config))
                ->getCollection($storeId);
        foreach ($collection as $item) {
            $date = date('Y-m-d', strtotime(($item->getUpdatedAt() === NULL) 
                        ? $item->getCreatedAt() 
                        : $item->getUpdatedAt())
                    );
                
            $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($baseUrl . $item->getUrl()),
                $date,
                $changefreq,
                $priority
            );
            
            if ($useSitemapIndex) {
                $files->streamWrite($xml);
                $urlsCount++;
                if ($urlsCount > $this->_config->getMaxFileUrls()) {
                    $filesCount++;
                    $urlsCount = 1;
                    $this->_closeFile($files);
                    $files = $this->_creatNewFile($io, $filesName, $filesCount);
                }
            }
            else {
                $io->streamWrite($xml);
            }
        }
        unset($collection);

        $changefreq = (string)Mage::getStoreConfig('sitemap/product/changefreq', $storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/product/priority', $storeId);
        $collection = Mage::getModel('advancedsitemap/resource_sitemap_catalog_product', array('config' => $this->_config))
                ->getCollection($storeId);
        foreach ($collection as $item) {
            $date = date('Y-m-d', strtotime(($item->getUpdatedAt() === NULL) 
                        ? $item->getCreatedAt() 
                        : $item->getUpdatedAt())
                    );
            
            $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($baseUrl . $item->getUrl()),
                $date,
                $changefreq,
                $priority
            );
            
            if ($useSitemapIndex) {
                $files->streamWrite($xml);
                $urlsCount++;
                if ($urlsCount > $this->_config->getMaxFileUrls()) {
                    $filesCount++;
                    $urlsCount = 1;
                    $this->_closeFile($files);
                    $files = $this->_creatNewFile($io, $filesName, $filesCount);
                }
            }
            else {
                $io->streamWrite($xml);
            }
        }
        unset($collection);

        $changefreq = (string)Mage::getStoreConfig('sitemap/page/changefreq', $storeId);
        $priority   = (string)Mage::getStoreConfig('sitemap/page/priority', $storeId);
        $collection = Mage::getModel('advancedsitemap/resource_sitemap_cms_page', array('config' => $this->_config))
                ->getCollection($storeId);
        foreach ($collection as $item) {
            $date = date('Y-m-d', strtotime(($item->getUpdateTime() === NULL) 
                        ? $item->getCreationTime() 
                        : $item->getUpdateTime())
                    );
            
            $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
                htmlspecialchars($baseUrl . $item->getUrl()),
                $date,
                $changefreq,
                $priority
            );
            
            if ($useSitemapIndex) {
                $files->streamWrite($xml);
                $urlsCount++;
                if ($urlsCount > $this->_config->getMaxFileUrls()) {
                    $filesCount++;
                    $urlsCount = 1;
                    $this->_closeFile($files);
                    $files = $this->_creatNewFile($io, $filesName, $filesCount);
                }
            }
            else {
                $io->streamWrite($xml);
            }
        }
        unset($collection);

        if ($useSitemapIndex) {
            $this->_closeFile($files);
            $io->streamWrite('</sitemapindex>');
        }
        else {
            $io->streamWrite('</urlset>');
        }
        $io->streamClose();

        $this->setSitemapTime(Mage::getSingleton('core/date')->gmtDate('Y-m-d H:i:s'));
        $this->save();

        return $this;
    }
    
    protected function _creatNewFile($io, $filesName, $filesCount)
    {
        $nameParts = explode('.', $filesName);
        $extension = (isset($nameParts[1]) && !empty($nameParts[1])) ? $nameParts[1] : 'xml';
        $filesName = $nameParts[0] . $filesCount . '.' . $extension;
        $fileInfoXml = sprintf('<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>',
            htmlspecialchars($this->_baseUrl . str_replace('\\', '/', substr($this->getSitemapPath(), 1)) . $filesName),
            Mage::getSingleton('core/date')->gmtDate('Y-m-d')
        );
        $io->streamWrite($fileInfoXml);
        
        $files = new Varien_Io_File();
        $files->setAllowCreateFolders(true);
        $files->open(array('path' => $this->getPath()));
        if ($files->fileExists($filesName) && !$io->isWriteable($filesName)) {
            Mage::throwException(Mage::helper('advancedsitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $this->getSitemapFilename(), $this->getPath()));
        }
        $files->streamOpen($filesName);
        $files->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        $files->streamWrite('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
        
        return $files;
    }
    
    protected function _closeFile($files)
    {
        $files->streamWrite('</urlset>');
        $files->streamClose();
    }
    
    public function getSitemapFilename()
    {
        $filename = $this->_config->getIndexFileName();
        if ($this->_config->useSitemapIndex() && $this->_config->isActive() && !empty($filename)) {
            return $filename;
        }
        
        return parent::getSitemapFilename();
    }

}
		