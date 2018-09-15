<?php
function post_add($photos,$videoFile,$code,$tags,$caption, $codeType,$videoImage, $isStory = 0) {
    $time = time();
    $tags = explode(',', $tags);

    db()->query("INSERT INTO posts (is_story,user_id,caption,images,video,embed_code,code_type,video_image,date_created)VALUES(?,?,?,?,?,?,?,?,?)",
        $isStory,get_userid(), $caption, perfectSerialize($photos), $videoFile, $code,$codeType,$videoImage, $time);
    $id = db()->lastInsertId();
    process_mention($caption, $id);
    process_hashtag($caption, $id);
    foreach($tags as $tag) {
        if (!empty($tag)) {
            $tagId = get_tag_id($tag);
            db()->query("INSERT INTO post_tags (post_id,tag_id)VALUES(?,?)", $id, $tagId);
        }
    }

    if ($isStory) {
        add_story($id);
    }
    process_point('add-post-point');
    return $id;
}

function add_story($id) {
    db()->query("INSERT INTO story_posts (post_id,user_id,time_created) VALUES(?,?,?)", $id, get_userid(),time());
    return true;
}

function get_stories() {
    $sql = "SELECT DISTINCT(user_id) FROM story_posts";
    $followIds = get_following(get_userid(), true);
    $followIds[] = get_userid();
    if ($followIds) {
        $ids = implode(',', $followIds);
        $sql .= " WHERE  user_id  IN ($ids) ";
    }

    $sql .= " ORDER BY time_created DESC ";
    return db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
function get_stories_userids() {
    $stories = get_stories();
    $ids = array();
    foreach($stories as $story) {
        $ids[] = $story['user_id'];
    }
    return $ids;
}

function get_story_nav($userid, $type = 'prev') {
    $ids = get_stories_userids();
    $key = array_search($userid, $ids);
    if ($type == 'prev') {
        $key = $key - 1;
    } else {
        $key = $key + 1;
    }
    if (isset($ids[$key])) return $ids[$key];
    return false;
}

function get_story_posts($userId) {
    $posts = array();
    $stories = db()->query("SELECT post_id FROM story_posts WHERE user_id=? ", $userId);
    while($fetch = $stories->fetch(PDO::FETCH_ASSOC)) {
        $posts[] = $fetch['post_id'];
    }
    return $posts;
}

function delete_old_stories() {
    $time = time() - (3600 * config('story-deleted-at', 24));
    $query = db()->query("SELECT id,post_id FROM story_posts WHERE time_created < $time ");
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        db()->query("DELETE FROM story_posts WHERE id=?", $fetch['id']); //delete story posts
        delete_post($fetch['post_id'], true);
    }
    return true;
}

function get_all_post_tags($id) {
    $query = db()->query("SELECT tag_id FROM post_tags WHERE post_id=?", $id);
    $ids = array();
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        $ids[] = $fetch['tag_id'];
    }
    if (!$ids) return array();
    $ids = implode(',', $ids);
    $query = db()->query("SELECT name FROM tags WHERE id IN ($ids) ");
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function save_post($id, $caption) {
    if (!can_edit_post(get_post($id))) return false;
    return db()->query("UPDATE posts SET caption=? WHERE id=?", $caption, $id);
}

function get_post($id) {
    $query = db()->query("SELECT * FROM posts WHERE id =? ", $id);
    return $query->fetch(PDO::FETCH_ASSOC);
}

