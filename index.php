<?php

require 'vendor/autoload.php';
require_once 'ApiUserForm.php';
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
        'id' => 'apiuserform'
    ),
    $app->request->post(),
    (empty($brands) ? array() : $brands)
);

// Adding a user
$app->post('/', function() use ($app, $form, $brandcode, $info) {

    $status = 'danger';
    $message = 'Unable to create user';
    $fields = array();
    
    if ($app->request->post('action') 
        && $app->request->post('action') == 'delete'
    ) {
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
    } else {
        $form->validate();           
        if ($form->isValid()) {
            try {
                createUser($app->request->post('key'), $app->request->post('email'), $info);
                $status = 'success';
                $message = 'User Created!';
            } catch (Exception $ex) {
                $status = 'danger';
                $message = $ex->getMessage();
            }
        } else {
            $fields = $form->getErrors();
        }
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

// Define routes
$app->get('/', function () use ($app, $info, $form, $brandcode) {

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
                    {getLabel}
                    {renderChildren}
                </label>
            </div>'
        );
    });

    $userException = false;
    $users = array();
    try {
        $users = \tabs\api\core\ApiUser::getUsers();
    } catch (Exception $e) {
        $userException = $e->getMessage();
    }

    // Render index view
    $app->render(
        'index.html',
        array(
            'info' => $info,
            'form' => $form,
            'brandcode' => $brandcode,
            'userException' => $userException,
            'users' => $users
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
 *
 * @return void
 */
function createUser($key, $email, $info)
{
    $user = new \tabs\api\core\ApiUser();
    $user->setKey($key);
    $user->setEmail($email);
    $user->create();
        
    mail(
        'alex@carltonsoftware.co.uk', 
        'New user created on the api',
        "New user created with key {$user->getKey()} and email {$user->getEmail()} created for api {$info->getApiRoot()}."
    );
    
    return $user;
}