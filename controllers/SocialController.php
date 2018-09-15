<?php
class SocialController extends Controller {
    public function __construct() {
        parent::__construct();
        require_once path('includes/libraries/hybridauth/autoload.php');
    }
    public function index() {
        $type = segment(2, 'facebook');
        $config = array(
            'callback' => getFullUrl(false),
            'keys' => [
                'id'     => config($type.'-key', ''),
                'secret' => config($type.'-secret', '')
            ],
        );

        switch($type) {
            case 'google':
                $adapter = new \Hybridauth\Provider\Google($config);
                break;
            case 'instagram':
                $adapter = new \Hybridauth\Provider\Instagram($config);
                break;
            case 'vkontakte':
                $adapter = new \Hybridauth\Provider\Vkontakte($config);
                break;
            case 'twitter':
                $adapter = new \Hybridauth\Provider\Twitter($config);
                break;
            default:
                $adapter = new \Hybridauth\Provider\Facebook($config);
                break;
        }

        try{
            $adapter->authenticate();
            $userProfile = $adapter->getUserProfile();
            $id = $userProfile->identifier;
            $email = $userProfile->email;
            $username = $userProfile->displayName;
            $fullName = $userProfile->firstName.' '.$userProfile->lastName;
            $website = ($userProfile->webSiteURL) ? $userProfile->webSiteURL : '';
            $bio = (!$userProfile->description) ? '' : $userProfile->description;
            $avatar = $userProfile->photoURL;
            $query = db()->query("SELECT * FROM members WHERE social_type=? AND social_id=? ", $type, $id);
            if ($query->rowCount() > 0) {
                //login this user
                $user = $query->fetch(PDO::FETCH_ASSOC);
                login_with_user($user);
                redirect(url());
            } else{
                $username = toAscii($username);
                $username = (!$username or check_username($username)) ? $id.time() : $username;
                $email = (!$email or check_email($email)) ? $id.'@'.$type.'.com' : $email;
                $password = hash_make($id.$username);
                db()->query("INSERT INTO members (username,email,password,bio,full_name,website,social_type,social_id,date_created,avatar)VALUES(
            ?,?,?,?,?,?,?,?,?,?)", $username,$email,$password,$bio,$fullName,$website,$type,$id,time(),$avatar);
                $user = get_user(db()->lastInsertId());
                login_with_user($user);
                redirect(url());
            }
        }catch (Exception $e) {
            exit($e->getMessage());
        }
    }
}