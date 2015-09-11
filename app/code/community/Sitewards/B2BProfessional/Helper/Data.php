<?php

/**
 * Sitewards_B2BProfessional_Helper_Data
 *  - Helper containing the checks for
 *      - extension is active,
 *      - product is active,
 *      - is the category active,
 *
 * @category    Sitewards
 * @package     Sitewards_B2BProfessional
 * @copyright   Copyright (c) 2014 Sitewards GmbH (http://www.sitewards.com/)
 */
class Sitewards_B2BProfessional_Helper_Data extends Sitewards_B2BProfessional_Helper_Core
{
    /**
     * Path for the config for extension active status
     */
    const CONFIG_EXTENSION_ACTIVE = 'b2bprofessional/generalsettings/active';

    /**
     * Path for the config for price block class names
     */
    const CONFIG_EXTENSION_PRICE_BLOCKS = 'b2bprofessional/generalsettings/priceblocks';

    /**
     * Path for the config for login message
     */
    const CONFIG_EXTENSION_LOGIN_MESSAGE = 'b2bprofessional/generalsettings/login_message';

    /**
     * Variable for if the extension is active
     *
     * @var bool
     */
    protected $bExtensionActive;

    /**
     * Variable for the login message
     *
     * @var string
     */
    protected $sLoginMessage;

    /**
     * Variable for if the extension is active by category
     *
     * @var bool
     */
    protected $bExtensionActiveByCategory;

    /**
     * Variable for if the extension is active by customer group
     *
     * @var bool
     */
    protected $bExtensionActiveByCustomerGroup;

    /**
     * Variable for the extension's price blocks
     *
     * @var string[]
     */
    protected $aPriceBlockClassNames;

    /**
     * Variable for the add-to-cart blocks' layout names
     *
     * @var string[]
     */
    protected $aAddToCartBlockLayoutNames = array(
        'product.info.addtocart'
    );

    /**
     * Check to see if the extension is active
     *
     * @return bool
     */
    public function isExtensionActive()
    {
        return $this->getStoreFlag(self::CONFIG_EXTENSION_ACTIVE, 'bExtensionActive');
    }

    /**
     * Return the login message to be displayed instead of the price block
     *
     * @return string
     */
    public function getLoginMessage()
    {
        $loginMessage = '<span class="login-message">' . $this->getStoreConfig(self::CONFIG_EXTENSION_LOGIN_MESSAGE, 'sLoginMessage') . '</span>';
        return $loginMessage;
    }

    /**
     * Check to see if the extension is active by category
     *
     * @return bool
     */
    protected function isExtensionActiveByCategory()
    {
        if ($this->bExtensionActiveByCategory === null) {
            $this->bExtensionActiveByCategory = Mage::helper(
                'sitewards_b2bprofessional/category'
            )->isExtensionActivatedByCategory();
        }
        return $this->bExtensionActiveByCategory;
    }

    /**
     * Check to see if the extension is active by user group
     *
     * @return bool
     */
    protected function isExtensionActivatedByCustomerGroup()
    {
        if ($this->bExtensionActiveByCustomerGroup === null) {
            $this->bExtensionActiveByCustomerGroup = Mage::helper(
                'sitewards_b2bprofessional/customer'
            )->isExtensionActivatedByCustomerGroup();
        }
        return $this->bExtensionActiveByCustomerGroup;
    }

    /**
     * Check to see if the block is a price block
     *
     * @param Mage_Core_Block_Template $oBlock
     * @return bool
     */
    public function isBlockPriceBlock($oBlock)
    {
        $aPriceBlockClassNames = $this->getPriceBlocks();
        return in_array(get_class($oBlock), $aPriceBlockClassNames);
    }

    /**
     * Check to see if the block is an add-to-cart block
     *
     * @param Mage_Core_Block_Template $oBlock
     * @return bool
     */
    public function isBlockAddToCartBlock($oBlock)
    {
        $aAddToCartBlockClassNames = $this->getAddToCartBlockLayoutNames();
        return in_array($oBlock->getNameInLayout(), $aAddToCartBlockClassNames);
    }

