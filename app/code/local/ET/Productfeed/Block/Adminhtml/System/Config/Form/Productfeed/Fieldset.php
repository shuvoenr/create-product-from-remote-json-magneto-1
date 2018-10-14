<?php

class ET_Productfeed_Block_Adminhtml_System_Config_Form_Productfeed_Fieldset
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Render fieldset html
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $this->_getHeaderHtml($element);
        foreach ($element->getElements() as $field) {
            $html .= $field->toHtml();
        }
        $html .= $this->getLayout()
            ->createBlock('et_productfeed/adminhtml_system_config_form_productfeed_fieldset_button')
            ->setTitle($this->__('Load Feed'))
            ->toHtml();
        $html .= $this->_getFooterHtml($element);
        return $html;
    }
}