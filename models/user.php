<?php
function is_loggedin() {
    return app()->authId;
}

function get_userid() {
    return app()->authId;
}

function get_user($id = null) {
    $user = app()->authUser;
    if ($id) {
        $query = db()->query("SELECT * FROM members WHERE id=? OR username=? OR email=?", $id,$id,$id);
        if ($query) $user = $query->fetch(PDO::FETCH_ASSOC);
    }
    return $user;
}

function has_getstarted() {
    if (!is_loggedin()) return false;
    //if (is_admin()) return true;
    return get_user()['has_getstarted'];
}
function check_username($username, $id = null) {
    $sql = "SELECT id FROM members WHERE username=? ";
    $param = array($username);
    if ($id) {
        $sql .= " AND id != ?";
        $param[] = $id;
    }
    
    $query = db()->query($sql, $param);
    return $query->rowCount();
}

function username_is_bad($username) {
    $bads = array('admin', 'administrator','explore','tags','people','messages','profile');
    if (is_numeric($username)) return true;
    $valid = preg_match('/^[\pL\pN_-]+$/u', $username);
    $slug = toAscii($username);

    if(!$valid or empty($slug)  or strlen($username) != strlen($slug)) return true;
    return in_array($username, $bads);
}


function check_email($email, $id = null) {
    $sql = "SELECT id FROM members WHERE email=? ";
    $param = array($email);
    if ($id) {
        $sql .= " AND id != ?";
        $param[] = $id;
    }

    $query = db()->query($sql, $param);
    return $query->rowCount();
}

function register_member($username,$name,$email,$pass) {
    $password = hash_make($pass);
    $time = time();
    $ip = get_ip();
    db()->query("INSERT INTO members (full_name,username,email,password,ip_address,last_active,date_created,banned,active)VALUES(?,?,?,?,?,?,?,?,?)",
        $name,$username,$email,$password, $ip, $time, $time,0,1);
    //login user

    login_user($username, $pass, false);

    if (config('auto-follow', '')) {
        $users = explode(',', config('auto-follow'));
        foreach($users as $user){
            $user = get_user($user);
            if ($user) {
                follow($user['id']);
            }
        }
    }
    return true;
}

function process_login() {
    $username = "";
    $password = "";
    if (isset($_COOKIE['username']) and isset($_COOKIE['user_token'])) {
        $username = $_COOKIE['username'];
        $password = $_COOKIE['user_token'];
    }
    if (isset($_SESSION['username']) and isset($_SESSION['user_token'])) {
        $username = $_SESSION['username'];
        $password = $_SESSION['user_token'];
    }

    $query = db()->query("SELECT * FROM members WHERE id = ? AND password = ? ", $username, $password);
    $result = $query->fetch(\PDO::FETCH_ASSOC);

    if (!$result) return false;

    //@TODO - Other processes for specific auth types
    app()->authId = $result['id'];
    app()->authUser = $result;


    save_login_data($result['id'], $result['password']);
    return true;
}
process_login();
function login_user($username, $password, $ban = true) {
    $query = db()->query("SELECT * FROM members WHERE email = ?  OR username = ?", $username, $username);
    $result = $query->fetch(\PDO::FETCH_ASSOC);

    if (!$result) return false;
    if (!hash_check($password, $result['password'])) return false;
    if ($ban and ($result['banned'] or !$result['active'])) redirect(url());
    app()->authId = $result['id'];
    app()->authUser = $result;
    save_login_data($result['id'], $result['password']);
    return true;
}

function login_with_user($result) {
    app()->authId = $result['id'];
    app()->authUser = $result;
    save_login_data($result['id'], $result['password']);
    return true;
}

function save_login_data($id, $password) {
    session_put("username", $id);
    session_put("user_token", $password);
    setcookie("username", $id, time() + 30 * 24 * 60 * 60, config('cookie_path'));
    setcookie("user_token", $password, time() + 30 * 24 * 60 * 60, config('cookie_path'));//expired in one month and extend on every request
}

function logout_user() {
    unset($_SESSION['username']);
    unset($_SESSION['user_token']);
    unset($_COOKIE['username']);
    unset($_COOKIE['user_token']);
    setcookie("username", "", 1, config('cookie_path'));
    setcookie("user_token", "", 1, config('cookie_path'));
}

function update_user_avatar($avatar) {
    return db()->query("UPDATE members SET avatar=? WHERE id=?", $avatar, get_userid());
}

function update_user_password($new) {
    $password = hash_make($new);
    session_put("user_token", $password);
    setcookie("user_token", $password, time() + 30 * 24 * 60 * 60, config('cookie_path'));//expired in one month and extend on every request
    return db()->query("UPDATE members SET password=? WHERE id=?", $password, get_userid());
}

