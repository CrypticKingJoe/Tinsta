<?php

$dbhost = input('dbhost');
$dbusername = input('dbusername');
$dbname = input('dbname');
$dbprefix = input('dbprefix');
$dbpassword = input('dbpassword');
$fullname = input('fullname');
$username = input('username');
$email = input('email');
$password = input('password');
$confirmPassword = input('confirm_password');
$error = false;
$errorType = '';

function hash_make($content)
{
    require_once "../includes/libraries/password.php";
    return password_hash($content, PASSWORD_BCRYPT, array('cost' => 10));
}

class InstallDatabase {
    private static $instance;

    private $host;
    private $dbName;
    private $username;
    private $password;

    public $db;
    private $driver;

    private $dbPrefix;

    public function __construct($host,$dbName,$dbUsername,$dbPassword,$dbPrefix) {
        $this->host = $host;
        $this->dbName = $dbName;
        $this->username = $dbUsername;
        $this->password = $dbPassword;
        $this->dbPrefix = $dbPrefix;
        $this->db = new \PDO("mysql:host={$this->host};dbname={$this->dbName}", $this->username, $this->password);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function query($query) {
        $args = func_get_args();
        array_shift($args);
        if (isset($args[0]) and is_array($args[0])) {
            $args = $args[0];
        }
        $response = $this->db->prepare($query);
        $response->execute($args);

        return $response;
    }

    public function lastInsertId(){
        return $this->db->lastInsertId();
    }

    public function prepare($query) {

        $args = func_get_args();

        $response = $this->db->prepare($query);
        return $response;
    }

}

//test for
try {
    $db = new InstallDatabase($dbhost,$dbname,$dbusername,$dbpassword,'');
   //$d->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\Exception $e) {
    ///exit($e->getMessage());
    $error = true;
    $errorType = 'db';
   // exit('am her');
}

if (!$error) {
    if ($password != $confirmPassword) {
        $error = true;
        $errorType = 'password';
    } else {
        $configHolderContent = file_get_contents('../config-holder.php');
        $configHolderContent = str_replace(array('{host}','{username}','{name}','{password}','{installed}'), array(
            $dbhost,$dbusername,$dbname,$dbpassword,1
        ), $configHolderContent);

        file_put_contents('../config.php', $configHolderContent);


        $sqlContent = file_get_contents('sql.txt');
        $db->query($sqlContent);

        //add admin
        $password = hash_make($password);


        $time = time();

        $db->query("INSERT INTO members (username,email,password,full_name,role,date_created)
        VALUES('$username','$email','$password','$fullname','1','$time')");

        $homeUrl = url();
        $homeUrl = str_replace('install/', '', $homeUrl);
        header("location:".$homeUrl);

    }
}

//header("location:".url('?step'))