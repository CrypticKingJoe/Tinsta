<?php
class Application {
    public $version = '1.0';
    public static  $instance;
    private $config = array();


    public $authId = null;
    public $authUser = null;

    public function __construct() {

    }

    public static  function getInstance() {
        if (static::$instance != null) return static::$instance;
        static::$instance = new Application();
        return static::$instance;
    }

    public function run() {
        $config = include(path()."config.php");
        $this->config = array_merge($this->config, $config);
        $this->config['base_url'] = getRoot();
        $this->config['cookie_path'] = getBase();
        if ($this->config['https'] and !isSecure()) {
            redirect(url());
        } else {
            if (isSecure() and !$this->config['https']) {
                redirect(url());
            }
        }
        Translation::getInstance()->init();
        View::init();
        $this->loadModels();
        include_once path('includes/routes.php');
        //delete stories
        try {
            delete_old_stories();
        } catch(Exception $e) {}
        Router::run();
    }

    private function loadModels() {
        include path('includes/models/admin.php');
        include path('includes/models/user.php');
        include path('includes/models/post.php');

        load_admin_settings();
    }

    public  function getConfig($key, $default = null) {
        if (!isset($this->config[$key])) return $default;
        return $this->config[$key];
    }

    public  function setConfig($key, $value) {
        $this->config[$key] = $value;
        return true;
    }

    public function loadConfig($settings = array()) {
        foreach($settings as $key => $value) {
            $this->config[$key] = $value;
        }
    }
}

class Cache {
    static $instance;

    private $driver;

    public function __construct() {
        $this->driver = new FileCache();
    }

    public static function getInstance()
    {
        if(!isset(static::$instance) && static::$instance == null) return static::$instance = new Cache;
        return static::$instance;
    }


    /**
     * Store an item in the cache for a given number of minutes.
     * If minute is not supplied cache for 60 sec
     * @param  string  $key
     * @param  mixed   $value
     * @param  int     $seconds
     * @return void
     */
    public  function set($key,$val,$time = null)
    {
        $this->driver->set($key, $val, $time);
    }

    /**
     * Store an item in the cache forever.
     * @param  string  $key
     * @param  mixed   $value
     * @param  int     $seconds
     * @return void
     */
    public  function setForever($key,$val)
    {
        $this->driver->setForever($key, $val);
    }

    /**
     * Retrieve an item from the cache.
     * @param  string  $key
     * @return mixed
     */
    public  function get($key,$default = null)
    {
        return $this->driver->get($key, $default);
    }


    /**
     * @param string $key
     * @return bool
     */
    public function keyexists($key)
    {
        return $this->driver->keyexists($key);
    }

    /**
     * delete an item from the cache.
     * @param $filename
     * @internal param string $key
     * @return mixed
     */
    public  function forget($key)
    {
        return $this->driver->forget($key);
    }


    /**
     * Remove all item from the cache.
     * @return void
     */
    public function flush()
    {
        $this->driver->flush();
    }
}

class FileCache {
    public function storage($filepath = null, $dir =  false)
    {
        if ($filepath) {
            $parts = array_slice(str_split($filepath, 2), 0, 2);
            $key = join('/', $parts).'';
            if ($dir) {
                try{

                    @mkdir(path('files/cache/'.$key.'/'), 0777, true);
                }catch(Exception $e) {}
            }
            $filepath = $key . $filepath;

        }

        return path('files/cache/'.$filepath);
    }

    /**
     * Store an item in the cache for a given number of minutes.
     * If minute is not supplied cache for 60 sec
     * @param  string  $key
     * @param  mixed   $value
     * @param  int     $seconds
     * @return void
     */
    public  function set($key,$val,$time = null)
    {
        $this->newcachelibrary();
        $key = md5($key);
        /**
         * Check if key exist, then overide
         */
        if( !file_exists($this->storage($key)) ) $this->forget($key);

        /**
         * Check if time to cache is supplied
         * use default of 60secs
         */
        $time = isset($time) ? time() + $time :  time() + 60 ;

        $this->filepath = $this->storage( $key, true);


        $filehandle = file_put_contents($this->filepath, $time.serialize($val));
    }

    /**
     * Store an item in the cache forever.
     * @param  string  $key
     * @param  mixed   $value
     * @param  int     $seconds
     * @return void
     */
    public  function setForever($key,$val)
    {
        $this->set($key,$val,9999999999);
    }

    /**
     * Retrieve an item from the cache.
     * @param  string  $key
     * @return mixed
     */
    public  function get($key,$default = null)
    {
        $key   = md5($key);

        /**
         * Check if available
         */
        $path = $this->storage($key);
        if( !file_exists($path) ) return $default;
        /**
         * Check if expire
         */
        $content = file_get_contents($this->storage($key));
        $time = substr( $content, 0, 11);

        if(time() > $time)
        {

            $this->forget($key);
            return $default;
        }

        $value = $content;
        return unserialize(str_replace($time,"", $value));
    }


