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
class SettingsForm extends \aw\formfields\forms\StaticForm
{
    /**
     * Constructor
     * 
     * @param array  $attributes Form attributes
     * @param array  $formValues Form Values
     * @param string $brandcode  Brandcode
     * 
     * @return void
     */
    public static function factory(
        $attributes = array(),
        $formValues = array(),
        $brandcode = 'ZZ'
    ) {
        // New form object
        $form = new \aw\formfields\forms\Form($attributes, $formValues);        
        
        // Fieldset
        $fs = \aw\formfields\fields\Fieldset::factory(
            'Add a new Setting',
            array(
                'class' => 'setting-details'
            )
        );

        // Add key field
        $fs->addChild(
            self::getNewLabelAndTextField(
                'Setting Name'
            )->getElementBy('getType', 'text')
                ->setName('key')
                ->setId('key')
                ->setAttribute('placeholder', 'Alpha/Numeric characters only')
                ->setRule('ValidSlug', true)
                ->getParent()
        );

        // Add value field
        $fs->addChild(
            self::getNewLabelAndTextField(
                'Value'
            )->getElementBy('getType', 'text')
                ->setRule('ValidString', true)
                ->getParent()
        );
        
        // Add brandcode
        $fs->addChild(
            new aw\formfields\fields\HiddenInput(
                'brandcode', 
                array(
                    'value' => $brandcode
                )
            )
        );
        
        // Add action
        $fs->addChild(
            new aw\formfields\fields\HiddenInput(
                'action', 
                array(
                    'value' => 'add-setting'
                )
            )
        );
        
        // Add fieldset to form
        $form->addChild($fs);
        
        // Add submit button
        $form->addChild(
            new \aw\formfields\fields\SubmitButton(
                array(
                    'value' => 'Add Setting',
                    'id' => 'formsubmitsetting'
                )
            )
        );
        
        return $form->mapValues();
    }
}