<?php

require 'vendor/autoload.php';
require_once 'ApiUserForm.php';
require_once 'SettingsForm.php';
require_once 'config.php';

// Get api info
$info = \tabs\api\utility\Utility::getApiInformation();

$app = new \Slim\Slim(
    array(
        'view' => new \Slim\Views\Twig(),
        'templates.path' => 'templates'
    )
);

$app->view->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
);

// Create userform
$form = ApiUserForm::factory(
    array(
        'class' => 'form',
        'method' => 'post',
        'id' => 'apiuserform',
        'action' => 'index.php/adduser'
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
        'action' => 'index.php/addsetting'
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