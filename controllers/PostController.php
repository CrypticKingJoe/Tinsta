<?php
class PostController extends Controller {
    public function add() {
        $result = array(
            'status' => 0,
            'message' => lang('post-empty-error'),
            'post' => ''
        );
        $images = input_file('photo');
        $video = input_file('video');
        $url = input('url');
        $tags = input('tag');
        $caption = input('caption');
        $story = input_file('story');

        if (!$images and !$video and !$url and !$story) return json_encode($result);
        $photos = array();
        if ($images) {
            foreach($images as $image ) {
                $uploader = new Uploader($image);
                $uploader->setPath('posts/photo/');
                if ($uploader->passed()) {
                    $photos[] = $uploader->resize(1000)->result();
                } else {
                    $result['message'] = $uploader->getError();
                    return json_encode($result);
                }
            }
        }

        $videoFile = '';
        $videoImage = '';
        if ($video and !$photos) {

            $uploader = new Uploader($video, 'video');
            $uploader->setPath('posts/videos/');
            if ($uploader->passed()) {
                $videoFile = $uploader->uploadVideo()->result();
                $file_path = $videoFile;
                if ($this->ffmpegExists()) {
                    //we can do conversation
                    $photoPath = 'files/uploads/video/photos/';
                    $photoName = md5(time().$videoFile).'dasjaslk.jpg';
                    @mkdir(path($photoPath), 0777, true);
                    $fullPhotoPath = $photoPath.$photoName;

                    $details = pathinfo($file_path);
                    $ext = strtolower($details['extension']);
                    $name = md5(str_replace($ext, 'mp4', $file_path).time()).'.mp4';
                    @mkdir(path('files/uploads/video/converted/'), 0777, true);
                    $output_file = 'files/uploads/video/converted/'.$name;
                    shell_exec('ffmpeg -i '.path($file_path).' -vcodec h264 -acodec aac -strict -2 '.path($output_file));
                    shell_exec('ffmpeg -ss 0.5 -i '.path($output_file).' -t 1 -s 480x300 -f image2 '.path($fullPhotoPath).' ');
                    $videoImage = $fullPhotoPath;
                    $videoFile = $output_file;
                    delete_file(path($file_path));//safely delete the old file
                }
            } else {
                $result['message'] = $uploader->getError();
                return json_encode($result);
            }
        }

        $code = '';
        $codeType = '';
        if ($url and !$photos and !$videoFile) {
           try{
               include_once path('includes/libraries/embed/autoloader.php');
               $info = \Embed\Embed::create($url);
               //var_dump($info->code);
               //exit;
               if ($info->type == 'photo' or $info->type == 'video' or $info->image) {
                   $code = ($info->code == null) ? '' : $info->code;
                   $videoImage = $info->image;
                   if ($videoImage) {
                       $uploader = new Uploader($videoImage, 'image', false, true, true);
                       $uploader->setPath('posts/embed/');
                       $videoImage = $uploader->resize(800)->result();
                   }
                   $codeType = $info->type;
               } else {
                   $result['message'] = lang('url-no-contain-images-video');
                   return json_encode($result);
               }
           } catch(Exception $e) {

               $result['message'] = lang('url-no-contain-images-video').$e->getMessage();
               return json_encode($result);
           }
        }

        $isStory = 0;
        if ($story) {
            $isStory = 1;
            $fileInfo = pathinfo($story['name']);
            $ext = $fileInfo['extension'];
            if (is_image($ext)) {
                $uploader = new Uploader($story);
                $uploader->setPath('posts/photo/');
                if ($uploader->passed()) {
                    $photos[] = $uploader->resize(1000)->result();
                } else {
                    $result['message'] = $uploader->getError();
                    return json_encode($result);
                }
            } else {
                $uploader = new Uploader($story, 'video');
                $uploader->setPath('posts/videos/');
                if ($uploader->passed()) {
                    $videoFile = $uploader->uploadVideo()->result();
                    $file_path = $videoFile;
                    if ($this->ffmpegExists()) {
                        //we can do conversation
                        $photoPath = 'files/uploads/video/photos/';
                        $photoName = md5(time().$videoFile).'dasjaslk.jpg';
                        @mkdir(path($photoPath), 0777, true);
                        $fullPhotoPath = $photoPath.$photoName;

                        $details = pathinfo($file_path);
                        $ext = strtolower($details['extension']);
                        $name = md5(str_replace($ext, 'mp4', $file_path).time()).'.mp4';
                        @mkdir(path('files/uploads/video/converted/'), 0777, true);
                        $output_file = 'files/uploads/video/converted/'.$name;
                        shell_exec('ffmpeg -i '.path($file_path).' -vcodec h264 -acodec aac -strict -2 '.path($output_file));
                        shell_exec('ffmpeg -ss 0.5 -i '.path($output_file).' -t 1 -s 480x300 -f image2 '.path($fullPhotoPath).' ');
                        $videoImage = $fullPhotoPath;
                        $videoFile = $output_file;
                        delete_file(path($file_path));//safely delete the old file
                    }
                } else {
                    $result['message'] = $uploader->getError();
                    return json_encode($result);
                }
            }
        }

        $id = post_add($photos,$videoFile,$code,$tags,$caption,$codeType,$videoImage, $isStory);
        $post = get_post($id);
        $result['status']= 1;
        $result['message'] = lang('posted-success');
        if (!$isStory) $result['post'] = view('main::post/each', array('post' => $post));
        return json_encode($result);
    }

