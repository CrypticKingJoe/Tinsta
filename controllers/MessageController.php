<?php
class MessageController extends Controller {
    public function load() {

        $id = input('id');
        if ($id and $id != 'false') {
            $user = get_user($id);
            return view('main::message/pane', array('user' => $user));
        } else{

            return view('main::message/index');
        }
    }

    public function send() {
        $userid = input('to');
        $text = input('text');
        $image = '';
        if ($file = input_file('file')) {
            $uploader = new Uploader($file);
            $uploader->setPath("chat/messages/");
            if($uploader->passed()) {
                $image = $uploader->resize(900)->result();
            }
        }

        if (empty($text) and empty($image)) return '';
        $id = send_message($userid, $text, $image);
        if (!$id) return '';
        $message = get_message($id);
        echo view('main::message/display', array('message' => $message));

    }

    public function paginate() {
        $userid = input('userid');
        $offset = input('offset') + 10;
        $messages = get_messages($userid, $offset);
        $result = array(
            'offset' => $offset,
            'content' => ''
        );
        foreach($messages as $message) {
            $result['content'] .= view('main::message/display', array('message' => $message));
        }
        return json_encode($result);
    }
}