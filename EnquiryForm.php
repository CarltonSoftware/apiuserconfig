<?php

/**
 * Settings Config form object
 *
 * PHP Version 5.3
 *
 * @category  Forms
 * @package   AW
 * @author    Alex Wyett <alex@wyett.co.uk>
 * @copyright 2013 Alex Wyett
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://www.github.com/alexwyett
 */

/**
 * Settings Config form object.  Extends the generic form and provides a static helper
 * method to build the form object
 *
 * PHP Version 5.3
 * 
 * @category  Forms
 * @package   AW
 * @author    Alex Wyett <alex@wyett.co.uk>
 * @copyright 2013 Alex Wyett
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://www.github.com/alexwyett
 */
class EnquiryForm extends \aw\formfields\forms\StaticForm
{
    /**
     * Array of properties
     * 
     * @var \tabs\api\property\Property[]
     */
    protected $properties = array();
    
    /**
     * Brandcode
     * 
     * @var string
     */
    protected $brandcode = 'ZZ';
    
    /**
     * Set the properties
     * 
     * @param \tabs\api\property\Property[] $properties Array of properties
     * 
     * @return \EnquiryForm
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        
        return $this;
    }
    
    /**
     * Set the form brandcode
     * 
     * @param string $brandcode Brandcode
     * 
     * @return \EnquiryForm
     */
    public function setBrandcode($brandcode)
    {
        $this->brandcode = $brandcode;
        
        return $this;
    }
    
    /**
     * Array of property info that the form can use
     * 
     * @return array
     */
    public function getPropertiesAsArray()
    {
        $properties = array();
        foreach ($this->properties as $property) {
            $properties[(string) $property] = $property->getPropref();
        }
        return $properties;
    }
    
    /**
     * Build the form
     * 
     * @return \EnquiryForm
     */
    public function build()
    {
        // Fieldset
        $fs = \aw\formfields\fields\Fieldset::factory(
            'Property',
            array(
                'class' => 'property'
            )
        );
        
        // Add brandcode
        $fs->addChild(
            new aw\formfields\fields\HiddenInput(
                'brandcode', 
                array(
                    'value' => $this->brandcode
                )
            )
        );

        // Add key field
        $fs->addChild(
            self::getNewLabelAndSelect(
                'Property',
                $this->getPropertiesAsArray()
            )
        );
        
        $this->addChild($fs);
        
        // Fieldset
        $fs = \aw\formfields\fields\Fieldset::factory(
            'Date and Druation',
            array(
                'class' => 'date-and-duration'
            )
        );
        
        // Add date
        $fs->addChild(
            self::getNewLabelAndTextField(
                'From'
            )->getElementBy('getType', 'text')
                ->setName('from')
                ->setId('from')
                ->setAttribute('placeholder', 'yyyy-mm-dd')
                ->setRule('ValidDate', true)
                ->getParent()
        );
        
        // Add date
        $fs->addChild(
            self::getNewLabelAndTextField(
                'To'
            )->getElementBy('getType', 'text')
                ->setName('to')
                ->setId('to')
                ->setAttribute('placeholder', 'yyyy-mm-dd')
                ->setRule('ValidDate', true)
                ->getParent()
        );
        
        $this->addChild($fs);
        
        // Fieldset
        $fs = \aw\formfields\fields\Fieldset::factory(
            'Party makeup',
            array(
                'class' => 'party-size'
            )
        );
        
        // Add adults etc
        foreach (array('adults', 'children', 'infants', 'pets') as $item) {
            $fs->addChild(
                self::getNewLabelAndTextField(
                    ucfirst($item)
                )->getElementBy('getType', 'text')
                    ->setRule('ValidNumber', true)
                    ->setValue('0')
                    ->getParent()
            );
        }
        
        $this->addChild($fs);
        
        // Add submit button
        $this->addChild(
            new \aw\formfields\fields\SubmitButton(
                array(
                    'value' => 'Check Price'
                )
            )
        );
        
        return $this;
    }
}