    /**
     * @param string $key
     * @return bool
     */
    public function keyexists($key)
    {
        $key = md5($key);
        return file_exists($this->storage($key));
    }

    /**
     * delete an item from the cache.
     * @param $filename
     * @internal param string $key
     * @return mixed
     */
    public  function forget($key)
    {
        $key = md5($key);

        if( !file_exists($this->storage($key)) ) return NULL;

        unlink($this->storage($key));
    }


    /**
     * Remove all item from the cache.
     * @return void
     */
    public function flush()
    {
        if( !file_exists($this->storage()) && !is_dir($this->storage()) ) return NULL;

        $handle = opendir($this->storage());
        while(false !== ($file = readdir($handle)))
        {
            if($file !== "." && $file !== "..")
            {
                unlink($this->storage($file));
            }
        }

        rmdir( $this->storage() );
    }


    /**
     * create new cache
     * library if not exist
     * @param
     * @return void
     */
    private function newcachelibrary()
    {
        if(!file_exists( $this->storage() ))  mkdir( $this->storage() ,0777, true);
    }
}

class Hook {
    private $events = array();

    static $instance;

    public static function getInstance()
    {
        if (!static::$instance) static::$instance = new Hook();
        return static::$instance;
    }

    /**
     * @param $event
     * @param null $values
     * @param null $callback
     * @return mixed|null
     */
    public function attachOrFire($event,$values = NULL,$callback = NULL, $param = array())
    {
        if (!is_array($param)) $param = array($param);
        if($callback !== NULL)
        {
            if(!isset($this->events[$event])) $this->events[$event] = array();
            $this->events[$event][] = $callback;
        }
        else{
            $theValue = $values;
            $result = $values;
            if (isset($this->events[$event])) {
                foreach($this->events[$event] as $callbacks)
                {
                    $newParam = ($values) ? array_merge(array($theValue), $param) : $param;
                    $v  = call_user_func_array($callbacks,$newParam);
                    $theValue = ($values) ? $v : $theValue;
                    $result = ($v) ? $v : $result;
                }
            }
            return ($values) ? $theValue : $result;
        }
    }
}

class Translation {
    private $lang = "en";
    private $fallbackLang = "en";

    private $namespace = array();

    private static  $instance;

    private $phrases = array();
    private $defaultPhrases = array();

    public function lang() {
        return $this->lang;
    }
    public static function getInstance() {
        if (static::$instance) return static::$instance;
        static::$instance = new Translation();
        return static::$instance;
    }

    public function init() {
        $this->addNamespace("global", path("languages/"));
        $this->lang = config('default_language', 'en');
        if (isset($_SESSION['language'])) {
            $this->lang = $_SESSION['language'];

        }
    }

    public function addNamespace($name, $path) {
        $this->namespace[$name] = $path;
    }

    public function translate($name, $param = array(), $default = null) {

        if (!preg_match("#::#", $name)) $name = "global::$name";


        list($namespace, $slug) = explode("::", $name);
        $default = ($default) ? $default : $slug;
        if (!isset($this->namespace[$namespace])) return $default;

        $languagePath = $this->namespace[$namespace].$this->lang.'.php';
        $defaultPath = $this->namespace[$namespace].$this->fallbackLang.'.php';
        $this->defaultPhrases = include($defaultPath); //default language
        $phrases = (file_exists($languagePath)) ? include($languagePath) : include($defaultPath);
        $this->phrases[$namespace] = $phrases;

        if (isset($this->phrases[$namespace][$slug])) {
            return $this->result($this->phrases[$namespace][$slug], $param, $default);
        } elseif(isset($this->defaultPhrases[$slug])){
            return $this->result($this->defaultPhrases[$slug], $param, $default);
        }
        return $default;
    }

    public function result($result, $param = array(), $default = null) {
        if (!empty($param)) {
            foreach($param as $replace => $value) {
                $result = str_replace(":".$replace, $value, $result);
            }
        }
        return ($result) ? $result : $default;
    }
}

class Url {
    private static  $url = null;

    private static $segments = array();

    public static  function get($url = null) {
        if (!static::$url) {
            static::$url = static::getBase();
        }

        if (preg_match('#http|https#', $url)) {
            return $url;
        }

        return static::$url.static::cleanUrl($url);
    }



    public static function getBase() {
        return config("base_url");
    }

    public static function getUri() {
        $fullUrl = getFullUrl();
        $uri = str_replace(strtolower(static::getBase()), "", strtolower($fullUrl));
        if (!empty($uri)) static::$segments = explode('/', $uri);
        return $uri;
    }