function get_posts($offset = 0, $userid = null, $type = 'feed', $limit  = null) {
    $limit = (!$limit) ? config('post-per-page', 10) : $limit;
    $sql = "SELECT * FROM posts WHERE id != '' ";

    $param = array();
    if ($userid) {
        $sql .= " AND user_id=? ";
        $param[] = $userid;
    }

    if ($type == 'favourites') {
        $ids = array();

        $q = db()->query("SELECT post_id FROM favourites WHERE user_id=?", get_userid());
        while($f = $q->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $f['post_id'];
        }
        if ($ids) {
            $ids = implode(',', $ids);
            $sql .= " AND id IN ($ids) ";
        } else {
            return array();
        }
    }

    if ($type == 'explore') {
        $tagsIds = perfectUnserialize(get_user()['my_tags']);
        if ($tagsIds) {
            $postsId = get_all_tag_post_ids($tagsIds);
            if ($postsId) {
                $postsId = implode(',', $postsId);
                $sql .= " AND (id IN ($postsId) OR id !='') ";
            }

        }
    }

    if (preg_match('#tag\|#', $type)) {
        list($tag, $dTag) = explode('|', $type);
        $tagsIds = get_all_possible_tag_ids($dTag);
        $postsId = get_all_tag_post_ids($tagsIds);
        if (!$postsId) return array();
        $postsId = implode(',', $postsId);
        $sql .= " AND id IN ($postsId) ";
    }
    if (preg_match('#search\|#', $type)) {
        list($search, $term) = explode('|', $type);
        $sql .= ' AND caption LIKE ? ';
        $param[] = '%'.$term.'%';
    }
    //block members
    if (is_loggedin()) {
        $blocked = get_block_users();
        if ($blocked) {
            $blocked = implode(',', $blocked);
            $sql .= " AND user_id NOT IN ($blocked) ";
        }
    }
    if ($type == 'feed' and config('feeds-post-list-type', 0) == 0) {
        $followIds = get_following(get_userid(), true);
        $followIds[] = get_userid();
        if ($followIds) {
            $ids = implode(',', $followIds);
            $sql .= " AND ( user_id  IN ($ids) ";
            if (config('use-interest-post', false)) {
                $tagsIds = perfectUnserialize(get_user()['my_tags']);
                if ($tagsIds) {
                    $postsId = get_all_tag_post_ids($tagsIds);
                    if (!$postsId) return array();
                    $postsId = implode(',', $postsId);
                    $sql .= " OR id IN ($postsId) ";
                }
            }
            $sql .= " )";
        }

    }

    $sql .= " AND is_story = ? ";
    $param[] = 0;
    $sql .= " ORDER by id DESC LIMIT {$limit} OFFSET $offset ";

    $query = db()->query($sql, $param);
    return $query->fetchAll(PDO::FETCH_ASSOC);

}

function get_post_nav($id, $page, $userid = null, $prev = true) {
    if ($prev) {
        $sql = "SELECT max(id) FROM posts WHERE id < $id ";
    } else {
        $sql = "SELECT min(id) FROM posts WHERE id > $id ";
    }

    $param = array();
    if ($userid) {
        $sql .= " AND user_id=? ";
        $param[] = $userid;
    }

    $blocked = get_block_users();
    if ($blocked) {
        $blocked = implode(',', $blocked);
        $sql .= " AND user_id NOT IN ($blocked) ";
    }
    if ($page == 'favourites') {
        $ids = array();

        $q = db()->query("SELECT post_id FROM favourites WHERE user_id=?", get_userid());
        while($f = $q->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $f['post_id'];
        }
        if ($ids) {
            $ids = implode(',', $ids);
            $sql .= " AND id IN ($ids) ";
        } else {
            return array();
        }
    }

    $query = db()->query("SELECT id FROM posts WHERE id=($sql) ORDER BY id DESC LIMIT 1 ", $param);
    if ($query) {
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if ($result) return $result['id'];
    }
    return false;
}

function get_all_possible_tag_ids($tag) {
    $query = db()->query("SELECT id FROM tags WHERE name LIKE ?", '%'.$tag.'%');
    $ids = array();
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        $ids[] = $fetch['id'];
    }
    return $ids;
}

