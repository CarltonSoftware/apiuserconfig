<?php

/**
 * Search Config form object
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
 * Search Config form object.  Extends the generic form and provides a static helper
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
class SearchForm extends \aw\formfields\forms\StaticForm
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
            'Search',
            array(
                'class' => 'search-form'
            )
        );

        // Add text fields
        $textfields = array(
            'Reference' => 'reference',
            'Name' => 'name',
            'Star rating' => 'rating',
            'Sleeps' => 'accommodates',
            'Bedrooms' => 'bedrooms',
            'From' => 'fromDate'
        );
        foreach ($textfields as $label => $name) {
            $fs->addChild(
                self::getNewLabelAndTextField(
                    $label
                )->getElementBy('getType', 'text')
                    ->setName($name)
                    ->setId($name)
                    ->setAttribute('maxlength', 15)
                    ->getParent()
                    ->setLabel($label)
            );
        }
        
        $fs->getElementBy('getId', 'fromDate')->setAttribute(
            'placeholder', 'In YYYY-mm-dd format');
        
        $fs->addChild(
            self::getNewLabelAndSelect(
                'Plus Minus days',
                array(
                    'N/A' => '',
                    'Zero' => '0',
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                )
            )->getElementBy('getType', 'select')
                ->setName('plusMinus')
                ->setId('plusMinus')
                ->getParent()
        );
        
        // Add bool fields
        $bools = array(
            'On Special Offer' => 'specialOffer',
            'Pet Friendly' => 'pets',
            'Promoted' => 'promote',
            'Accepts Short Breaks' => 'sbtemplate'
        );
        
        foreach ($bools as $label => $key) {
            $fs->addChild(
                self::getNewLabelAndCheckboxField(
                    $label
                )->getElementBy('getType', 'checkbox')
                    ->setName($key)
                    ->setValue('true')
                    ->getParent()
            );
        }
        
        // Add fieldset to form
        $form->addChild($fs);
        
        // Add brandcode
        $form->addChild(
            new \aw\formfields\fields\HiddenInput(
                'brandcode'
            )
        );
        
        // Add submit button
        $form->addChild(
            new \aw\formfields\fields\SubmitButton(
                array(
                    'value' => 'Search',
                    'id' => 'submit'
                )
            )
        );
        
        return $form->mapValues();
    }
}