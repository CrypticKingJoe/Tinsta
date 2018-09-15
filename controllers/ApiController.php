<?php
class ApiController extends Controller {
    public function developers() {
        $this->setTitle(lang('developers'));
        return $this->render(view('main::developers/index'));
    }
    public function index() {
        $request = input('request');
        $key = input('key');
        $result = array(
            'status' => 0,
            'message' => 'NOT OK'
        );
        switch($request) {
            case 'profile':
                $id = input('user');
                $user = get_user($id);
                if ($user) {
                    $result['status'] = 1;
                    $result['message'] = 'Ok';
                    $result['username'] = $user['username'];
                    $result['email'] = $user['email'];
                    $result['website'] = $user['website'];
                    $result['bio'] = $user['bio'];
                    $result['full_name'] = $user['full_name'];
                    $result['verified'] = $user['verified'];
                    $result['avatar'] = get_avatar(200, $user);
                }
                break;
            case 'posts':
                $id = input('user');
                $user = get_user($id);
                   if ($user) {
                       $posts = get_posts(0, $user['id']);
                       $result['status'] = 1;
                       $result['message'] = 'OK';
                       $result['posts']  = $posts;
                   }
                break;
            case 'signup':
                if ($key == config('api-key')) {
                    $username = input('username');
                    $password = input('password');
                    $name = input('name');
                    $email = input('email');
                    if (!check_username($username)  and !check_email($email)) {
                        register_member($username, $name, $email, $password);
                        $result['status'] = 1;
                        $result['message'] = 'OK';
                    }
                }
                break;
            case 'login':
                if ($key == config('api-key')) {
                    $username = input('username');
                    $password = input('password');
                    if (login_user($username, $password)) {
                        $result['status'] = 1;
                        $result['message'] = 'OK';
                    }
                }
                break;
        }

        return json_encode($result);
    }
}