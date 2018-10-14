<?php

/**
 * Button block
 */
class ET_Productfeed_Block_Adminhtml_System_Config_Form_Productfeed_Fieldset_Button extends Mage_Core_Block_Template
{
    /**
     * Default button template
     */
    const DEFAULT_BUTTON_TEMPLATE = "productfeed/fieldset/button.phtml";

    /**
     * This is constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate(self::DEFAULT_BUTTON_TEMPLATE);
    }
}