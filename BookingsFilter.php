<?php

/**
 * Bookings filter form object
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
 * Bookings filter form object
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
class BookingsFilter extends \aw\formfields\forms\StaticForm
{
    /**
     * Constructor
     * 
     * @param array  $attributes Form attributes
     * @param array  $formValues Form Values
     * @param string $brandcode  Brand Info
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
            'Bookings list',
            array(
                'class' => 'filter-bookings'
            )
        );

        // Add fromdate field
        $fs->addChild(
            self::getNewLabelAndTextField(
                'Date booked'
            )->getElementBy('getType', 'text')
                ->setName('filter-createdate')
                ->setId('filter-createdate')
                ->getParent()
        );

        // Add confirmed field
        $fs->addChild(
            self::getNewLabelAndSelect(
                'Confirmed?',
                array(
                    'Either' => '',
                    'Yes' => '1',
                    'No' => 'false'
                )
            )->getElementBy('getType', 'select')
                ->setName('filter-status')
                ->setId('filter-status')
                ->getParent()
        );

        // Add pageSize field
        $fs->addChild(
            self::getNewLabelAndSelect(
                'Amount to display?',
                array(
                    '20' => '20',
                    '50' => '50',
                    '100' => '100'
                )
            )->getElementBy('getType', 'select')
                ->setName('pageSize')
                ->setId('pageSize')
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
                    'value' => 'filter-bookings'
                )
            )
        );
        
        // Add fieldset to form
        $form->addChild($fs);
        
        // Add submit button
        $form->addChild(
            new \aw\formfields\fields\SubmitButton(
                array(
                    'value' => 'Filter',
                    'id' => 'formsubmitfilterbookings'
                )
            )
        );
        
        return $form->mapValues();
    }
}