    public static function segment($index, $default = false) {
        static::getUri();//
        if (isset(static::$segments[$index])) return static::$segments[$index];
        return $default;
    }

    public static function cleanUrl($url) {
        $url = str_replace("//", "/", $url);
        return $url;
    }
}

class Email {
    protected $driver = 'mail';
    protected $fromName = '';
    protected $fromAddress = '';
    protected $smtp_host = '';
    protected $smtp_username = '';
    protected $smtp_password = '';
    protected $smtp_port = '';
    protected $ssl = '';
    protected $queue = false;
    protected $charset = 'utf-8';
    protected static $instance;

    protected $localTemplates = array();
    protected $dbTemplates = array();
    public $mailer = null;

    //queue
    private $queueFromName = '';
    private $queueFromMail = '';
    private $queueSubject = '';
    private $queueMessage = '';
    private $queueAddress = array();

    public static function getInstance()
    {
        if (!static::$instance) static::$instance = new Email();
        return static::$instance;
    }

    public function __construct() {
        $this->init();
    }

    public function init() {
        $this->driver = config('email-driver', 'mail');
        $this->smtp_host = config('smtp-host');
        $this->smtp_username = config('smtp-username');
        $this->smtp_password = config('smtp-password');
        $this->smtp_port = config('smtp-port');
        $this->charset = config('email-charset', 'utf-8');
        $this->fromAddress = config('from_adddress', '');

        include_once path('includes/libraries/phpmailer/PHPMailerAutoload.php');
        try {
            $this->mailer = new \PHPMailer(true);
            if ($this->driver == 'smtp') {
                $this->mailer->isSMTP();
                $this->mailer->Host = $this->smtp_host;
                $this->mailer->Port = $this->smtp_port;
                $this->mailer->CharSet = $this->charset;
                $this->mailer->Encoding = "base64";
                $this->mailer->SMTPAutoTLS = false;
                if (!empty($this->smtp_username) and !empty($this->smtp_password)) {
                    $this->mailer->Username = $this->smtp_username;
                    $this->mailer->Password = $this->smtp_password;
                    $this->mailer->SMTPAuth = true;
                }
            }
            $this->mailer->setFrom($this->fromAddress, $this->fromName);
        } catch(\Exception $e) {}


    }



    public function setFrom($fromEmail, $fromName) {
        $this->queueFromName = $fromName;
        $this->queueFromMail = $fromEmail;
        try{
            $this->mailer->setFrom($fromEmail, $fromName);
        } catch(\Exception $e){}
    }

    public function addCC($address, $name = null) {
        try{
            $this->mailer->addCC($address, $name);
        } catch(\Exception $e){}
        return $this;
    }
    public function setAddress($address, $name = null)
    {
        $this->queueAddress[$name] = $address;
        try{
            $this->mailer->addAddress($address, $name);
        } catch(\Exception $e){}
        return $this;
    }

    public function setSubject($subject)
    {
        $this->mailer->Subject = $subject;
        $this->queueSubject = $subject;
        return $this;
    }

    public function setMessage($message)
    {
        $message = str_replace(array("\\n","\\r"), array('', ''), $message);
        $message = html_entity_decode($message, ENT_QUOTES);
        $this->queueMessage = $message;
        $this->mailer->msgHTML($message);
        return $this;
    }

    public function addAttachment($path)
    {
        $this->mailer->addAttachment($path);
        return $this;
    }

    public function send()
    {
        try {
            $this->mailer->send();
            return true;
        } catch(Exception $e) {
            return false;
        }
    }

}

class Database {
    private static $instance;

    private $host;
    private $dbName;
    private $username;
    private $password;

    private $db;
    private $driver;

    private $dbPrefix;

    public function __construct() {
        $this->init();
    }

