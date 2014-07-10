<?php

/**
 * Api User Config form object
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
 * Api User Config form object.  Extends the generic form and provides a static helper
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
class ApiUserForm extends \aw\formfields\forms\StaticForm
{
    /**
     * Constructor
     * 
     * @param array $attributes Form attributes
     * @param array $formValues Form Values
     * @param array $brands     Brands
     * 
     * @return void
     */
    public static function factory(
        $attributes = array(),
        $formValues = array(),
        $brands = array()
    ) {
        // New form object
        $form = new \aw\formfields\forms\Form($attributes, $formValues);        
        
        // Fieldset
        $fs = \aw\formfields\fields\Fieldset::factory(
            'Create an Api User',
            array(
                'class' => 'user-details'
            )
        );

        // Add key field
        $fs->addChild(
            self::getNewLabelAndTextField(
                'User Name'
            )->getElementBy('getType', 'text')
                ->setName('key')
                ->setId('key')
                ->setAttribute('placeholder', 'Alpha/Numeric characters only')
                ->setRule('ValidSlug', true)
                ->getParent()
        );

        // Add email field
        $fs->addChild(
            self::getNewLabelAndTextField(
                'Email Address'
            )->getElementBy('getType', 'text')
                ->setName('email')
                ->setId('email')
                ->setRule('ValidEmail', true)
                ->getParent()
        );

        // Add secret field
        $fs->addChild(
            self::getNewLabelAndTextField(
                'Secret (optional)'
            )->getElementBy('getType', 'text')
                ->setName('secret')
                ->setId('secret')
                ->getParent()
        );
        
        // Add fieldset to form
        $form->addChild($fs);
        
        // Add submit button
        $form->addChild(
            new \aw\formfields\fields\SubmitButton(
                array(
                    'value' => 'Create User',
                    'id' => 'formsubmit'
                )
            )
        );
        
        // Add brand checkboxes for additional roleouts
        if (count($brands) > 0) {
            // Fieldset
            $fs = \aw\formfields\fields\Fieldset::factory(
                'Add to additional brands?',
                array(
                    'class' => 'additional-brands'
                )
            );
            
            foreach ($brands as $brandCode => $brandName) {
                $fs->addChild(
                    self::getNewLabelAndCheckboxField(
                        $brandCode
                    )->setLabel($brandName)
                );
            }
            
            $form->addChild($fs);
        }
        
        return $form->mapValues();
    }
}