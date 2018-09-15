<?php
class ProfileController extends Controller {
    public function index() {
        $user = get_user();
        return $this->render(view('main::profile/index'));
    }

    public function follow() {
        return $this->render(view('main::profile/follow'));
    }
    public function favourites() {
        return $this->render(view('main::profile/favourites'));
    }

    public function processFollow() {
        $type = input('type');
        $id = input('id');
        if ($type == 'follow') {
            follow($id);
        } else {
            unFollow($id);
        }
        $user = get_user($id);
        return ($type == 'follow') ? lang('you-have-started-following', array('name' => $user['full_name'])) : lang('you-have-unfollow', array('name' => $user['full_name']));
    }

    public function render($content) {
        return parent::render(view('main::profile/layout', array('content' => $content)));
    }
}