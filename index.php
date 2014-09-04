<?php

require 'vendor/autoload.php';
require_once 'ApiUserForm.php';
require_once 'SettingsForm.php';
require_once 'EnquiryForm.php';
require_once 'BookingsFilter.php';
require_once 'config.php';

// Get api info
$info = \tabs\api\utility\Utility::getApiInformation();

$app = new \Slim\Slim(
    array(
        'view' => new \Slim\Views\Twig(),
        'templates.path' => 'templates'
    )
);

$posIndex = strpos($_SERVER['PHP_SELF'], '/index.php');
$baseUrl = substr($_SERVER['PHP_SELF'], 0, $posIndex);
$app->hook('slim.before', function() use ($app, $baseUrl) {
    $app->view->appendData(array('baseUrl' => $baseUrl));
});

$app->view->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
);

// Create userform
$form = ApiUserForm::factory(
    array(
        'class' => 'form',
        'method' => 'post',
        'id' => 'apiuserform',
        'action' => $baseUrl . '/adduser'
    ),
    $app->request->post(),
    (empty($brands) ? array() : $brands)
);

// Create Settings Form
$sForm = SettingsForm::factory(
    array(
        'class' => 'form',
        'method' => 'post',
        'id' => 'settingsform',
        'action' => $baseUrl . '/addsetting'
    ),
    $app->request->post(),
    $brandcode
);

// Add user submission
$app->post('/adduser', function() use ($app, $form, $brandcode, $info) {
    $status = 'danger';
    $message = 'Unable to create user';
    $fields = array();
    
    $form->validate();        
    
    if ($form->isValid()) {
        try {
            createUser(
                $app->request->post('key'),
                $app->request->post('email'),
                $info,
                $app->request->post('secret')
            );
            $status = 'success';
            $message = 'User Created!';
        } catch (Exception $ex) {
            $status = 'danger';
            $message = $ex->getMessage();
        }
    } else {
        $fields = $form->getErrors();
    }
    
    die(
        json_encode(
            array(
                'status' => $status, 
                'message' => $message, 
                'fields' => $fields,
                'brandcode' => $brandcode
            )
        )
    );
});


// Adding a user
$app->post(
    '/deleteuser', 
    function() use (
        $app, 
        $form,
        $brandcode
    ) {

    $status = 'danger';
    $message = 'Unable to create user';
    $fields = array();
    
    try {
        $user = \tabs\api\core\ApiUser::getUser($app->request->post('key'));

        if (!$user) {
            throw new Exception('User not found', 500);
        }

        if ($user->getKey() == tabs\api\client\ApiClient::getApi()->getApiKey()) {
            throw new Exception('Cant delete this user!', 500);
        }

        $user->delete();
        $status = 'success';
        $message = 'User Deleted!';
    } catch (Exception $ex) {
        $status = 'danger';
        $message = $ex->getMessage();
    }
    
    die(
        json_encode(
            array(
                'status' => $status, 
                'message' => $message, 
                'fields' => $fields,
                'brandcode' => $brandcode
            )
        )
    );
});


// Adding a user
$app->post(
    '/deletesetting', 
    function() use (
        $app, 
        $brandcode
    ) {

    $status = 'danger';
    $message = 'Unable to delete setting';
    $fields = array();
    
    try {
        $setting = \tabs\api\core\ApiSetting::getSetting(
            $app->request->post('key'),
            $app->request->post('brandcode')
        );

        if (!$setting) {
            throw new Exception('Setting not found', 500);
        }

        $setting->delete();
        $status = 'success';
        $message = 'Setting Deleted!';
    } catch (Exception $ex) {
        $status = 'danger';
        $message = $ex->getMessage();
    }
    
    die(
        json_encode(
            array(
                'status' => $status, 
                'message' => $message, 
                'fields' => $fields,
                'brandcode' => $brandcode
            )
        )
    );
});


// Adding a user
$app->post(
    '/addsetting', 
    function() use (
        $app, 
        $sForm,
        $brandcode
    ) {

    $status = 'danger';
    $message = 'Unable to create setting';
    $fields = array();
    
    $sForm->validate();
    
    if ($sForm->isValid()) {
        try {
            $setting = new \tabs\api\core\ApiSetting();
            $setting->setBrandcode(strtoupper($brandcode));
            $setting->setName($app->request->post('key'));
            $setting->setValue($app->request->post('value'));
            $setting->create();
            
            $status = 'success';
            $message = 'Setting Added!';
        } catch (Exception $ex) {
            $status = 'danger';
            $message = $ex->getMessage();
        }
    } else {
        $fields = $sForm->getErrors();
    }
    
    die(
        json_encode(
            array(
                'status' => $status, 
                'message' => $message, 
                'fields' => $fields,
                'brandcode' => $brandcode
            )
        )
    );
});


