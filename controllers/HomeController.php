<?php
class HomeController extends Controller {
    public function index() {
        Layout::getInstance()->activeMenu = 'home';
        if (!is_loggedin()) {
            $content = view('main::home/index');
        } else {
            $content = view('main::home/feed');
        }
        return $this->render($content);
    }

    public function searchTag() {
        $tag = input('tag');
        $tags = find_tags($tag);
        foreach($tags as $tag) {
            echo "<a href='' class='dropdown-item'>".$tag['name']."<input type='hidden' name='tags[]' value='".$tag['id']."'/></a>";
        }
    }

    public function login() {
        $username = input('username');
        $password = input('password');
        $result = array(
            'status' => 0,
            'message' => lang('failed-to-login')
        );

        if (empty($username) or empty($password)) return json_encode($result);
        if (login_user($username, $password)) {
            $result['status'] = 1;
            $result['message'] = lang('login-success');
            $result['url'] = url('');
            return json_encode($result);
        } else {
            $result['message'] = lang('invalid-login-details');
            return json_encode($result);
        }
    }

    public function signup() {
        $name = input('name');
        $username = input('username');
        $password = input('password');
        $email = input('email');
        $result = array(
            'status' => 0,
            'message' => ''
        );

        if (empty($name)) {
            $result['message'] = lang('provide-full-name');
            return json_encode($result);
        }

        if (empty($username)) {
            $result['message'] = lang('provide-username');
            return json_encode($result);
        }

        if (empty($password)) {
            $result['message'] = lang('choose-a-password');
            return json_encode($result);
        }

        if (empty($email)) {
            $result['message'] = lang('provide-email-address');
            return json_encode($result);
        }

        if (check_username($username)) {
            $result['message'] = lang('username-already-exist');
            return json_encode($result);
        }

        if (username_is_bad($username)) {
            $result['message'] = lang('username-is-bad');
            return json_encode($result);
        }

        if (!is_email($email) or check_email($email)) {
            $result['message'] = lang('email-already-exist');
            return json_encode($result);
        }

        if (config('enable-recaptcha', false) and !validate_recaptcha()){
            $result['message'] = lang('security-check-failed');
            return json_encode($result);
        }

        register_member($username,$name,$email,$password);
        if (config('send-welcome-mail', true)) {
            $message  = lang('welcome-mail');
            Email::getInstance()->setAddress($email)
                ->setSubject(lang('welcome-to-site'))
                ->setMessage($message)->send();
        }
        return json_encode(array(
            'status' => 1,
            'message' => lang('registration-success'),
            'url' => url('getstarted')
        ));
    }

    public function logout() {
        logout_user();
        redirect(url());
    }

    public function getstarted() {
        $step = input('step', 1);
        switch($step) {
            case 4:
                user_has_getstarted();
                redirect(url());
                break;
            case 3:
                $content = view('main::getstarted/'.$step);
                break;
            case 2:
                if(request_is_post()) {
                    $result = array(
                        'status' => 1,
                        'message' => lang('thanks-for-selecting-interest'),
                        'url' => url('getstarted?step=3')
                    );
                    $tags = input('tags');
                    if ($tags) {
                        update_user_tags($tags);
                    }
                    return json_encode($result);
                }
                $content = view('main::getstarted/'.$step);
                break;
            default:
                if(request_is_post()) {
                    $result = array(
                        'status' => 0,
                        'message' => lang('something-went-wrong')
                    );
                    if($avatar = input_file('avatar')) {
                        $uploader = new Uploader($avatar);
                        $uploader->setPath("user/".get_userid().'/');
                        if ($uploader->passed()) {
                            update_user_avatar($uploader->resize()->result());
                            $result['message'] = lang('avatar-upload-success');
                            $result['url'] = url('getstarted?step=2');
                            $result['status'] = 1;
                            return json_encode($result);
                        } else {
                            $result['message'] = $uploader->getError();
                            return json_encode($result);
                        }
                    } else {
                        return json_encode($result);
                    }
                }
                $content = view('main::getstarted/'.$step);
                break;
        }
        return $this->render($content);
    }

    public function getTags() {
        exit(json_encode(array('twalo','kola')));
    }

    public function explore(){
        Layout::getInstance()->activeMenu = 'explore';
        $this->setTitle(lang('explore'));
        return $this->render(view('main::explore/index'));
    }

    public function explorePeople() {
        Layout::getInstance()->activeMenu = 'explore';
        $this->setTitle(lang('discover-people'));
        return $this->render(view('main::explore/people'));
    }

    public function exploreTag() {
        $tag = input('t');
        Layout::getInstance()->activeMenu = 'explore';
        if (!$tag) redirect(url('explore'));
        $this->setTitle('#'.$tag);
        return $this->render(view('main::explore/tag', array('tag' => $tag)));
    }

    public function info() {
        $segment = segment(0);

        switch($segment) {
            case 'privacy':
                $title = lang('privacy');
                $content = config('privacy');
                break;
            case 'about':
                $title = lang('about');
                $content = config('about');
                break;
            default:
                $title = lang('terms-and-condition');
                $content = config('terms-and-condition');
                break;
        }
        return $this->render(view('main::home/info', array('content' => $content,'title' => $title)));
    }

    public function setLanguage() {
        session_put('language', input('id'));
         redirect_back();
    }

    public function forgot() {
        $this->setTitle(lang('forgot-password'));
        $error = null;
        $success = null;
        if (request_is_post()) {
            $email = input('email');
            $user = get_user($email);
            if ($user) {
                //send the reset link
                $hash = hash_make('djsdfsjkfsd1234233'.time());
                db()->query("UPDATE members SET hash=? WHERE id=?", $hash, $user['id']);
                $link = "<a href='".url('reset/password?hash='.$hash)."'>".url('reset/password?hash='.$hash).'</a>';
                $success = lang('reset-link-sent');
                $message  = lang('reset-password-message', array('link' => $link));
                Email::getInstance()->setAddress($user['email'])
                    ->setSubject(lang('reset-password-link'))
                    ->setMessage($message)->send();
            } else {
                $error = lang('email-address-does-not-exist');
            }
        }
        return $this->render(view('main::home/forgot', array('error' => $error, 'success' => $success)));
    }

    public function reset() {
        $this->setTitle(lang('reset-password'));
        $error = null;
        $success = null;
        $hash = input('hash');
        $query = db()->query("SELECT * FROM members WHERE hash=?", $hash);
        if ($query->rowCount() < 1) redirect(url());
        if (request_is_post()) {
            $password = input('password');
            $confirm = input('confirm');
            if ($password != $confirm) {
                $error = lang('password-deos-not-match');
            } else {
                $password = hash_make($password);
                $user = $query->fetch(PDO::FETCH_ASSOC);
                db()->query("UPDATE members SET password=?,hash=? WHERE hash=?", $password, $hash,$hash);
                login_user($user['username'], $confirm);
                return redirect(url());
            }
        }
        return $this->render(view('main::home/reset', array('error' => $error, 'success' => $success)));
    }
}