<?php

require 'vendor/autoload.php';
require_once 'ApiUserForm.php';
require_once 'SettingsForm.php';
require_once 'EnquiryForm.php';
require_once 'BookingsFilter.php';
require_once 'SearchForm.php';
require_once 'UserExtension.php';
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
    new UserExtention()
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
        $tabsBooking = $booking->getTabsBooking();
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
            'tabsBooking' => (isset($tabsBooking) ? $tabsBooking : null),
            'bookingException' => $bookingException,
            'apiRoutes' => \tabs\api\client\ApiClient::getApi()->getRoutes()
        )
    );
});



$app->get(
    '/tabsbooking',
    function () use (
        $app,
        $info,
        $brandcode
    ) {

    $tabsBookingException = false;
    $tabsBooking = null;
    $customer = null;
    if (filter_input(INPUT_GET, 'bookingref')) {
        $bookingRef = filter_input(INPUT_GET, 'bookingref');
        try {
            $tabsBooking = \tabs\api\booking\TabsBooking::getBooking($bookingRef, $brandcode);
            $customer = $tabsBooking->getCustomer();
        } catch (Exception $ex) {
            $tabsBookingException = $ex->getMessage();
        }
    }

    $app->render(
        'tabsbooking.html',
        array(
            'info' => $info,
            'brandcode' => $brandcode,
            'tabsBooking' => $tabsBooking,
            'tabsBookingException' => $tabsBookingException,
            'customer' => $customer,
            'apiRoutes' => \tabs\api\client\ApiClient::getApi()->getRoutes()
        )
    );

});



// Define routes
$app->get(
    '/property', 
    function () use (
        $app, 
        $info, 
        $brandcode
    ) {
        
    $filters = (filter_input_array(INPUT_GET)) ? filter_input_array(INPUT_GET) : array();
    $formFilters = $filters;
    if (isset($filters['brandcode'])) {
        unset($filters['brandcode']);
    }
    
    $additionalParams = array();
    if (isset($filters['shortBreakCheck'])) {
        $additionalParams['shortBreakCheck'] = $filters['shortBreakCheck'];
        unset($filters['shortBreakCheck']);
    }
    if (isset($filters['shortBreakOnly'])) {
        $additionalParams['shortBreakOnly'] = $filters['shortBreakOnly'];
        unset($filters['shortBreakOnly']);
    }
    if (isset($filters['page'])) {
        $additionalParams['page'] = $filters['page'];
        unset($filters['page']);
    }
    if (isset($filters['pageSize'])) {
        $additionalParams['pageSize'] = $filters['pageSize'];
        unset($filters['pageSize']);
    }
    
    $areas = \tabs\api\utility\Utility::getAreas();
    $areas = array_flip($areas);
    SearchForm::$areas = array_merge(
        array(
            'Any' => ''
        ),
        $areas
    );
    
    $locations = \tabs\api\utility\Utility::getLocations();
    $locations = array_flip($locations);
    SearchForm::$locations = array_merge(
        array(
            'Any' => ''
        ),
        $locations
    );
    
    $form = SearchForm::factory(
        array(), 
        $formFilters,
        $brandcode
    );
    templateForm($form);
    
    try {
        $search = new \tabs\api\property\SearchHelper();
        $search->setFilters($filters);
        $search->setSearchId('1');
        if (count($additionalParams) > 0) {
            foreach ($additionalParams as $key => $val) {
                $accessor = 'set' . ucfirst($key);
                if (method_exists($search, $accessor)) {
                    $search->$accessor($val);
                } else {
                    $search->setAdditionalParam($key, $val);
                }
            }
        }
        $search->search();
        
        // Add in brandcode back for pagintation
        $search->addFilter('brandcode', $brandcode);
    
        // Render index view
        $app->render(
            'properties.html',
            array(
                'info' => $info,
                'brandcode' => $brandcode,
                'searchHelper' => $search,
                'searchForm' => $form,
                'apiRoutes' => \tabs\api\client\ApiClient::getApi()->getRoutes()
            )
        );
    } catch (Exception $ex) {
    
        // Render index view
        $app->render(
            'properties.html',
            array(
                'info' => $info,
                'brandcode' => $brandcode,
                'exception' => $ex,
                'searchForm' => $form
            )
        );
    }
});