    public function ffmpegExists()  {
        if(!function_exists('shell_exec')) return false;
        $ffmpeg = trim(shell_exec('which ffmpeg'));
        if(empty($ffmpeg)) return false;
        return true;
    }

    public function like() {
        like(input('id'));
        return json_encode(array('count' => count_likes(input('id'))));
    }

    public function favourite() {
        $result = favourite(input('id'));
        return json_encode(array('message' => ($result) ? lang('add-to-favourite-list') : lang('remove-favourite-list')));
    }

    public function addComment() {
        $id = input('id');
        $comment = input('comment');
        $commentId = add_comment($id, $comment);
        return view('main::post/comment', array('comment' => get_comment($commentId)));
    }

    public function removeComment() {
        remove_comment(input('id'));
    }

    public function moreComment() {
        $offset = input('offset') + config('comment-per-page', 5);
        $comments = get_comments(input('id'), $offset);
        $comments = array_reverse($comments);
        $content = '';
        foreach($comments as $comment) {
            $content .= view('main::post/comment', array('comment' => $comment));
        }
        $result = array(
            'offset' => $offset,
            'content' => $content
        );
        return json_encode($result);
    }

    public function page() {
        $post = get_post(segment(1));
        if (!$post) redirect(url());
        if ($post['caption']) {
            $this->setTitle($post['captionn']);
        } else {
            $user = get_user($post['user_id']);
            $this->setTitle($user['full_name']);
        }
        $prev = false;
        $next = false;
        return $this->render(view('main::post/page', array('post' => $post , 'prev' => $prev, 'next' => $next )));
    }

    public function loadPost() {
        $post = get_post(input('id'));

        $prev = false;
        $next = false;
        if (input('page')) {
            $prev = get_post_nav(input('id'),input('page'), input('userid'));
            $next = get_post_nav(input('id'),input('page'), input('userid'), false);
        }
        return view('main::post/page', array('post' => $post, 'prev' => $prev, 'next' => $next ));
    }

    public function savePost() {
        $caption = input('caption');
        $tags = input('tags');
        save_post(input('id'), $caption);
        return lang('post-saved-success');
    }

    public function deletePost() {
        delete_post(input('id'));
        return lang('post-deleted-success');
    }

    public function morePost() {
        $userid = input('userid');
        $page = input('page');
        $type = input('type');
        $offset = input('offset');
        $newOffset = $offset + config('post-per-page', 10);
        $posts = get_posts($newOffset, $userid, $page);

        $result = array(
            'offset' => $newOffset,
            'content' => ''
        );
        foreach($posts as $post){
            $result['content'] .= ($type == 'list') ? view('main::post/each', array('post' => $post)) : view('main::post/each-grid', array('post' => $post));
        }
        return json_encode($result);
    }

    public function loadVideo() {
        echo view('main::post/load-video', array('post' => get_post(input('id'))));
    }

    public function loadStory() {
        $userid = input('userid');
        $user = get_user($userid);
        $results = array(
            'posts' => '',
            'count' => 0,
            'name' => $user['full_name'],
            'avatar' => get_avatar(75, $user),
            'prev' => get_story_nav($userid),
            'next' => get_story_nav($userid, 'next')
        );
        $posts  = get_story_posts($userid);
        $thePosts = array();
        foreach($posts as $post) {
            $post = get_post($post);
            $images = perfectUnserialize($post['images']);
            $details = array(
                'type' => ($images) ? 'image' : 'video',
                'image' => '',
                'video' => '',
                'id' => $post['id'],
                'time' => strtolower(format_time($post['date_created']))
            );
            if ($images) {
                $details['image'] = url($images[0]);
            } else {
                $details['video'] = url($post['video']);
            }
            $thePosts[] = $details;

        }
        $results['posts'] = $thePosts;
        $results['count'] = count($thePosts);

        return json_encode($results);
    }

    public function confirmStory() {
        $post = get_post(input('id'));
        if ($post) {
            return 1;
        } else {
            return lang('story-not-available-anymore');
        }
    }
}