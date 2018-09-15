<?php
class AdminController extends Controller {
    public function __construct() {
        parent::__construct();
        Layout::getInstance()->activeMenu = 'admin';
    }
    public function index() {
        return $this->render(view('main::admin/index'));
    }

    public function settings() {
        $message = null;
        $errorMessage = null;
        if (request_is_post()) {
            $val = input('val');
            if ($val) {
                if ($logo = input_file('site_logo')) {
                    $uploader = new Uploader($logo);
                    $uploader->setPath('logo/');
                    if ($uploader->passed()) {
                        $val['site-logo'] = $uploader->uploadFile()->result();
                    } else {
                        $errorMessage = $uploader->getError();
                    }
                }

                if ($favicon = input_file('site_favicon')) {
                    $uploader = new Uploader($favicon);
                    $uploader->setPath('favicon/');
                    if ($uploader->passed()) {
                        $val['site-favicon'] = $uploader->uploadFile()->result();
                    } else {
                        $errorMessage = $uploader->getError();
                    }
                }
                save_admin_settings($val);
                $message = lang('admin-settings-saved-success');
            }

            if ($email = input('test_email')) {
                $sent = Email::getInstance()->setAddress($email)->setSubject("Email Testing")->setMessage("Testing email settings")->send();
                if ($sent) {
                    $message = "Email sent successfully";
                } else {
                    $errorMessage = "Failed to send email, please confirm the details";
                }
            }
        }
        return $this->render(view('main::admin/settings', array('message' => $message, 'error' => $errorMessage)));
    }

    public function tags() {
        $this->setTitle(lang('tags'));
        $message = null;
        if (request_is_post()) {
            $tag = input('name');
            add_tag($tag, true);
            redirect(url('admin/tags'));
        }
        return $this->render(view('main::admin/tags', array('message' => $message)));
    }

    public function tagDelete() {
        $id = input('id');
        delete_tag($id);
        redirect(url('admin/tags'));
    }

    public function appearance() {
        $this->setTitle(lang('appearance'));
        $message = null;
        if (request_is_post()) {
            $val = input('val');
            $images = input('img');

            foreach($images as $image => $value) {
                $val[$image] = $value;
                if ($imageFile = input_file($image)) {
                    $uploader = new Uploader($imageFile);
                    $uploader->setPath("settings/".getSiteId().'/');
                    if ($uploader->passed()) {
                        $val[$image] = $uploader->uploadFile()->result();
                    } else {
                        //there is problem
                    }
                }
            }
            save_admin_settings($val);
            $message = lang('admin-settings-saved-success');
        }
        return $this->render(view('main::admin/appearance', array('message' => $message)));
    }

    public function users() {
        $this->setTitle(lang('users-management'));
        return $this->render(view('main::admin/users', array()));
    }

    public function editUser() {
        $this->setTitle(lang('users-management'));
        $error = null;
        $message = null;
        $id = input('id');
        if (input('type') == 'delete') {
            delete_user($id);
            redirect(url('admin/users'));
        }
        $user = get_user($id);

        if (request_is_post()) {
            $val = input('val');
            if($avatar = input_file('avatar')) {
                $uploader = new Uploader($avatar);
                $uploader->setPath("user/".get_userid().'/');
                if ($uploader->passed()) {
                    $val['avatar'] = $uploader->resize()->result();
                } else {
                    $error = $uploader->getError();
                    //return json_encode($result);
                }
            }
            $username = input('username');
            $email = input('email');
            if ($username != $user['username']) {
                if (check_username($username, $user['id']) or username_is_bad($username)) {
                    $username = $user['username'];
                    $error = lang('username-already-exist');
                }
            }

            if ($email != $user['email']) {
                if (check_email($email,  $user['id'])) {
                    $email = $user['email'];
                    $error = lang('email-already-exist');
                }
            }
            $password = input('password');
            if ($password) {
                $password = hash_make($password);
            } else {
                $password = $user['password'];
            }
            $val['password'] = $password;
            admin_update_user($val, $id);
            if (!$error) $message = lang('user-details-save');
            $user = get_user($id);
        }
        return $this->render(view('main::admin/edit-user', array('user' => $user, 'error' => $error, 'message' => $message)));
    }

    public function posts() {
        $this->setTitle(lang('posts-management'));
        return $this->render(view('main::admin/posts', array()));
    }

    public function adverts() {
        $this->setTitle(lang('advertisements'));
        if (request_is_post()) {
            $val = input('val', '', false);

            save_admin_settings($val);
            $message = lang('admin-settings-saved-success');
        }
        return $this->render(view('main::admin/adverts', array()));
    }

    public function pages() {
        $this->setTitle(lang('pages'));
        if (request_is_post()) {
            $val = input('val');

            save_admin_settings($val);
            $message = lang('admin-settings-saved-success');
        }
        return $this->render(view('main::admin/pages', array()));
    }

    public function requests() {
        if (input('type')) {
            $type = input('type');
            process_verification_request($type, input('id'));
        }
        return $this->render(view('main::admin/requests', array()));
    }

    public function badges() {
        $this->setTitle(lang('user-badges'));

        return $this->render(view('main::admin/badges', array()));
    }

    public function badge() {
        $this->setTitle(lang('user-badges'));
        $type = input('type', 'edit');
        switch($type) {
            case 'delete':
                delete_badge(input('id'));
                redirect(url('admin/bages'));
                break;
            default:
                $badge = array();
                if (input('id')) $badge = get_badge(input('id'));
                if (request_is_post()) {
                    save_badge(input('val'), $badge);
                    redirect(url('admin/badges'));
                }
                return $this->render(view('main::admin/badge', array('badge' => $badge)));
                break;
        }


    }

    public function updateSystem() {
        try{
            db()->query(file_get_contents(path('update/sql.txt')));
        } catch(Exception $e){}
        return redirect(url('admin/settings'));
    }

    public function render($content) {
        return parent::render(view('main::admin/layout', array('content' => $content)));
    }
}