// Define routes
$app->get(
    '/property/:propref', 
    function ($propref) use (
        $app, 
        $info, 
        $brandcode
    ) {
    
    try {
        
        $propbrandcode = $brandcode;
        if (stristr($propref, '_')) {
            list($propref, $propbrandcode) = explode('_', $propref, 2);
        }
        
        $property = \tabs\api\property\Property::getProperty($propref, $propbrandcode);
    
        $enquiry = null;
        $enquiryForm = new EnquiryForm(
            array(
                'id' => 'enquiryform',
                'class' => 'price-check'
            ),
            filter_input_array(INPUT_GET)
        );
        $enquiryForm->setBrandcode($brandcode)->buildDropdowns(
            $property->getAccommodates()
        )->getElementBy('getName', 'property')->setValue($property->getPropref());
        $enquiryForm->setAttribute('action', '')->getElementBy('getName', 'propBrandcode')->setValue($property->getBrandcode());
        $enquiryForm->mapValues();
        templateForm($enquiryForm);

        if (filter_input(INPUT_GET, 'property')) {
            $enquiryForm->validate();
            $json = array('status' => 'ok', 'message' => '', 'enquiry' => array());
            if ($enquiryForm->isValid()) {
                try {
                    $enquiry = \tabs\api\booking\Enquiry::create(
                        filter_input(INPUT_GET, 'property'),
                        filter_input(INPUT_GET, 'propBrandcode'),
                        strtotime(filter_input(INPUT_GET, 'from')),
                        strtotime(filter_input(INPUT_GET, 'to')),
                        (integer) filter_input(INPUT_GET, 'adults'),
                        (integer) filter_input(INPUT_GET, 'children'),
                        (integer) filter_input(INPUT_GET, 'infants'),
                        (integer) filter_input(INPUT_GET, 'pets')
                    );
                    
                    $json['enquiry'] = array(
                        'Basic Price' => $enquiry->getPricing()->getBasicPrice()
                    );
                    $json['enquiry']['Security Deposit'] = $enquiry->getPricing()->getSecurityDeposit();
                    foreach ($enquiry->getPricing()->getExtras() as $extra) {
                        $json['enquiry'][$extra->getDescription()] = $extra->getTotalPrice();
                    }
                    $json['enquiry']['Total Price'] = $enquiry->getPricing()->getTotalPrice();
                    
                } catch (Exception $ex) {
                    $json['status'] = 'error';
                    $json['message'] = $ex->getApiCode() . ': ' . $ex->getApiMessage();
                    $json['statuscode'] = $ex->getApiCode();
                }
            } else {
                $json['status'] = 'error';
                $json['message'] = 'There were some errors with the data you\'ve added.';
            }
            
            die(json_encode($json));
        }
        
        $calendars = array();
    
        for ($i = 1; $i <= 24; $i++) {
            $date = mktime(0, 0, 0, $i, 1, date('Y'));
            $calendars[] = $property->getCalendarWidget(
                $date, 
                array(
                    'start_day' => strtolower($property->getChangeOverDay()),
                    'sevenRows' => true,
                    'attributes' => sprintf(
                        'class="calendar" data-month="%s"',
                        date('Y-m', $date)
                    ),
                    'cal_cell_content' => '{content}',
                    'cal_cell_content_today' => '{content}',
                    'sevenRows' => true
                )
            );
        }
        
        // Get a date range price object array
        $drps = $property->getDateRangePrices(date('Y'));
        $ranges = '';
        if (count($drps) > 0) {
            foreach ($drps as $drp) {
                $ranges .= sprintf(
                    '<tr><td>%s</td><td>&pound;%s</td></tr>',
                    call_user_func($drp->getDateRangeString, 'd F Y'),
                    $drp->price
                );
            }
        }
        
        $availableBreaksUrl = str_replace(
            'calendar',
            'availablebreaks',
            $property->getCalendarUrl()
        ) . '?' . \tabs\api\client\ApiClient::getApi()->getHmacQuery();

        // Render index view
        $app->render(
            'property.html',
            array(
                'info' => $info,
                'brandcode' => $brandcode,
                'property' => $property,
                'owner' => $property->getOwner(),
                'calendars' => $calendars,
                'form' => $enquiryForm,
                'priceranges' => $ranges,
                'availableBreaksUrl' => $availableBreaksUrl,
                'apiRoutes' => \tabs\api\client\ApiClient::getApi()->getRoutes()
            )
        );
    } catch (Exception $ex) {
        // Render index view
        $app->render(
            'property.html',
            array(
                'info' => $info,
                'brandcode' => $brandcode,
                'exception' => $ex,
                'apiRoutes' => \tabs\api\client\ApiClient::getApi()->getRoutes()
            )
        );
    }
});

