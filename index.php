<?php

// Include libraries and objects
require_once 'vendor/autoload.php';
require_once 'ApiUserForm.php';
require_once 'config.php';

// Create userform
$form = ApiUserForm::factory(
    array(
        'class' => 'form',
        'method' => 'post'
    ),
    $formArray
);

// Perform delete
if (isset($formArray['action'])
    && isset($formArray['key'])
) {
    switch ($formArray['action']) {
    case 'delete':
        try {
            $user = \tabs\api\core\ApiUser::getUser($formArray['key']);
            
            if (!$user) {
                throw new Exception('User not found', 500);
            }
            
            if ($user->getKey() == tabs\api\client\ApiClient::getApi()->getApiKey()) {
                throw new Exception('Cant delete this user!', 500);
            }
            
            $user->delete();
            die(json_encode(array('status' => 'ok')));
        } catch (Exception $ex) {
            die(
                json_encode(
                    array(
                        'status' => 'error', 
                        'message' => $ex->getMessage()
                    )
                )
            );
        }
        break;
    case 'add': 
        
        break;
    }
}

// Get api info
$info = \tabs\api\utility\Utility::getApiInformation();

// Set template to bootstrap
$form->each('getType', 'label', function($ele) {
    $ele->setClass('control-label')
        ->setTemplate(
            '<div class="form-group">
                <label{implodeAttributes}>{getLabel}</label>
                <div class="">
                    {renderChildren}
                </div>
                <!--{error}-->
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
    '<div class="form-actions">
        <input type="{getType}"{implodeAttributes}>
    </div>'
);

// Set the validation call back
$form->setCallback(
    function($form, $ele, $e) {
        $ele->getParent()->setTemplate(
            str_replace(
                '<!--{error}-->', 
                sprintf(
                    '<span class="help-block">%s</span>',
                    $e->getMessage()
                ),
                $ele->getParent()->getTemplate()
            )
        );
        $ele->getParent()->setTemplate(
            str_replace(
                'form-group', 
                'form-group has-error', 
                $ele->getParent()->getTemplate()
            )
        );
    }
);

$result = '';
if (count($formArray) > 0) {
    $form->validate();                        
    if ($form->isValid()) {
        try {
            $user = new \tabs\api\core\ApiUser();
            $user->setKey($formArray['key']);
            $user->setEmail($formArray['email']);
            $user->create();
            $result = sprintf(
                '<div class="alert alert-success">User created!</div>'
            );
        } catch (Exception $ex) {
            $result = sprintf(
                '<div class="alert alert-danger">%s</div>',
                $ex->getMessage()
            );
        }
    }
}

?>
<!DOCTYPE HTML>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <title>API User Config</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="page-header">
                <h1>Api Config Form</h1>
            </div>
            
            <h2>Existing Users</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Secret</th>
                        <th>Roles</th>
                        <th>Delete?</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        try {
                            $users = \tabs\api\core\ApiUser::getUsers();
                            foreach ($users as $user) {
                                echo sprintf(
                                    '<tr>'
                                        . '<td>%s</td>'
                                        . '<td>%s</td>'
                                        . '<td>%s</td>'
                                        . '<td>%s</td>'
                                        . '<td><a href="#" data-key="%s" data-action="delete" class="btn btn-danger btn-sm btn-delete">Delete</a></td>'
                                    . '</tr>',
                                    $user->getKey(),
                                    $user->getEmail(),
                                    $user->getSecret(),
                                    implode(',', $user->getRoles()),
                                    $user->getKey()
                                );
                            }
                        } catch (Exception $ex) {
                            echo sprintf(
                                '<tr><td colspan="5">%s</td></tr>',
                                $ex->getMessage()
                            );
                        }
                    ?>
                </tbody>
            </table>
            <?php
                echo $result;
            ?>
        </div>
        <div class="row">
            <div class="well">
                <?php
                   echo $form;
                ?>
            </div>
        </div>
    </div>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <script src="assets/js/bootbox.min.js"></script>
    <script type="text/javascript">
        
        // Delete functionality
        jQuery(document).ready(function() {
            jQuery('.btn-danger').click(function() {
                $btn = jQuery(this);
                $tr = $btn.parent().parent();
                bootbox.confirm("Are you sure?", function(result) {
                    if (result === true) {
                        console.log();
                        jQuery.postJSON('', $btn.data(), function(res) {
                            if (res.status === 'ok') {
                                $tr.remove();
                            } else {
                                bootbox.alert(res.message);
                            }
                        });
                    }
                });
            });
        });
        
        
        /**
         * Shortcut to post json data to a url
         * @param url A string containing the URL to which the request is sent. (put any get parameters into the url)
         * @param data A map or string that is sent to be posted to the server with the request.
         * @param callback A callback function that is executed if the request succeeds.
         */
        jQuery.postJSON = function(url, data, callback) {
            jQuery.post(url, data, callback, "json");
        }
    </script>
</body>
</html>
