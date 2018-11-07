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

class Codnitive_AdvancedSitemap_Helper_Data extends Mage_Core_Helper_Data
{
    
    private $_url = 'http://extension.codnitive.com/status/';
    
    public function __construct()
    {
        if (time() > (int)$this->getLastCheck() + (int)$this->getFrq()) {
            $this->setLastCheck();
        }
    }
    
    public function getFrq()
    {
        return Mage::getStoreConfig(Codnitive_AdvancedSitemap_Model_Config::getNamespace() . 'chkfrq');
    }

    public function getLastCheck()
    {
        $namespace = Codnitive_AdvancedSitemap_Model_Config::EXTENSION_NAMESPACE;
        return Mage::app()->loadCache('codnitive_'.$namespace.'_lastcheck');
    }

    public function setLastCheck()
    {
        $namespace = Codnitive_AdvancedSitemap_Model_Config::EXTENSION_NAMESPACE;
        Mage::app()->saveCache(time(), 'codnitive_'.$namespace.'_lastcheck');
        return $this;
    }
    
    public function getConUrl()
    {
        return $this->_url;
    }

    public function curl($inf = null, $url = null)
    {
        $url = ($url === null) ? $this->_url : $url;
        
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $inf);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $data = curl_exec($ch);
            curl_close($ch);

            return $data;
        }
        catch (Exception $e) {
            return false;
        }
    }
    
    protected function _checkCert()
    {
        $nameSpace = Codnitive_AdvancedSitemap_Model_Config::getNamespace();

        $sernum = Mage::getStoreConfig($nameSpace . 'sernum');
        $regcod = Mage::getStoreConfig($nameSpace . 'regcod');
        $ownnam = Mage::getStoreConfig($nameSpace . 'ownnam');
        $ownmai = Mage::getStoreConfig($nameSpace . 'ownmai');
        
        try {
            $condition = empty($sernum) || !$sernum || empty($regcod) || !$regcod
                || empty($ownnam) || !$ownnam || empty($ownmai) || !$ownmai;

            $crypt = Varien_Crypt::factory()->init('3ee2a23ba72ce85081fae961d2e51b1b');
            $inf = array(
                'sn' => base64_encode($crypt->encrypt($this->decrypt((string)$sernum))),
                'rc' => base64_encode($crypt->encrypt($this->decrypt((string)$regcod))),
                'on' => base64_encode($crypt->encrypt((string)$ownnam)),
                'om' => base64_encode($crypt->encrypt((string)$ownmai)),
                'bu' => base64_encode($crypt->encrypt((string)Mage::getStoreConfig('web/unsecure/base_url'))),
                'en' => base64_encode($crypt->encrypt((string)Codnitive_AdvancedSitemap_Model_Config::EXTENSION_NAME)),
                'ev' => base64_encode($crypt->encrypt((string)Codnitive_AdvancedSitemap_Model_Config::EXTENSION_VERSION)),
                'es' => base64_encode($crypt->encrypt((string)Mage::getStoreConfig($nameSpace . 'active'))),
            );
            
            $data = $this->curl($inf);
            
            if ($condition || false == $data || '1' !== $data) {
                Mage::getConfig()->saveConfig($nameSpace.'active', 0)->reinit();
                Mage::app()->reinitStores();
            }
            
        }
        catch (Exception $e) {
            Mage::getConfig()->saveConfig($nameSpace.'active', 0)->reinit();
            Mage::app()->reinitStores();
        }
    }
    
}