// Adding a user
$app->post(
    '/checksetting', 
    function() use (
        $app, 
        $brandcode
    ) {

    $status = 'danger';
    $message = 'Not Found';
    
    try {
        $setting = \tabs\api\core\ApiSetting::getSetting(
            $app->request->post('key'),
            strtoupper($brandcode)
        );
        
        $status = 'success';
        if (strtolower($app->request->post('key')) == 'hmac') {
            if ($setting->getValue() != 'true') {
                throw new Exception('Hmac is false');
            }
            $message = 'OK';
        } else {
            $message = $setting->getValue();
        }
    } catch (Exception $ex) {
        $message = 'FAIL';
    }
    
    die(
        json_encode(
            array(
                'status' => $status, 
                'message' => $message, 
                'brandcode' => $brandcode
            )
        )
    );
});

// Define routes
$app->get(
    '/', 
    function () use (
        $app, 
        $info, 
        $form, 
        $brandcode,
        $sForm
    ) {
    
    templateForm($form);
    templateForm($sForm);

    $userException = false;
    $users = array();
    try {
        $users = \tabs\api\core\ApiUser::getUsers();
    } catch (Exception $e) {
        $userException = $e->getMessage();
    }

    $settingException = false;
    $settings = array();
    try {
        $settings = \tabs\api\core\ApiSetting::getSettings();
    } catch (Exception $e) {
        $settingException = $e->getMessage();
    }

    // Render index view
    $app->render(
        'index.html',
        array(
            'info' => $info,
            'form' => $form,
            'sForm' => $sForm,
            'brandcode' => $brandcode,
            'userException' => $userException,
            'users' => $users,
            'settingException' => $settingException,
            'settings' => $settings
        )
    );
});

// Define routes
$app->get(
    '/bookings', 
    function () use (
        $app, 
        $info, 
        $form, 
        $brandcode,
        $sForm
    ) {
    
    templateForm($form);
    templateForm($sForm);
    $filterForm = BookingsFilter::factory(
        array(
            'class' => 'form-inline',
            'id' => 'bookingsfilterform',
        ),
        filter_input_array(INPUT_GET),
        $brandcode
    );
    templateForm($filterForm);
    
    $filters = array();
    foreach (\tabs\api\booking\BookingAdmin::getBookingFilters() as $filter) {
        $filters[$filter] = $app->request->get('filter-' . $filter, '');
    }
    
    $bookingException = false;
    $bookings = null;
    try {
        $bookings = \tabs\api\booking\BookingAdmin::factory(
            array_filter($filters),
            $app->request->get('page', 1),
            $app->request->get('pageSize', 20)
        );
    } catch (Exception $ex) {
        $bookingException = $ex->getMessage();
    }
    
    // Render index view
    $app->render(
        'bookings.html',
        array(
            'info' => $info,
            'form' => $form,
            'sForm' => $sForm,
            'brandcode' => $brandcode,
            'bookingsFilter' => $filterForm,
            'bookings' => $bookings,
            'bookingException' => $bookingException
        )
    );
});

// Define routes
$app->get(
    '/booking/:id', 
    function ($id) use (
        $app, 
        $info, 
        $form, 
        $brandcode,
        $sForm
    ) {
    
    $bookingException = false;
    $booking = null;
    try {
        $booking = \tabs\api\booking\Booking::createBookingFromId($id);
    } catch (Exception $ex) {
        $bookingException = $ex->getMessage();
    }
    
    // Render index view
    $app->render(
        'booking.html',
        array(
            'info' => $info,
            'form' => $form,
            'sForm' => $sForm,
            'brandcode' => $brandcode,
            'booking' => $booking,
            'bookingException' => $bookingException
        )
    );
});