    /**
     * Check to see if it's the add-to-cart block should be hidden
     *
     * @param Mage_Core_Block_Template $oBlock
     * @return bool
     */
    public function isAddToCartBlockAndHidden($oBlock)
    {
        if ($this->isBlockAddToCartBlock($oBlock)) {
            $oProduct = $oBlock->getProduct();
            if ($this->isProductActive($oProduct) === false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check to see if the given product is active
     *  - In this case active means product behaves as normal in a magento shop
     *
     * @param Mage_Catalog_Model_Product $oProduct
     * @return bool
     */
    public function isProductActive(Mage_Catalog_Model_Product $oProduct)
    {
        $bIsProductActive = true;
        if ($this->isExtensionActive() === true) {
            $bCheckCategory      = $this->isExtensionActiveByCategory();
            $bCheckUser          = $this->isExtensionActivatedByCustomerGroup();
            $bIsCustomerLoggedIn = $this->isCustomerLoggedIn();

            /** @var Sitewards_B2BProfessional_Helper_Category $oCategoryHelper */
            $oCategoryHelper = Mage::helper('sitewards_b2bprofessional/category');
            /** @var Sitewards_B2BProfessional_Helper_Customer $oCustomerHelper */
            $oCustomerHelper = Mage::helper('sitewards_b2bprofessional/customer');

            $bIsCategoryEnabled      = $oCategoryHelper->isCategoryActiveByProduct($oProduct);
            $bIsCustomerGroupEnabled = $oCustomerHelper->isCustomerGroupActive();

            if ($bCheckCategory && $bCheckUser) {
                $bIsProductActive = !($bIsCategoryEnabled && $bIsCustomerGroupEnabled);
            } elseif ($bCheckUser) {
                $bIsProductActive = !$bIsCustomerGroupEnabled;
            } elseif ($bCheckCategory) {
                if ($bIsCustomerLoggedIn) {
                    $bIsProductActive = true;
                } else {
                    $bIsProductActive = !$bIsCategoryEnabled;
                }
            } else {
                $bIsProductActive = $bIsCustomerLoggedIn;
            }
        }

        return $bIsProductActive;
    }

    /**
     * From an array of category ids check to see if any are enabled via the extension to hide prices
     *
     * @param int[] $aCategoryIds
     * @return bool
     */
    public function hasEnabledCategories($aCategoryIds)
    {
        $bHasCategories = false;
        if ($this->isExtensionActive() === true) {
            $bCheckCategory      = $this->isExtensionActiveByCategory();
            $bCheckUser          = $this->isExtensionActivatedByCustomerGroup();
            $bIsCustomerLoggedIn = $this->isCustomerLoggedIn();

            /** @var Sitewards_B2BProfessional_Helper_Category $oCategoryHelper */
            $oCategoryHelper = Mage::helper('sitewards_b2bprofessional/category');
            /** @var Sitewards_B2BProfessional_Helper_Customer $oCustomerHelper */
            $oCustomerHelper = Mage::helper('sitewards_b2bprofessional/customer');

            $bHasActiveCategories = $oCategoryHelper->hasActiveCategory($aCategoryIds);
            $bIsUserGroupActive   = $oCustomerHelper->isCustomerGroupActive();

            if ($bCheckCategory && $bCheckUser) {
                $bHasCategories = $bHasActiveCategories && $bIsUserGroupActive;
            } elseif ($bCheckUser) {
                $bHasCategories = $bIsUserGroupActive;
            } elseif ($bCheckCategory) {
                if ($bIsCustomerLoggedIn) {
                    $bHasCategories = false;
                } else {
                    $bHasCategories = $bHasActiveCategories;
                }
            } else {
                $bHasCategories = !$bIsCustomerLoggedIn;
            }
        }
        return $bHasCategories;
    }

    /**
     * Check if the customer is logged in
     *
     * @return bool
     */
    protected function isCustomerLoggedIn()
    {
        return Mage::helper('sitewards_b2bprofessional/customer')->isCustomerLoggedIn();
    }

    /**
     * Get the price blocks as defined in the xml
     *
     * @return string[]
     */
    protected function getPriceBlocks()
    {
        if ($this->aPriceBlockClassNames === null) {
            $this->aPriceBlockClassNames = Mage::getStoreConfig(self::CONFIG_EXTENSION_PRICE_BLOCKS);
        }
        return $this->aPriceBlockClassNames;
    }

    /**
     * Get the add-to-cart blocks' layout names
     *
     * @return string[]
     */
    protected function getAddToCartBlockLayoutNames()
    {
        return $this->aAddToCartBlockLayoutNames;
    }
}