$app->get(
    '/customer/:cusref',
    function ($cusref) use (
        $app, 
        $info, 
        $brandcode
    ) {

    $exception = false;
    $customer = null;
    try {
        $customer = \tabs\api\core\Customer::create($cusref);
    } catch (Exception $ex) {
        $exception = $ex->getMessage();
    }

    $app->render(
        'customer.html',
        array(
            'info' => $info,
            'exception' => $exception,
            'brandcode' => $brandcode,
            'customer' => $customer,
            'bookings' => ($customer) ? $customer->getBookings() : array(),
            'apiRoutes' => \tabs\api\client\ApiClient::getApi()->getRoutes()
        )
    );

});

$app->get(
    '/property/:propref/tabsbookings',
    function ($propref) use (
        $app, 
        $info, 
        $brandcode
    ) {

    $exception = false;
    $property = null;
    try {
        $property = \tabs\api\property\Property::getProperty($propref, $brandcode);
    } catch (Exception $ex) {
        $exception = $ex->getMessage();
    }

    $app->render(
        'propertytabsbookings.html',
        array(
            'info' => $info,
            'brandcode' => $brandcode,
            'property' => $property,
            'exception' => $exception
        )
    );

});



// Define routes
$app->get(
    '/keycount/:key', 
    function ($key) use (
        $app, 
        $info, 
        $brandcode
    ) {
    
    try {
        
        $apiKey = \tabs\api\core\ApiUser::getUser($key);
        $counts = \tabs\api\utility\Utility::getRequestCount($key);
        $monthCounts = array();
        for ($i = 1; $i <= 12; $i++) {
            $monthCounts[$i] = 0;
            $year = date('Y');
            if (isset($counts->$year) 
                && isset($counts->$year->months) 
                && isset($counts->$year->months->$i)
                && isset($counts->$year->months->$i->total)
            ) {
                $monthCounts[$i] = $counts->$year->months->$i->total;
            }
        }

        // Render index view
        $app->render(
            'keycount.html',
            array(
                'info' => $info,
                'brandcode' => $brandcode,
                'count' => $monthCounts,
                'key' => $apiKey
            )
        );
    } catch (Exception $ex) {
        // Render index view
        $app->render(
            'property.html',
            array(
                'info' => $info,
                'brandcode' => $brandcode,
                'exception' => $ex
            )
        );
    }
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
    $booking = null;
    
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
                $booking = \tabs\api\booking\Booking::create(
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
                $exception = $ex->getApiCode() . ': ' . $ex->getApiMessage();
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
            'booking' => $booking
        )
    );
});

// Define routes
$app->get(
    '/status', 
    function () use (
        $app, 
        $info,
        $brandcode
    ) {
    
    $exception = null;
    $statuses = array();
    $statusesReq = \tabs\api\client\ApiClient::getApi()->get('/api/dbstatus');
    if ($statusesReq->status == '200') {
        if (isset($statusesReq->response->db->tabs) 
            && isset($statusesReq->response->db->tabs->tables)
        ) {
            foreach (get_object_vars($statusesReq->response->db->tabs->tables) as $table => $status) {
                $st = new stdClass();
                $st->table = $table;
                $st->rows = $status->rows;
                $st->data_length = $status->data_length;
                array_push($statuses, $st);
            }
        }
    } else {
        $exception = 'Unable to fetch db status';
    }
    
    // Render index view
    $app->render(
        'status.html',
        array(
            'info' => $info,
            'brandcode' => $brandcode,
            'statuses' => $statuses,
            'exception' => $exception
        )
    );
});