// Define routes
$app->get(
    '/attributes', 
    function () use (
        $app, 
        $info,
        $brandcode
    ) {
    
    // Sort the attributes so that they are in alphabetical order
    $attributes = $info->getAttributes();
    sort($attributes);
    
    // Render index view
    $app->render(
        'attributes.html',
        array(
            'info' => $info,
            'brandcode' => $brandcode,
            'attributes' => $attributes
        )
    );
});

// Define routes
$app->get(
    '/pricecheck', 
    function () use (
        $app, 
        $info,
        $brandcode
    ) {
    
    // Get all property references
    $searchHelper = new tabs\api\property\SearchHelper();
    $searchHelper->setFields(array('id', 'propertyRef', 'name'));
    $searchHelper->setInitialParams(array('pageSize' => 9999, 'orderBy' => 'propname'));
    $searchHelper->search(1);
    $properties = $searchHelper->getProperties();
    $exception = null;
    $enquiry = null;
    
    $enquiryForm = new EnquiryForm(
        array(
            'id' => 'enquiryform',
            'class' => 'price-check'
        ),
        filter_input_array(INPUT_GET)
    );
    $enquiryForm->setProperties($properties)->setBrandcode($brandcode);
    
    $enquiryForm->build()->mapValues();
    
    if (filter_input(INPUT_GET, 'property')) {
        $enquiryForm->validate();
        if ($enquiryForm->isValid()) {
            try {
                $enquiry = \tabs\api\booking\Enquiry::create(
                    filter_input(INPUT_GET, 'property'),
                    filter_input(INPUT_GET, 'brandcode'),
                    strtotime(filter_input(INPUT_GET, 'from')),
                    strtotime(filter_input(INPUT_GET, 'to')),
                    (integer) filter_input(INPUT_GET, 'adults'),
                    (integer) filter_input(INPUT_GET, 'children'),
                    (integer) filter_input(INPUT_GET, 'infants'),
                    (integer) filter_input(INPUT_GET, 'pets')
                );
            } catch (Exception $ex) {
                $exception = $ex->getMessage();
            }
        } else {
            $exception = 'There were some errors with the data you\'ve added.';
        }
    }
    
    // Render index view
    $app->render(
        'pricecheck.html',
        array(
            'info' => $info,
            'brandcode' => $brandcode,
            'enquiryForm' => $enquiryForm->render(),
            'exception' => $exception,
            'enquiry' => $enquiry
        )
    );
});

// Run app
$app->run();

/**
 * Create an API User
 *
 * @param string $key
 * @param string $email
 * @param string $secret
 *
 * @return void
 */
function createUser($key, $email, $info, $secret = '')
{
    $user = new \tabs\api\core\ApiUser();
    $user->setKey($key);
    $user->setEmail($email);
    
    if (strlen($secret) > 0) {
        $user->setSecret($secret);
    }
    
    $user->create();
        
    mail(
        'alex@carltonsoftware.co.uk', 
        'New user created on the api',
        "New user created with key {$user->getKey()} and email {$user->getEmail()} created for api {$info->getApiRoot()}."
    );
    
    return $user;
}

/**
 * Bootstrap templating function
 * 
 * @param \aw\formfields\forms\Form &$form Form object to template
 * 
 * @return void
 */
function templateForm(&$form)
{
    // Set template to bootstrap
    $form->each('getType', 'label', function($ele) {
        $ele->setClass('control-label')
            ->setTemplate(
                '<div class="form-group">
                    <label{implodeAttributes}>{getLabel}</label>
                    <div class="">
                        {renderChildren}
                    </div>
                </div>'
            );
    });

    // Set template to bootstrap
    $form->each('getType', 'text', function($ele) {
        $ele->setClass('form-control');
    });

    // Set template to bootstrap
    $form->each('getType', 'select', function($ele) {
        $ele->setClass('form-control');
    });

    // Set the submit button template
    $form->getElementBy('getType', 'submit')
    ->setClass('btn btn-primary btn-lg')
    ->setTemplate(
        '<div class="form-actions" style="margin-bottom: 10px;">
            <input type="{getType}"{implodeAttributes}>
        </div>'
    );

    // Set the template for checkboxes
    $form->each('getType', 'checkbox', function ($ele) {
        $ele->getParent()
            ->setTemplate(
            '<div class="checkbox">
                <label{implodeAttributes}>
                    <a href="?brandcode={getFor}">
                        {getLabel}
                    </a>
                    {renderChildren}
                </label>
            </div>'
        );
    });
}