function get_all_tag_post_ids($ids) {
    if (!$ids) return false;
    $ids = implode(',', $ids);
    $query = db()->query("SELECT post_id FROM post_tags WHERE tag_id IN ($ids)");
    $ids = array();
    while($fetch = $query->fetch(PDO::FETCH_ASSOC)) {
        $ids[] = $fetch['post_id'];
    }
    return $ids;
}

function can_edit_post($post) {
    if (get_userid() == $post['user_id']) return true;
    if (is_admin() or is_moderator()) return true;
    return false;
}

function delete_post($id, $fromStory = false) {
    $post = get_post($id);
    if ($fromStory and !$post['is_story']) return false;
    if (!$fromStory and !can_edit_post($post)) return false;
    if ($post['images']) {
        $images = perfectUnserialize($post['images']);
        if($images) {
            foreach($images as $image) {
                delete_file(path($image));
            }
        }

        if ($post['video']) {
            delete_file(path($post['video']));
        }
    }
    db()->query("DELETE FROM posts WHERE id=?", $id);
    db()->query("DELETE FROM comments WHERE post_id=?", $id);

    db()->query("DELETE FROM likes WHERE post_id=?", $id);
    db()->query("DELETE FROM favourites WHERE post_id=?", $id);
    if (!$fromStory) db()->query("DELETE FROM story_posts WHERE post_id=?", $id); //just to be safe of problems
    return true;
}

function count_posts($userid) {
    $query = db()->query("SELECT id FROM posts WHERE  user_id=?",  $userid);
    return $query->rowCount();
}