// Define routes
$app->get(
    '/users', 
    function () use (
        $app,
        $info,
        $brands,
        $brandcode
    ) {
        // Render index view
        $app->render(
            'users.html',
            array(
                'info' => $info,
                'brandcode' => $brandcode,
                'brands' => $brands
            )
        );
    }
);

// Define routes
$app->post(
    '/users', 
    function () use (
        $app
    ) {
        $users = array();
        try {
            $apiUsers = \tabs\api\core\ApiUser::getUsers();
            foreach ($apiUsers as $user) {
                $users[] = array(
                    'key' => $user->getKey(),
                    'secret' => $user->getSecret(),
                    'email' => $user->getEmail()
                );
            }
        } catch (Exception $e) {}
    
        die(
            json_encode(
                $users
            )
        );
    }
);

// Define routes
$app->get(
    '/billing', 
    function () use (
        $app,
        $info,
        $brands,
        $brandcode
    ) {
        $period = mktime(0, 0, 0, date('m'), 1, date('Y'));
        
        if (filter_input(INPUT_GET, 'period') && strtotime(filter_input(INPUT_GET, 'period'))) {
            $period = strtotime(filter_input(INPUT_GET, 'period'));
        }
        
        $periods = array();
        for ($i = 1; $i <= 12; $i++) {
            $periods[] = mktime(0, 0, 0, $i, 1, date('Y'));
        }
    
        // Render index view
        $app->render(
            'billing.html',
            array(
                'info' => $info,
                'brandcode' => $brandcode,
                'brands' => $brands,
                'period' => $period,
                'periods' => $periods
            )
        );
    }
);

// Define routes
$app->post(
    '/billing', 
    function () use (
        $app,
        $brandcode
    ) {
        $period = mktime(0, 0, 0, date('m'), 1, date('Y'));
        
        if (filter_input(INPUT_GET, 'period') && strtotime(filter_input(INPUT_GET, 'period'))) {
            $period = strtotime(filter_input(INPUT_GET, 'period'));
        }
        
        
        $freeKeys = array(
            'carltonsoftware'
        );
        $freeRequests = 0;
        $paidRequests = 0;
        $year = date('Y', $period);
        $month = date('n', $period);

        foreach (\tabs\api\core\ApiUser::getUsers() as $user) {
            try {
                $requests = \tabs\api\utility\Utility::getRequestCount($user->getKey());
                \tabs\api\utility\Utility::resetCache();
                
                if (in_array($user->getKey(), $freeKeys)) {                
                    $freeRequests += $requests->{$year}->months->{$month}->total;
                } else {
                    $paidRequests += $requests->{$year}->months->{$month}->total;
                }

                unset($requests);
            } catch (Exception $ex) {

            }
        }
    
        die(
            json_encode(
                array(
                    'brandcode' => $brandcode,
                    'paid' => $paidRequests,
                    'free' => $freeRequests,
                    'year' => $year,
                    'month' => $month
                )
            )
        );
    }
);

// Define routes
$app->get(
    '/propertynumbers', 
    function () use (
        $app,
        $info,
        $brands,
        $brandcode
    ) {
    
        asort($brands);
    
        // Render index view
        $app->render(
            'propertynumbers.html',
            array(
                'info' => $info,
                'brandcode' => $brandcode,
                'brands' => $brands
            )
        );
    }
);

// Define routes
$app->post(
    '/propertynumbers', 
    function () use (
        $app,
        $info
    ) {
        die(json_encode(array('total' => $info->getTotalNumberOfProperties())));
    }
);

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
    if ($form->getElementBy('getType', 'submit')) {
        $form->getElementBy('getType', 'submit')
        ->setClass('btn btn-primary btn-lg')
        ->setTemplate(
            '<div class="form-actions" style="margin-bottom: 10px;">
                <input type="{getType}"{implodeAttributes}>
            </div>'
        );
    }
    // Set the template for checkboxes
    $form->each('getType', 'checkbox', function ($ele) {
        if (strlen($ele->getName()) == 2) {
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
        } else {
            $ele->getParent()
                ->setTemplate(
                '<div class="checkbox">
                    <label{implodeAttributes}>
                        {getLabel}
                        {renderChildren}
                    </label>
                </div>'
            );
        }
    });
}