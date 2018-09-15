<?php
session_start();
//exit('ssdd');
error_reporting(-1);
date_default_timezone_set('UTC');

$basePath =  dirname(__DIR__).'/';
function server($name, $default = null)
{
    if (isset($_SERVER[$name])) return $_SERVER[$name];
    return $default;
}
function getScheme()
{
    return  'http';
}

function getHost()
{
    $request = $_SERVER;
    $host = (isset($request['HTTP_HOST'])) ? $request['HTTP_HOST'] : $request['SERVER_NAME'];

    //remove unwanted characters
    $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));
    //prevent Dos attack
    if ($host && '' !== preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $host)) {
        die();
    }

    return $host;
}

function getRoot()
{
    $base = getBase();

    return getScheme() . '://' . getHost() . $base;
}
function getBase()
{
    $filename = basename(server('SCRIPT_FILENAME'));
    if (basename(server('SCRIPT_NAME')) == $filename) {
        $baseUrl = server('SCRIPT_NAME');
    } elseif (basename(server('PHP_SELF')) == $filename) {
        $baseUrl = server('PHP_SELF');
    } elseif (basename(server('ORIG_SCRIPT_NAME')) == $filename) {
        $baseUrl = server('ORIG_SCRIPT_NAME');
    } else {
        $baseUrl = server('SCRIPT_NAME');
    }

    $baseUrl = str_replace('index.php', '', $baseUrl);

    return $baseUrl;
}


function url($slug = '') {
    return getRoot().$slug;
}
function input($index, $value = '') {
    $value = (isset($_GET[$index])) ? $_GET[$index] : $value;
    $value = (isset($_POST[$index])) ? $_POST[$index] : $value;
    return trim($value);
}
function get_timezones_list()
{
    return array(
        'EUROPE'=>DateTimeZone::listIdentifiers(DateTimeZone::EUROPE),
        'AMERICA'=>DateTimeZone::listIdentifiers(DateTimeZone::AMERICA),
        'INDIAN'=>DateTimeZone::listIdentifiers(DateTimeZone::INDIAN),
        'AUSTRALIA'=>DateTimeZone::listIdentifiers(DateTimeZone::AUSTRALIA),
        'ASIA'=>DateTimeZone::listIdentifiers(DateTimeZone::ASIA),
        'AFRICA'=>DateTimeZone::listIdentifiers(DateTimeZone::AFRICA),
        'ANTARCTICA'=>DateTimeZone::listIdentifiers(DateTimeZone::ANTARCTICA),
        'ARCTIC'=>DateTimeZone::listIdentifiers(DateTimeZone::ARCTIC),
        'ATLANTIC'=>DateTimeZone::listIdentifiers(DateTimeZone::ATLANTIC),
        'PACIFIC'=>DateTimeZone::listIdentifiers(DateTimeZone::PACIFIC),
        'UTC'=>DateTimeZone::listIdentifiers(DateTimeZone::UTC),
    );
}
$step = input('step', 1);

if ($step == 3) {
    if (!isset($_POST['permission_passed']) or !$_POST['permission_passed']) header('Location:'.url('?step=2'));
    if ($_POST and isset($_POST['dbhost'])) {
        include 'install.php';
    }
}

