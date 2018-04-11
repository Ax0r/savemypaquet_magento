<?php

/**
 * @category    Ecomtech
 * @package     Ecomtech_Savemypaquet
 * @author      Savemypaquet ( http://www.savemypaquet.com)
 * @developer   Ahmed MAHI <ahmed@mahi.ma> (http://ahmedmahi.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ecomtech_Savemypaquet_Block_Adminhtml_Zones_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId("zonesGrid");
        $this->setDefaultSort("id_zone");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel("savemypaquet/zones")->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    protected function _prepareColumns()
    {
        $this->addColumn("id_zone", array(
            "header" => Mage::helper("savemypaquet")->__("ID"),
            "align"  => "right",
            "width"  => "50px",
            "type"   => "number",
            "index"  => "id_zone",
        ));

        $this->addColumn("nom", array(
            "header" => Mage::helper("savemypaquet")->__("Nom"),
            "index"  => "nom",
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl("*/*/edit", array("id" => $row->getId()));
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id_zone');
        $this->getMassactionBlock()->setFormFieldName('id_zones');
        $this->getMassactionBlock()->setUseSelectAll(true);
        $this->getMassactionBlock()->addItem('remove_zones', array(
            'label'   => Mage::helper('savemypaquet')->__('Remove Zones'),
            'url'     => $this->getUrl('*/adminhtml_zones/massRemove'),
            'confirm' => Mage::helper('savemypaquet')->__('Are you sure?'),
        ));
        return $this;
    }
}