    public function init() {
        $expected = array(
            'db_host' => '',
            'db_name' => '',
            'db_username' => '',
            'db_password' => '',
            'driver' => 'mysql',
            'port' => '',
        );
        $databaseDetails = include(path()."config.php");
        $databaseDetails = array_merge($expected, $databaseDetails);

        $this->host = $databaseDetails['db_host'];
        $this->dbName = $databaseDetails['db_name'];
        $this->username = $databaseDetails['db_username'];
        $this->password = $databaseDetails['db_password'];
        $this->driver = $databaseDetails['driver'];

        /**
         * Try make connection to the database
         */
        try {
            $this->db = new \PDO("{$this->driver}:host={$this->host};dbname={$this->dbName}", $this->username, $this->password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
    }

    public static function getInstance() {
        if (static::$instance) return static::$instance;
        static::$instance = new Database();
        return static::$instance;
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

class Route {
    public $id;
    public $uses;
    public $filter;
    public $permission;
    public $slug;
    public $method = "ANY";
    public $where = array();
    public $columns = '';
    public $param = array();

    public function __construct($id, $slug, $uses, $method = "", $filter = "", $permission = "") {
        $this->id = $id;
        $this->slug = $slug;
        $this->uses = $uses;
        $this->filter = $filter;
        $this->method = $method;
        $this->permission = $permission;
    }

    public function where($whereClause) {
        $this->where = $whereClause;
        return $this;
    }

    public function column($column) {
        $this->columns = $column;
    }


    public function getPattern()
    {
        if ($this->slug != "/" and substr($this->slug, -1) == '/') $this->slug = rtrim($this->slug, '/');
        if (empty($this->where)) return $this->slug;
        $slug = $this->slug;
        foreach($this->where as $replace => $p) {
            $slug = str_replace("{".$replace."}", $p, $slug);
        }
        return $slug;
    }

    /**
     * @param array $param
     * @return mixed
     */
    public function getLink($param = array())
    {
        $slug = ($this->slug == "/") ? "" : $this->slug;
        if (empty($param)) return Url::get($slug);

        foreach($param as $id => $value) {
            $slug = str_replace("{".$id."}", $value, $slug);
        }
        return Url::get($slug);
    }

    public function addFilter($filter) {
        $this->filter = $filter;
        return $this;
    }

    public function addPermission($permission) {
        $this->permission = $permission;
        return $this;
    }

    public function addParam($param = array()) {
        $this->param = $param;
        return $this;
    }
}

class Router {
    private static  $routes = array();

    private static  $filters = array();

    private static $currentRoute;
    /**
     * Method to add routes of any type
     * @param $slug
     * @param $param
     * @return Route
     */
    public static function add($slug, $param) {
        $expectedParam = array(
            'uses' => '',
            'filter' => '',
            'as' => $slug,
            'method' => 'ANY'
        );

        /**
         * @var $uses
         * @var $filter
         * @var $as
         * @var $method
         */
        extract(array_merge($expectedParam, $param));

        $route = new Route($as, $slug, $uses,$method, $filter);

        static::$routes[$as] = $route;
        return $route;
    }

    /**
     * Method to add routes of any type
     * @param $slug
     * @param $param
     * @return Route
     */
    public static function any($slug, $param) {
        return static::add($slug, array_merge($param, array('method' => 'ANY')));
    }

    /**
     * Method to add routes of get type
     * @param $slug
     * @param $param
     * @return Route
     */
    public static function get($slug, $param) {
        return static::add($slug, array_merge($param, array('method' => 'GET')));
    }

    /**
     * Method to add routes of post type
     * @param $slug
     * @param $param
     * @return Route
     */
    public static function post($slug, $param) {
        return static::add($slug, array_merge($param, array('method' => 'POST')));
    }

    /**
     * Method to find route
     * @param $id
     * @return bool
     */
    public static  function findRoute($id) {
        if (isset(static::$routes[$id])) return static::$routes[$id];
        return false;
    }

    public static function run() {
        $uri = Url::getUri();
        if (substr($uri, -1) == '/') $uri = rtrim($uri, '/');
        if (!$uri) $uri = "/";

        $content = "";
        $resultFound = false;

        foreach(static::$routes as $id => $route) {
            $slug = $route->slug;
            $slug = $route->getPattern();
            if (!$content and preg_match("!^".$slug."$!", $uri)) {
                $requestMethod = get_request_method();
                $thisMethod = strtoupper($route->method);

                if (($thisMethod == "ANY" or $requestMethod == $thisMethod) and static::passFilter($route)) {
                    $content = static::load($route);
                    if ($content) {
                        //$content = "";
                        $resultFound = true;
                        echo ($content == 'found') ? '' : $content;
                    }

                }

            }
        }
        if (!$resultFound) {
            exit(view('main::404'));

        }
    }

    /**
     * Method to add route filter
     * @param $name
     * @param $function
     */
    public static function addFilter($name, $function) {
        static::$filters[$name] = $function;
    }

    public static function passFilter($route) {
        $filter = $route->filter;
        $filters = explode("|", $filter);
        $passed = true;
        foreach($filters as $filter) {
            if (isset(static::$filters[$filter])) {
                $callableFunction = static::$filters[$filter];
                if (is_callable($callableFunction)) {
                    if (!call_user_func_array($callableFunction, array(app()))) {
                        $passed = false;
                    }
                }
            }
        }

        return $passed;
    }

    public static function load($route, $active = true, $filter = false, $permission = false) {

        if ($filter and !static::passFilter($route)) return false;

        if($active) static::$currentRoute = $route;
        $uses = $route->uses;
        $moduleController  =  $uses;
        list($controller, $method) = explode("@", $moduleController);

        $modulesPath = path("includes/controllers/");

        if (file_exists($modulesPath.$controller.".php") ) {
            require_once $modulesPath.$controller.".php";
        }

        if (!class_exists($controller)) return false;

        $controllerClass = new $controller();
        //if (!method_exists($controllerClass, $method)) return false;
        $controllerClass->before(); //before method to call before calling the actual route method
        if (method_exists($controllerClass, "get".ucfirst($method))) {
            $method = "get".ucfirst($method);
        } elseif (method_exists($controllerClass, "post".ucfirst($method))) {
            $method = "post".ucfirst($method);
        }
        $result = call_user_func_array(array($controllerClass, $method), $route->param);
        //$result = $controllerClass->$method(); //calling the route method
        $controllerClass->after();

        return ($result) ? $result : 'found';

    }

    public static function getCurrentRoute() {
        return static::$currentRoute;
    }
}

class Layout {
    private static  $instance;
    private  $pageTitle;
    private  $titleSeparator = "|";

    private $metaTags = array();

    private $headerContent = "";
    private $footerBeforeContent = "";
    private $footerAfterContent = "";

    public $pageType = 'frontend';
    private $mainLayout = "main::includes/layout";

    public $theme = "default";
    public $showHeader = true;

    public $activeMenu = "";


    public function __construct() {
        $this->theme = config('theme', 'default');
        $this->setTitle();
    }

    public static  function getInstance() {
        if (static::$instance) return static::$instance;
        static::$instance = new Layout();
        return static::$instance;
    }

    public function setMainLayout($layout) {
        $this->mainLayout = $layout;
        return $this;
    }

    public function render($content) {


        if (is_ajax()) {
            return json_encode(array(
                'title' => $this->pageTitle,
                'content' => $content,
                'active_menu' => $this->activeMenu
            ));
        }

        $content = view($this->mainLayout, array('content' => $content));

        $output = "";

        $output .= view("main::includes/header", array(
            'title' => $this->pageTitle,
            'header_content' => $this->headerContent,
            'active_menu' => $this->activeMenu
        ));
        $output .= $content;
        $output .= view("main::includes/footer", array('beforeContent' => $this->footerBeforeContent, 'afterContent' => $this->footerAfterContent));
        return $output;
    }

    public function setTitle($title = "") {
        $titleStr = config('site-title', 'Tinsta');
        if ($title) $titleStr .= " ".$this->titleSeparator." {$title}";
        $this->pageTitle = $titleStr;
        //exit($this->pageTitle);
        return $this;
    }

    public function addHeaderContent($content = "") {
        $this->headerContent .= $content;
        return $this;
    }

    public function addFooterBeforeContent($content = "") {
        $this->footerBeforeContent .= $content;
        return $this;
    }

    public function addFooterAfterContent($content = "") {
        $this->footerAfterContent .= $content;
        return $this;
    }
}

class Controller{
    public function __construct() {


        $this->layout = Layout::getInstance();
    }

    public function render($content) {
        return $this->layout->render($content);
    }

    public function before() {

    }

    public function after() {

    }

    public function setTitle($title) {
        Layout::getInstance()->setTitle($title);
    }
}

class View {
    private static  $namespaces = array();
    private static  $defaultTheme;
    public static  function init() {
        static::$defaultTheme = config('default-theme', 'default');

        static::addNamespace("main", path("includes/views/"));
        static::addNamespace("style", path("styles/".static::$defaultTheme.'/views/'));
    }

    public static function addNamespace($name, $path) {
        static::$namespaces[$name] = $path;
    }

    public static  function find($view, $param = array()) {
        $viewPath = static::getViewPath($view);
        //exit($viewPath.'dsd');


        if (!$viewPath) return false;
        ob_start();

        /**
         * make the parameters available to the views
         */
        $app = app();
        //$app->config = array();
        extract($param);

        if (file_exists($viewPath)) //trigger_error(Error::viewNotFound($viewPath));
            include $viewPath;
        $content = ob_get_clean();
        return $content;
    }


    public static function getViewPath($view) {
        list($namespace, $path) = explode("::", $view);
        if (!static::namespaceExists($namespace)) return false;

        $namespacePath = static::$namespaces[$namespace];
        $viewPath = $namespacePath.$path.'.phtml';
        $styleOverridePath = static::$namespaces['style'].static::stripOffBasePath($path).'.phtml';
        if (file_exists($styleOverridePath)) return $styleOverridePath;
        return $viewPath;
    }

    public static function namespaceExists($namespace) {
        return (isset(static::$namespaces[$namespace]));
    }

    public static function stripOffBasePath($path) {
        return str_replace(path(), "", $path);
    }
}

class Uploader {
    /**
     * Allow image type
     */
    private $imageTypes = array('png', 'jpg', 'gif', 'jpeg');
    private $imageSizes = array(75, 200,600, 920);

    /**
     * Allowed File types
     */
    private $fileTypes = array(
        'doc',
        'xml',
        'exe',
        'txt',
        'zip',
        'rar',
        'doc',
        'mp3',
        'jpg',
        'png',
        'css',
        'psd',
        'pdf',
        '3gp',
        'ppt',
        'pptx',
        'xls',
        'xlsx',
        'html',
        'docx',
        'fla',
        'avi',
        'mp4',
        'swf',
        'ico',
        'gif',
        'webm',
        'jpeg'
    );

    /**
     * Allowed video types
     */
    private $videoTypes = array('mp4');
    private $audioTypes = array('mp3');
    private $sourceFile;
    private $linkContent = '';
    public $source;
    public $sourceName;
    public $sourceSize;
    public $extension;
    public $destinationPath;
    public $destinationName;
    public $baseDir;

    private $dbType;
    private $dbTypeId;
    private $type;

    //max sizes
    private $maxFileSize = 10000000;
    private $maxImageSize = 10000000;
    private $maxVideoSize = 10000000;
    private $maxAudioSize = 10000000;

    //allow Animated gif
    private $animatedGif = true;

    private $error = false;
    private $errorMessage;
    public $result;
    public  $insertedId;
    public $allowCDN = true;
    /**
     * @param $source
     * @param string $type
     * @param mixed $validate
     */
    public function __construct($source, $type = "image", $validate = false, $fromFile = false, $isLink = false)
    {
        //$source = is_string($source) ? runHook('path.local', null, array($source)) : $source;
        $this->source = $source;
        $this->type = $type;
        $this->maxFileSize = config("max-file-upload", $this->maxFileSize);
        $this->maxVideoSize = config("max-video-upload", $this->maxVideoSize);
        $this->maxAudioSize = config("max-audio-upload", $this->maxAudioSize);
        $this->maxImageSize = config("max-image-size", $this->maxImageSize);
        $this->animatedGif = config("support-animated-image", $this->animatedGif);
        $this->imageTypes = explode(',', config('image-file-types', 'jpg,png,gif,jpeg'));
        $this->videoTypes = explode(',', config('video-file-types', 'mp4,mov,wmv,3gp,avi,flv,f4v,webm'));
        $this->audioTypes = explode(',', config('audio-file-types', 'mp3,aac,wma'));
        //$this->fileTypes = explode(',', config('files-file-types', 'doc,xml,exe,txt,zip,rar,mp3,jpg,png,css,psd,pdf,3gp,ppt,pptx,xls,xlsx,html,docx,fla,avi,mp4,swf,ico,gif,jpeg,webm'));

        if(!$fromFile) {
            if ($source and $this->source['size'] != 0) {
                $this->source = $source;
                $this->sourceFile = $this->source['tmp_name'];
                $this->sourceSize = $this->source['size'];
                $this->sourceName = $this->source['name'];
                $name = pathinfo($this->sourceName);
                if (isset($name['extension'])) $this->extension = strtolower($name['extension']);

                $this->confirmFile();

            } else {
                if (!$validate) {
                    $this->error = true;
                    $this->errorMessage = lang("failed-to-upload-file");
                } else {
                    $this->validate($validate);
                }
            }
        } else {
            $this->source = $this->sourceFile = $this->sourceName = $source;
            if (!$isLink) {
                $name = pathinfo($this->sourceName);
                if (isset($name['extension'])) $this->extension = strtolower($name['extension']);
            } else {

                $content = file_get_contents($this->source);

                if (!$content) {
                    $this->error = true;
                    $this->errorMessage = lang("failed-to-upload-file");
                } else {
                    $this->extension = get_file_extension($this->source);
                    $this->linkContent = $content;

                }

            }
        }

        //load our libraries
        if($this->animatedGif) require_once path("includes/libraries/gif_exg.php");

        //confirm the creation of uploads directory
        if (!is_dir(path('files/uploads/'))) {
            @mkdir(path('files/uploads/'), 0777, true);
            $file = @fopen(path('files/uploads/index.html'), 'x+');
            fclose($file);
        }

    }

    public function setFileTypes($types) {
        $this->fileTypes = $types;
        return $this;
    }

    public function noThumbnails() {
        $this->imageSizes = array(600, 920);
        return $this;
    }

    public function disableCDN() {
        $this->allowCDN = false;
    }

    public function enableCDN() {
        $this->allowCDN = true;
    }

    /**
     * Method to get the image width
     * @return null
     */
    function getWidth()
    {
        list($width, $height) = getimagesize($this->sourceFile);
        return ($width) ? $width : null;
    }

    /**
     * Method to get the image height
     * @return int
     */
    function getHeight()
    {
        list($width, $height) = getimagesize($this->sourceFile);
        return ($height) ? $height : null;
    }

    public function confirmFile()
    {
        switch($this->type) {
            case 'image':
                if (!in_array($this->extension, $this->imageTypes)){
                    $this->errorMessage = lang("upload-file-not-valid-image");
                    $this->error = true;
                }
                if ($this->sourceSize > $this->maxImageSize) {
                    $this->errorMessage = lang("upload-image-size-error", array('size' => format_bytes($this->maxImageSize)));
                    $this->error = true;
                }
                break;
            case 'video':
                if (!in_array($this->extension, $this->videoTypes)) {
                    $this->errorMessage = lang("upload-file-not-valid-video");
                    $this->error = true;
                }
                if ($this->sourceSize > $this->maxVideoSize) {
                    $this->errorMessage = lang("upload-video-size-error", array('size' => format_bytes($this->maxVideoSize)));
                    $this->error = true;
                }
                break;
            case 'audio':
                if (!in_array($this->extension, $this->audioTypes)) {
                    $this->errorMessage = lang("upload-file-not-valid-audio");
                    $this->error = true;
                }
                if ($this->sourceSize > $this->maxAudioSize) {
                    $this->errorMessage = lang("upload-audio-size-error", array('size' => format_bytes($this->maxAudioSize)));
                    $this->error = true;
                }
                break;
            case 'file':
                if (!in_array($this->extension, $this->fileTypes)) {
                    $this->errorMessage = lang("upload-file-not-valid-file");
                    $this->error = true;
                }

                if ($this->sourceSize > $this->maxFileSize) {
                    $this->errorMessage = lang("upload-file-size-error", array('size' => format_bytes($this->maxFileSize)));
                    $this->error = true;
                }
                break;
        }
    }

    /**
     * Validate upload files for multiple uploads
     * @param array $files
     * @return boolean
     */
    public function validate($files)
    {
        $isError = false;
        foreach($files as $file){
            $pathInfo = pathinfo($file['name']);
            $this->extension = strtolower($pathInfo['extension']);
            $this->sourceSize = $file['size'];
            switch($this->type) {
                case 'image':
                    if (!in_array($this->extension, $this->imageTypes)){
                        $this->errorMessage = lang("upload-file-not-valid-image");
                        $this->error = true;
                    }
                    if ($this->sourceSize > $this->maxImageSize) {
                        $this->errorMessage = lang("upload-file-size-error", array('size' => format_bytes($this->maxImageSize)));
                        $this->error = true;
                    }
                    break;
                case 'video':
                    if (!in_array($this->extension, $this->videoTypes)) {
                        $this->errorMessage = lang("upload-file-not-valid-video");
                        $this->error = true;
                    }
                    if ($this->sourceSize > $this->maxVideoSize) {
                        $this->errorMessage = lang("upload-file-size-error", array('size' => format_bytes($this->maxVideoSize)));
                        $this->error = true;
                    }
                    break;
                case 'audio':
                    if (!in_array($this->extension, $this->audioTypes)) {
                        $this->errorMessage = lang("upload-file-not-valid-audio");
                        $this->error = true;
                    }
                    if ($this->sourceSize > $this->maxAudioSize) {
                        $this->errorMessage = lang("upload-file-size-error", array('size' => format_bytes($this->maxAudioSize)));
                        $this->error = true;
                    }
                    break;
                case 'file':
                    if (!in_array($this->extension, $this->fileTypes)) {
                        $this->errorMessage = lang("upload-file-not-valid-file");
                        $this->error = true;
                    }

                    if ($this->sourceSize > $this->maxFileSize) {
                        $this->errorMessage = lang("upload-file-size-error", array('size' => format_bytes($this->maxFileSize)));
                        $this->error = true;
                    }
                    break;
            }
        }
    }

    /**
     * Function to confirm file passes
     */
    public function passed()
    {
        return !$this->error;
    }

    /**
     * Function to set destination
     */
    public function setPath($path)
    {
        $this->baseDir = "files/uploads/".$path;
        $path = path("files/uploads/").$path;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
            //create the index.html file
            if (!file_exists($path.'index.html')) {
                $file = fopen($path.'index.html', 'x+');
                fclose($file);
            }
        }
        $this->destinationPath = $path;
        return $this;
    }

    /**
     *Function to resize image
     * @param int $width
     * @param int $height
     * @param string $fit
     * @param string $any
     * @return $this
     */
    public function resize($width = null, $height = null, $fit = "inside", $any = "down")
    {
        if ($this->error) return false;
        $fileName = md5($this->sourceName.time()).'.'.$this->extension;
        $fileName = (!$width) ? '_%w_'.$fileName : '_'.$width.'_'.$fileName;

        $this->result = $this->baseDir.$fileName;

        if ($width) {
            $this->finalizeResize($fileName, $width, $height, $fit, $any);
        } else {
            foreach($this->imageSizes as $size) {
                $this->finalizeResize(str_replace('%w', $size, $fileName), $size, $size, $fit, $any);
            }
        }

        return $this;
    }

    /**
     * @param $filename
     * @param $width
     * @param $height
     * @param $fit
     * @param $any
     */
    private function finalizeResize($filename, $width, $height, $fit, $any)
    {
        try {
            if ($this->animatedGif and $this->extension == "gif") {
                $Gif = new \GIF_eXG($this->sourceFile, 1);
                if (!$height) $height = $width;
                $Gif->resize($this->destinationPath.$filename, $width, $height, 1, 0);
                if(extension_loaded('exif')) {
                    $layer = \PHPImageWorkshop\ImageWorkshop::initFromPath($this->sourceFile, true);
                } else {
                    $layer = \PHPImageWorkshop\ImageWorkshop::initFromPath($this->sourceFile);
                }

                if($width == 550) {
                    $layer->resizeInPixel($width, $height, true);
                }
                elseif ($width < 600) {
                    $layer->cropMaximumInPixel(0, 0, "MM");
                    $layer->resizeInPixel($width, $height);
                } else {
                    $layer->resizeToFit($width, $height, true);
                }
                $filename = str_replace($this->extension, 'jpg', $filename);
                $layer->save($this->destinationPath, $filename);
            } else {
                /**$wm = WideImage::load($this->sourceFile);
                $wm = $wm->resize($width, $height, $fit, $any);
                $wm->saveToFile($this->destinationPath.$filename);**/

                if ($this->linkContent) {
                    $layer = \PHPImageWorkshop\ImageWorkshop::initFromString($this->linkContent);
                } else {
                    if(extension_loaded('exif')) {
                        $layer = \PHPImageWorkshop\ImageWorkshop::initFromPath($this->sourceFile, true);
                    } else {
                        $layer = \PHPImageWorkshop\ImageWorkshop::initFromPath($this->sourceFile);
                    }
                }
                if($width == 550) {
                    $layer->resizeInPixel($width, $height, true);
                }
                elseif ($width < 600) {
                    $layer->cropMaximumInPixel(0, 0, "MM");
                    $layer->resizeInPixel($width, $height);
                } else {
                    $layer->resizeToFit($width, $height, true);
                }

                $layer->save($this->destinationPath, $filename);
            }

            runHook("upload", null, array($this, $filename));
        } catch(Exception $e){
            exit($e->getMessage());
            $this->result = '';
        }
    }

    /**
     * Function to crop image
     * @param int $left
     * @param int $top
     * @param int $width
     * @param int $height
     * @return $this
     */
    public function crop($left = 0, $top = 0, $width = '100%', $height = '100%')
    {
        if ($this->error) return false;
        $fileName = md5($this->sourceName.time()).'.'.$this->extension;
        $fileName = '_'.str_replace('%', '', $width).'_'.$fileName;
        $this->result = $this->baseDir.$fileName;

        try{
            $layer = \PHPImageWorkshop\ImageWorkshop::initFromPath($this->sourceFile, true);
            $layer->cropInPixel($width, $height, $left, $top);
            $layer->save($this->destinationPath, $fileName);
            /**$wm = $wm->crop($left, $top, $width, $height);
            $wm->saveToFile($this->destinationPath.$fileName);**/
            runHook("upload", null, array($this, $fileName));
        } catch(Exception $e){$this->result = '';}

        return $this;
    }
    /**
     * Function to get result
     * @return string
     */
    public function result()
    {
        return $this->result;
    }



    /**
     * Function to upload video
     */
    public function uploadVideo()
    {
        return $this->directUpload();
    }

    /**
     * function to upload file
     */
    public function uploadFile()
    {
        return $this->directUpload();
    }

    protected function directUpload()
    {
        if ($this->error) return false;
        $fileName = md5($this->sourceName.time()).".".$this->extension;
        $this->result = $this->baseDir.$fileName;
        move_uploaded_file($this->sourceFile, $this->destinationPath.$fileName);
        return $this;
    }

    public function getError()
    {
        return $this->errorMessage;
    }

    public static function isImage($file) {
        $name = (isset($file['name'])) ? $file['name'] : false;
        if (!$name and $file) $name = $file;
        if ($name ) {
            $name = strtolower($name);
            foreach(array('png', 'jpg', 'gif', 'jpeg') as $type) {
                if (preg_match("#\.$type#", $name)) return true;
            }
        }
        return false;
    }
}