function search_posts($term) {
    $term = '%'.$term.'%';

    $query = db()->query("SELECT * FROM posts WHERE caption LIKE ?  LIMIT 100 ", $term);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function can_edit_comment($comment) {
    if (get_userid() == $comment['user_id']) return true;
    if (is_admin() or is_moderator()) return true;
    return false;
}

function has_liked($postId) {
    $query = db()->query("SELECT id FROM likes WHERE user_id=? AND post_id=?", get_userid(), $postId);
    return $query->rowCount();
}

function count_likes($postId) {
    $query = db()->query("SELECT id FROM likes WHERE  post_id=?",  $postId);
    return $query->rowCount();
}

function like($postId) {
    if (!has_liked($postId)) {
        db()->query("INSERT INTO likes (post_id,user_id)VALUES(?,?)", $postId, get_userid());
        $post = get_post($postId);
        $user = get_user($post['user_id']);
        $privacy = get_user_privacy($user);
        if ($privacy['like-post'] and $post['user_id'] != get_userid()) {
            add_notification($post['user_id'], 'like-post', $postId);
        }
        process_point('like-post-point');
    } else{
        db()->query("DELETE FROM likes WHERE post_id=? AND user_id=?", $postId, get_userid());
    }
}

function has_favourite($postId) {
    $query = db()->query("SELECT id FROM favourites WHERE user_id=? AND post_id=?", get_userid(), $postId);
    return $query->rowCount();
}


function favourite($postId) {
    if (!has_favourite($postId)) {
        db()->query("INSERT INTO favourites (post_id,user_id)VALUES(?,?)", $postId, get_userid());
        return true;
    } else{
        db()->query("DELETE FROM favourites WHERE post_id=? AND user_id=?", $postId, get_userid());
        return false;
    }
}

function add_comment($id, $comment) {
    db()->query("INSERT INTO comments (post_id,user_id,comment,date_created)VALUES(?,?,?,?)", $id,get_userid(),$comment,time());
    $postId = $id;
    $id = db()->lastInsertId();
    $post = get_post($postId);
    $members = array();
    $usersQuery = db()->query("SELECT DISTINCT(user_id) as user_id FROM comments WHERE post_id=? ", $postId);
    while($fetch = $usersQuery->fetch(PDO::FETCH_ASSOC)) {
        $members[] = $fetch['user_id'];
    }

    if ($post['user_id'] != get_userid()) {
        $user = get_user($post['user_id']);
        $privacy = get_user_privacy($user);
        if ($privacy['comment-post']) {
            add_notification($post['user_id'], 'comment-post-your', $postId);
        }
    }
    process_point('add-comment-point');
    foreach($members as $member) {
        if ($member != get_userid() and $member != $post['user_id']) {
            $user = get_user($member);
            $privacy = get_user_privacy($user);
            if ($privacy['comment-post']) {
                add_notification($member, 'comment-post', $postId);
            }
        }
    }

    process_mention($comment,$postId, 'comment');
    return $id;
}
function get_comment($id) {

    $query = db()->query("SELECT * FROM comments WHERE id =? ", $id);
    return $query->fetch(PDO::FETCH_ASSOC);
}
function get_comments($id, $offset = 0) {
    $limit = config('comment-per-page', 5);
    $sql = "SELECT * FROM comments WHERE post_id=? ";
    //block members
    $blocked = get_block_users();
    if ($blocked) {
        $blocked = implode(',', $blocked);
        $sql .= " AND user_id NOT IN ($blocked) ";
    }
    $sql .= " ORDER by id DESC LIMIT {$limit} OFFSET $offset ";
    $param = array($id);
    $query = db()->query($sql, $param);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}
function count_comments($id) {
    $query = db()->query("SELECT id FROM comments WHERE post_id=?", $id);
    return $query->rowCount();
}
function remove_comment($id) {
    $comment = get_comment($id);
    if(!can_edit_comment($comment)) return false;
   return db()->query("DELETE FROM comments WHERE id=?", $id);
}

function count_all_posts() {
    $query = db()->query("SELECT * FROM posts");
    return $query->rowCount();
}

function count_all_comments() {
    $query = db()->query("SELECT * FROM comments");
    return $query->rowCount();
}

function count_all_likes() {
    $query = db()->query("SELECT * FROM likes");
    return $query->rowCount();
}

function process_hashtag($caption, $id) {
    $hashtags= FALSE;
    preg_match_all("/(#\w+)/u", $caption, $matches);
    if ($matches) {
        $hashtagsArray = array_count_values($matches[0]);
        $hashtags = array_keys($hashtagsArray);
        foreach($hashtags as $hashtag) {
            $tag = str_replace('#','', $hashtag);
            if (!empty($tag)) {
                $tagId = get_tag_id($tag);
                db()->query("INSERT INTO post_tags (post_id,tag_id)VALUES(?,?)", $id, $tagId);
            }
        }
    }


    return true;
}
function process_mention($caption, $id, $type = 'post') {
    preg_match_all('/(^|\s)(@\w+)/', $caption, $matches);
    $mentions = array_map('trim', $matches[0]);
    foreach($mentions as $mention) {
        $username = str_replace('@', '', $mention);
        $user = get_user($username);
        if ($user and $user['id'] != get_userid()) {
            //send notification
            $key = 'mention-you';
            if ($type == 'comment')  $key = 'mention-you-comment';
            add_notification($user['id'], $key, $id);
        }
    }
    return true;
}

function format_mention($text) {
    preg_match_all('/(^|\s)(@\w+)/', $text, $matches);
    $mentions = array_map('trim', $matches[0]);
    foreach($mentions as $mention) {
        $username = str_replace('@', '', $mention);
        $user = get_user($username);
        if ($user) {
            $link = profile_link($user);
            $text = str_replace($mention, "<a href='$link' class='ajax-link'>$mention</a>", $text);
        }
    }

    preg_match_all("/(#\w+)/u", $text, $matches);
    if ($matches) {
        $hashtagsArray = array_count_values($matches[0]);
        $hashtags = array_keys($hashtagsArray);
        foreach($hashtags as $hashtag) {
            $tag = str_replace('#','', $hashtag);
            if (!empty($tag)) {
                $link = url('explore/tag?t='.$tag);
                $text = str_replace($hashtag, "<a href='$link' class='ajax-link'>$hashtag</a>", $text);
            }
        }
    }


    return $text;
}