?>
<!DOCTYPE html>
    <html>
        <head>
            <title>Tinsta - Installation</title>
            <link href="https://fonts.googleapis.com/css?family=Poppins:400,600" rel="stylesheet">
            <link href="<?php echo url('bootstrap.min.css')?>" rel="stylesheet" type="text/css"/>
            <link href="<?php echo url('style.css')?>" rel="stylesheet" type="text/css"/>
        </head>
        <body>
            <div class="container">
                <?php if ($step == 1):?>
                    <h4>Tinsta Installation</h4>
                    <p>This wizard will work you through tinsta installation process , to continue press the continue button</p>
                    <p>
                        <a href="<?php echo url('?step=2')?>" class="btn btn-info">Continue</a>
                    </p>
                    <?php elseif($step == 2):$error = false;?>
                    <h4>Permissions and Requirements Check</h4>
                    <p>Confirm the follow requirements are met by your server and the folders has writable (0777) permissions</p>
                    <h5>Server Requirements</h5>

                    <ul class="list-group">
                        <li class="list-group-item justify-content-between">
                            PHP 5.5+
                            <?php if(phpversion() < "5.5"):$error= true;?>
                                <span class="badge badge-danger badge-pill"><?php echo phpversion()?></span>
                            <?php else:?>
                                <span class="badge badge-success badge-pill"><?php echo phpversion()?></span>
                            <?php endif?>
                        </li>

                        <li class="list-group-item justify-content-between">
                            Mcrypt Extension
                            <?php if(!extension_loaded('mcrypt')):$error= true;?>
                                <span class="badge badge-danger badge-pill">Not Enabled</span>
                            <?php else:?>
                                <span class="badge badge-success badge-pill">Enabled</span>
                            <?php endif?>
                        </li>
                        <li class="list-group-item justify-content-between">
                            MBString Extension
                            <?php if(!extension_loaded('mbstring')):$error= true;?>
                                <span class="badge badge-danger badge-pill">Not Enabled</span>
                            <?php else:?>
                                <span class="badge badge-success badge-pill">Enabled</span>
                            <?php endif?>
                        </li>
                        <li class="list-group-item justify-content-between">
                            GD Extension
                            <?php if(!extension_loaded('gd')):$error= true;?>
                                <span class="badge badge-danger badge-pill">Not Enabled</span>
                            <?php else:?>
                                <span class="badge badge-success badge-pill">Enabled</span>
                            <?php endif?>
                        </li>
                        <li class="list-group-item justify-content-between">
                            MYSQLi Extension
                            <?php if(!extension_loaded('mysqli')):$error=true?>
                                <span class="badge badge-danger badge-pill">Not Enabled</span>
                            <?php else:?>
                                <span class="badge badge-success badge-pill">Enabled</span>
                            <?php endif?>
                        </li>
                        <li class="list-group-item justify-content-between">
                            PDO Extension
                            <?php if(!extension_loaded('pdo')):$error=true?>
                                <span class="badge badge-danger badge-pill">Not Enabled</span>
                            <?php else:?>
                                <span class="badge badge-success badge-pill">Enabled</span>
                            <?php endif?>
                        </li>


                        <li class="list-group-item justify-content-between">
                            CURL Extension
                            <?php if(!extension_loaded('curl')):$error=true?>
                                <span class="badge badge-danger badge-pill">Not Enabled</span>
                            <?php else:?>
                                <span class="badge badge-success badge-pill">Enabled</span>
                            <?php endif?>
                        </li>

                        <li class="list-group-item justify-content-between">
                            allow_url_fopen
                            <?php $url_f_open = ini_get('allow_url_fopen'); if($url_f_open != "1" && $url_f_open != 'On'):$error=true?>
                                <span class="badge badge-danger badge-pill">Not Enabled</span>
                            <?php else:?>
                                <span class="badge badge-success badge-pill">Enabled</span>
                            <?php endif?>
                        </li>
                    </ul>
                    <h5>Files / Folder Permissions</h5>

                    <ul class="list-group">
                        <li class="list-group-item justify-content-between">
                            files/
                            <?php if(!is_writable('../files/')):$error= true;?>
                                <span class="badge badge-danger badge-pill">Not Writable</span>
                            <?php else:?>
                                <span class="badge badge-success badge-pill">Writable</span>
                            <?php endif?>
                        </li>

                        <li class="list-group-item justify-content-between">
                            /config.php
                            <?php if(!is_writable('../config.php')):$error= true;?>
                                <span class="badge badge-danger badge-pill">Not Writable</span>
                            <?php else:?>
                                <span class="badge badge-success badge-pill">Writable</span>
                            <?php endif?>
                        </li>
                    </ul>

                    <?php if(!$error):?>
                            <form action="<?php echo url('?step=3')?>" method="post">
                                <input type="hidden" name="permission_passed" value="true"/>
                                <button  class="btn btn-info">Continue</button>
                            </form>
                        <?php else:?>
                            <a href="#" class="btn btn-default" disabled>Fix errors above to continue</a>
                        <?php endif?>

                    <?php elseif($step == 3):?>
                        <form action="<?php echo url('?step=3')?>" method="post">
                            <input type="hidden" name="permission_passed" value="true"/>
                            <h4>Database Setup</h4>
                            <div class="alert alert-info">Provide your database details, you can get this details from your server cpanel</div>
                            <div class="form-group">
                                <label>Database Host</label>
                                <input required class="form-control" name="dbhost" type="text" value="<?php echo input('dbhost')?>"/>
                            </div>
                            <div class="form-group">
                                <label>Database Username</label>
                                <input required class="form-control" name="dbusername" value="<?php echo input('dbusername')?>" type="text"/>
                            </div>
                            <div class="form-group">
                                <label>Database Password</label>
                                <input  class="form-control" name="dbpassword" value="<?php echo input('dbpassword')?>" type="text"/>
                            </div>
                            <div class="form-group">
                                <label>Database Name</label>
                                <input required class="form-control" name="dbname" value="<?php echo input('dbname')?>" type="text"/>
                            </div>



                            <h4>Admin Login</h4>
                            <p>Create your base admin account</p>
                            <div class="form-group">
                                <label>Full Name</label>
                                <input required class="form-control" name="fullname" value="<?php echo input('fullname')?>" type="text"/>
                            </div>
                            <div class="form-group">
                                <label>Username (<strong>Note:</strong> Please don't you use 'admin')</label>
                                <input required class="form-control" name="username" value="<?php echo input('username')?>" type="text"/>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input required class="form-control" name="email" value="<?php echo input('email')?>" type="text"/>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Password</label>
                                        <input required class="form-control" name="password" value="<?php echo input('password')?>" type="text"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Confirm Password</label>
                                        <input required class="form-control" name="confirm_password" value="<?php echo input('confirm_password')?>" type="text"/>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-danger"><strong>Note:</strong> This might take longtime before it finish, please be patience</div>
                            <button  class="btn btn-info" style="margin: 30px 0">Install & Finish</button>
                        </form>RG93bmxvYWRlZCBmcm9tIENPREVMSVNULkND

                <?php endif?>
            </div>
        </body>
    </html>