function update_user_details($fullname,$website,$bio,$username,$email) {
    return db()->query("UPDATE members SET full_name=?,website=?,bio=?,username=?,email=? WHERE id=?", $fullname,$website,$bio,$username,$email, get_userid());
}

function update_user_privacy($privacy) {
    return db()->query("UPDATE members SET privacy=? WHERE id=?", $privacy, get_userid());
}

function get_user_privacy($user = null) {
    $e = array(
        'follow-you' => 1,
        'comment-post' => 1,
        'like-post' => 1,
        'email-follow' => 1,
        'who-can-view-profile' => 1,
        'who-can-message' => 1
    );
    $user = ($user) ? $user : get_user();
    $privacy = perfectUnserialize($user['privacy']);
    if ($privacy) $e = array_merge($e, $privacy);
    return $e;
}

function update_user_tags($tags) {
    $tags = perfectSerialize($tags);
    return db()->query("UPDATE members SET my_tags=? WHERE id=?", $tags, get_userid());
}
function user_has_getstarted() {
    return db()->query("UPDATE members SET has_getstarted=? WHERE id=?", 1, get_userid());
}

function search_user($term) {
    $term = '%'.$term.'%';
    $query = db()->query("SELECT * FROM members WHERE full_name LIKE ? OR username LIKE ? OR email LIKE ?  LIMIT 100 ", $term,$term,$term);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function suggest_users($limit, $offset = 0) {
    $sql = "SELECT * FROM members WHERE id!=?";
    $param = array(get_userid());

    //block members
    $blocked = get_block_users();
    if ($blocked) {
        $blocked = implode(',', $blocked);
        $sql .= " AND id NOT IN ($blocked) ";
    }

    $followIds = get_following(get_userid(), true);
    if ($followIds) {
        $ids = implode(',', $followIds);
        $sql .= " AND id NOT IN ($ids) ";
    }

    $sql .= " LIMIT {$limit} OFFSET {$offset} ";
    $query = db()->query($sql, $param);
    return $query->fetchAll(PDO::FETCH_ASSOC);

}

function get_latest_members($limit, $offset =  0) {
    $sql = "SELECT * FROM members ";


    $sql .= " ORDER BY id DESC LIMIT {$limit} OFFSET {$offset} ";
    $query = db()->query($sql);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function block_user($userid) {
    $blockedList = get_block_users();
    $blockedList[] = $userid;
    db()->query("UPDATE members SET blocked=? WHERE id=?" , perfectSerialize($blockedList), get_userid());
    //we to unfollow each other
    unFollow($userid);
    unFollow(get_userid(), $userid);
    runHook('user.blocked', null, array($userid));
    return true;
}

function get_blocked_list() {
    $blocked = get_block_users();
    if (!$blocked) return array();
    $blocked = implode(',', $blocked);
    $query = db()->query("SELECT * FROM members WHERE id IN ({$blocked}) ");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function unblock_user($userid) {
    $blockedList = get_block_users();
    $newArray = array();
    foreach($blockedList as $u) {
        if ($u != $userid) $newArray[] = $u;
    }
    db()->query("UPDATE members SET blocked=? WHERE id=?" , perfectSerialize($newArray), get_userid());
    runHook('user.unblocked', null, array($userid));
    return true;
}
function get_block_users($userid = null) {
    $user = ($userid) ? get_user($userid) : get_user();
    $blockedList = perfectUnserialize($user['blocked']);
    return ($blockedList) ? $blockedList : array();
}

function get_avatar($size = 75, $user = null) {
    $user = ($user) ? $user : get_user();
    //return url('assets/images/avatar.png');
    if (!$user['avatar']) return url('assets/images/avatar.png');
    return url(str_replace('%w', $size, $user['avatar']));
}

function profile_link($user = null, $s = null) {
    $user = ($user) ? $user : get_user();
    $url = $user['username'];
    if ($s) $url .= "/".$s;
    return url($url);
}

function profile_owner() {
    if (!is_loggedin()) return false;
    if (app()->profileId == get_userid()) return true;
    return false;
}

function count_follow($id, $following = true) {
    if ($following) {
        $query = db()->query("SELECT id FROM follow WHERE following_id=?", $id);
    } else {
        $query = db()->query("SELECT id FROM follow WHERE follow_id=?", $id);
    }
    return $query->rowCount();
}
function get_following($id , $idsOnly = false) {
    $query = db()->query("SELECT follow_id FROM follow WHERE following_id=?", $id);
    $ids = array();
    while($f = $query->fetch(PDO::FETCH_ASSOC)) {
        $ids[] = $f['follow_id'];
    }
    if (!$ids) return array();
    if ($idsOnly) return $ids;
    $ids = implode(',', $ids);
    $query = db()->query("SELECT * FROM members WHERE id IN ($ids)");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function get_followers($id ) {
    $query = db()->query("SELECT following_id FROM follow WHERE follow_id=?", $id);
    $ids = array();
    while($f = $query->fetch(PDO::FETCH_ASSOC)) {
        $ids[] = $f['following_id'];
    }
    if (!$ids) return array();
    $ids = implode(',', $ids);
    $query = db()->query("SELECT * FROM members WHERE id IN ($ids)");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function is_following($id) {
    $query = db()->query("SELECT id FROM follow WHERE follow_id=? AND following_id=?", $id, get_userid());
    return $query->rowCount();
}

function follow($id) {
    $user = get_user($id);
    $privacy = get_user_privacy($user);
    if ($privacy['follow-you']) {
        add_notification($id, 'follow-you','');
    }
    if ($privacy['email-follow']) {
        $message  = lang('new-follower-mail', array('name' => get_user()['full_name']));
        Email::getInstance()->setAddress($user['email'])
            ->setSubject(lang('you-have-new-follower'))
            ->setMessage($message)->send();
    }
    process_point('add-follow-point');
    db()->query("INSERT INTO follow (follow_id,following_id)VALUES(?,?)", $id, get_userid());
    return true;
}
function unFollow($id, $userid = null) {
    $userid = ($userid) ? $userid : get_userid();
    db()->query("DELETE FROM follow WHERE follow_id=? AND following_id=?", $id, $userid);
    return true;
}

function get_notifications($offset = 0, $limit = 10) {
    $sql = "SELECT * FROM notifications WHERE user_id=?  ";
    $param = array(get_userid());
    //block members
    $blocked = get_block_users();
    if ($blocked) {
        $blocked = implode(',', $blocked);
        $sql .= " AND from_userid NOT IN ($blocked) ";
    }
    $sql .= "ORDER BY id desc LIMIT {$limit} OFFSET {$offset}";
    $query = db()->query($sql, $param);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function count_unread_notifications() {
    $query = db()->query("SELECT id FROM notifications WHERE user_id=? AND seen=?", get_userid(), 0);
    return $query->rowCount();
}

function add_notification($userid,$key,$postId = null) {
    return db()->query("INSERT INTO notifications (user_id,from_userid,post_id,notification_key,date_created)VALUES(?,?,?,?,?)",
        $userid, get_userid(),$postId,$key,time());
}

function mark_notification($id) {
    return db()->query("UPDATE notifications SET seen=? WHERE id=?", 1, $id);
}

function report_content($type,$id, $text) {
    return db()->query("INSERT INTO reports (user_id,type,type_id,message,date_created)VALUES(?,?,?,?,?)", get_userid(),$type,$id,$text,time());
}

function get_reports() {
    return db()->query("SELECT * FROM reports ORDER BY date_created DESC")->fetchAll(PDO::FETCH_ASSOC);
}

function user_has_pending_request() {
    $query = db()->query("SELECT id FROM verification_request WHERE user_id=?", get_userid());
    return $query->rowCount();
}

function get_verification_requests() {
    $query = db()->query("SELECT * FROM verification_request WHERE processed=? ORDER BY id DESC",0);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function add_verification_request($name, $message, $photo1, $photo2) {
    return db()->query("INSERT INTO verification_request (name,message,photo_1,photo_2,user_id)VALUES(?,?,?,?,?)",
        $name,$message,$photo1,$photo2,get_userid());
}

function process_verification_request($type, $id) {
    $query = db()->query("SELECT * FROM verification_request WHERE id=?", $id);
    $request = $query->fetch(PDO::FETCH_ASSOC);
    if ($type == 'accept') {
        db()->query("UPDATE members SET verified=? WHERE id=? ", 1, $request['user_id']);
    }
    db()->query("UPDATE verification_request SET processed=? WHERE id=?",1, $id);
    return true;
}

function count_users() {
    $query = db()->query("SELECT * FROM members");
    return $query->rowCount();
}

function can_see_profile($userid) {
    $user = get_user($userid);
    $privacy = get_user_privacy($user);
    switch($privacy['who-can-view-profile']) {
        case 1:
            return true;
            break;
        case 3 :
            return false;
            break;
        case 2 :
            if (is_following($userid)) return true;
            return false;
            break;
    }
    return false;
}

function can_receive_message($userid) {
    $user = get_user($userid);
    $privacy = get_user_privacy($user);
    switch($privacy['who-can-message']) {
        case 1:
            return true;
            break;
        case 3 :
            return false;
            break;
        case 2 :
            if (is_following($userid)) return true;
            return false;
            break;
    }
    return false;
}

function send_message($userid, $text, $image) {
    if (can_receive_message($userid)) return false;
    $cid = get_conversation_id($userid);
    db()->query("UPDATE conversations SET last_time=? WHERE id=?", time(), $cid);
    db()->query("INSERT INTO messages (cid,message,image,user_id,date_created)VALUES(?,?,?,?,?)", $cid, $text,$image,get_userid(),time());
    return db()->lastInsertId();
}

function get_conversation_id($userid, $create = true) {
    $id = get_userid();
    $query = db()->query("SELECT id FROM conversations WHERE (userid_1=? AND userid_2=?) OR (userid_1=? AND userid_2=?) ",
        $id, $userid, $userid, $id);
    if ($query->rowCount() > 0) {
        $fetch = $query->fetch(PDO::FETCH_ASSOC);
        return $fetch['id'];
    } else {
       if($create) {
           db()->query("INSERT INTO conversations (userid_1,userid_2)VALUES(?,?)", $userid, $id);
           return db()->lastInsertId();
       } else{
           return false;
       }
    }
}
function get_message($id) {
    $query = db()->query("SELECT * FROM messages WHERE id=?", $id);
    return $query->fetch(PDO::FETCH_ASSOC);
}
function get_messages($userid, $offset = 0) {
    $cid = get_conversation_id($userid, false);
    if(!$cid) return array();
    $query = db()->query("SELECT * FROM messages WHERE cid=? ORDER BY id DESC LIMIT 10 OFFSET $offset ", $cid);
    return array_reverse($query->fetchAll(PDO::FETCH_ASSOC));
}
function get_lastest_messages($userid, $lastcheck) {
    $cid = get_conversation_id($userid, false);
    if(!$cid) return array();
    $query = db()->query("SELECT * FROM messages WHERE cid=? AND date_created > ? AND user_id != ?  ORDER BY id DESC  ", $cid, $lastcheck, get_userid());
    return array_reverse($query->fetchAll(PDO::FETCH_ASSOC));
}
function mark_message_read($message) {
    if ($message['user_id'] != get_userid()) {
        db()->query("UPDATE messages SET is_read=? WHERE id=?", 1, $message['id']);
    }
    return true;
}

function get_last_message($id) {
    $query = db()->query("SELECT * FROM messages WHERE cid=? ORDER BY id DESC LIMIT 1", $id);
    return $query->fetch(PDO::FETCH_ASSOC);
}

function get_conversation_list() {
    $query = db()->query("SELECT * FROM conversations WHERE userid_1=? or userid_2=? ORDER BY last_time DESC", get_userid(), get_userid());
    $results = array();
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        $userid = ($fetch['userid_1'] == get_userid()) ? $fetch['userid_2'] : $fetch['userid_1'];
        $each= get_user($userid);
        $each['cid'] = $fetch['id'];
        $lastMessage = get_last_message($fetch['id']);
        $each['last_message'] = $lastMessage;
        $results[] = $each;
    }
    return $results;
}

function count_unread_messages($id = null) {
    $cids = array();
    if ($id) {
        $cids[] = $id;
    } else {
        $query = db()->query("SELECT id FROM conversations WHERE userid_1=? or userid_2=? ORDER BY last_time DESC", get_userid(), get_userid());
        while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
            $cids[] = $fetch['id'];
        }
    }

    if ($cids) {
        $cids = implode(',', $cids);
        $query = db()->query("SELECT id FROM messages WHERE cid IN ($cids) AND user_id!=? AND is_read=? ", get_userid(), 0);
        return $query->rowCount();
    }
    return 0;
}

function user_completion_percent() {
    $user = get_user();
    $percent = 0;
    if ($user['avatar']) $percent += 50;
    if ($user['bio']) $percent +=10;
    if (count_posts(get_userid()) > 0) $percent += 20;
    if (count_follow($user['id']) > 0) $percent +=20;
    return $percent;
}

function delete_user($id) {
    $user = get_user($id);
    if($user['avatar']) @delete_file(path($user['avatar']));
    $query = db()->query("SELECT id FROM posts WHERE user_id=?", $id);
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        delete_post($fetch['id']);
    }
    db()->query("DELETE FROM messages WHERE user_id=?", $id);
    db()->query("DELETE FROM conversations WHERE userid_1=?  OR userid_2=?", $id,$id);
    db()->query("DELETE FROM reports WHERE user_id=?", $id);
    db()->query("DELETE FROM user_badges WHERE user_id=?", $id);
    db()->query("DELETE FROM notifications WHERE user_id=? OR from_userid=?", $id,$id);
    db()->query("DELETE FROM follow WHERE follow_id=? OR following_id=?", $id,$id);
    db()->query("DELETE FROM favourites WHERE user_id=?", $id);
    db()->query("DELETE FROM members WHERE id=?", $id);
    return true;
}

function get_top_members() {
    $limit = config('top-members-limit', 10);
    $query = db()->query("SELECT * FROM members ORDER BY point DESC LIMIT $limit ");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}