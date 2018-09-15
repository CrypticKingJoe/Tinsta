<?php
class UserController extends Controller {
    public function settings() {
        $type = input('type', 'profile');
        $this->setTitle(lang('edit-profile'));
        switch($type) {
            case 'verification':
                $this->setTitle('verification');
                if(request_is_post()) {
                    $message = null;
                    $photo1 = '';
                    $photo2 = '';
                    if($file = input_file('photo_1')) {
                        $uploader = new Uploader($file);
                        $uploader->setPath("user/".get_userid().'/verification/');
                        if ($uploader->passed()) {
                            $photo1 = $uploader->uploadFile()->result();
                        } else {
                            $Message = $uploader->getError();
                        }
                    }

                    if($file = input_file('photo_2')) {
                        $uploader = new Uploader($file);
                        $uploader->setPath("user/".get_userid().'/verification/');
                        if ($uploader->passed()) {
                            $photo2 = $uploader->uploadFile()->result();
                        } else {
                            $Message = $uploader->getError();
                        }
                    }

                    if (!$message) {
                        add_verification_request(input('name'), input('message'), $photo1, $photo2);
                    }
                }
                $content = view('main::settings/verification');
                break;
            case 'blocked':
                $this->setTitle('blocked-members');
                $content = view('main::settings/blocked');
                break;
            case 'privacy':
                $this->setTitle('privacy');
                $message = null;
                $errorMessage = null;
                if (request_is_post()) {
                    $val = input('val');
                    update_user_privacy(perfectSerialize($val));
                    $message = lang('notifications-updated-success');
                    app()->authUser = get_user(get_userid()); //to referesh the details
                }
                $content = view('main::settings/privacy', array('message' => $message, 'error' => $errorMessage));
                break;
            case 'notifications':
                $this->setTitle(lang('notifications'));
                $message = null;
                $errorMessage = null;
                if (request_is_post()) {
                    $val = input('val');
                    update_user_privacy(perfectSerialize($val));
                    $message = lang('notifications-updated-success');
                    app()->authUser = get_user(get_userid()); //to referesh the details
                }
                $content = view('main::settings/notifications', array('message' => $message, 'error' => $errorMessage));
                break;
            case 'password':
                $this->setTitle(lang('change-password'));
                $message = null;
                $errorMessage = null;
                if (request_is_post()) {
                    $old = input('old');
                    $new = input('new');
                    $confirm = input('confirm');
                    if (!hash_check($old,get_user()['password'])) {
                        $errorMessage = lang('old-password-wrong');
                    } else {
                        if ($new != $confirm) {
                            $errorMessage = lang('new-password-not-match');
                        } else{
                            update_user_password($new);
                            $message = lang('password-changed-success');
                        }
                    }
                }
                $content = view('main::settings/password', array('message' => $message, 'error' => $errorMessage));
                break;
            default:
                $message = null;
                $errorMessage = null;
                if (request_is_post()) {

                    if($avatar = input_file('avatar')) {
                        $uploader = new Uploader($avatar);
                        $uploader->setPath("user/".get_userid().'/');
                        if ($uploader->passed()) {
                            update_user_avatar($uploader->resize()->result());
                        } else {
                            $errorMessage = $uploader->getError();
                            //return json_encode($result);
                        }
                    }
                    $username = input('username');
                    $email = input('email');
                    if ($username != get_user()['username']) {
                        if (check_username($username, get_userid())) {
                            $username = get_user()['username'];
                            $errorMessage = lang('username-already-exist');
                        } 
                    }

                    if ($email != get_user()['email']) {
                        if (check_email($email, get_userid())) {
                            $email = get_user()['email'];
                            $errorMessage = lang('email-already-exist');
                        }
                    }
                    if ($errorMessage) $message = lang('profile-details-updated-success');
                    app()->authUser = get_user(get_userid()); //to referesh the details
                    update_user_details(input('fullname'),input('website'), input('bio'),$username, $email);
                }
                $content = view('main::settings/profile', array('message' => $message, 'error' => $errorMessage));
                break;
        }
        return $this->render(view('main::settings/layout', array('content' => $content, 'type' => $type)));
    }

    public function loadNotification() {
        $notifications = get_notifications();
        return view('main::notification/load', array('notifications' => $notifications));
    }

    public function notifications() {
        $this->setTitle(lang('notifications'));
        return $this->render(view('main::notification/index'));
    }

    public function checkEvents() {
        $result = array(
            'notify' => 0,
            'newmessage' => 0,
            'lastcheck' => time(),
            'messagecontent' => ''
        );

        if (count_unread_notifications() > 0) $result['notify'] = true;
        if (count_unread_messages() > 0) $result['newmessage'] = true;
        if (input('userid')) {
            $messages = get_lastest_messages(input('userid'), input('lastcheck'));
            foreach($messages as $message) {
                $result['messagecontent'] .= view('main::message/display', array('message' => $message));
            }
        }
        return json_encode($result);
    }

    public function search() {
        $term = input('term');
        $users = search_user($term);
        $type = 'search|'.$term;
        if (substr($term, 0, 1) == '#') {
            $type = 'tag|'.str_replace('#','',$term);
        }
        $posts = get_posts(0,null,$type, 100);
        return view('main::search/result', array('users' => $users, 'posts' => $posts));
    }

    public function block() {
        $userid = input('id');
        block_user($userid);
        return lang('user-blocked-success');
    }

    public function unblock() {
        $userid = input('id');
        unblock_user($userid);
        return lang('user-unblocked-success');
    }

    public function report() {
        $type = input('type');
        $id = input('id');
        $text = input('text');
        report_content($type,$id, $text);
        return lang('content-reported');
    }
}