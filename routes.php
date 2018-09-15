<?php
Router::addFilter('home', function() {
    if (!is_loggedin()) {
        Layout::getInstance()->showHeader = false;
    } else {
        if (!has_getstarted() and segment(0) != 'getstarted') redirect(url('getstarted'));
    }
    return true;
});
Router::addFilter('auth', function() {
    if (!is_loggedin()) {
        if (is_ajax()) exit('login-is-needed');
        return redirect(url('?login=true'));
    }
    if (!has_getstarted() and segment(0) != 'getstarted' and !is_ajax()) redirect(url('getstarted'));
    return true;
});

Router::addFilter('profile', function() {
    $user = get_user(segment(0));
    if (!$user) return false;
    app()->profileUser = $user;
    app()->profileId = $user['id'];
    return true;
});

Router::addFilter('admin', function() {
    if (!is_loggedin()) {
        if (is_ajax()) exit(json_encode(array('status' => 0, 'message' => 'Please login')));
        return redirect(url('?login=true'));
    }
    if (!is_admin()) return redirect(url());
    Layout::getInstance()->pageType = "backend";
    return true;
});
Router::get("/", array('as' => 'home', 'uses' => 'HomeController@index'))->addFilter('home');
Router::get("terms", array( 'uses' => 'HomeController@info'));
Router::get("privacy", array( 'uses' => 'HomeController@info'));
Router::get("about", array( 'uses' => 'HomeController@info'));

Router::post("signup", array( 'uses' => 'HomeController@signup'));
Router::post("login", array( 'uses' => 'HomeController@login'));
Router::any("forgot-password", array( 'uses' => 'HomeController@forgot'));
Router::any("reset/password", array( 'uses' => 'HomeController@reset'));
Router::get("logout", array( 'uses' => 'HomeController@logout'));
Router::any("getstarted", array( 'uses' => 'HomeController@getstarted'))->addFilter('auth');

Router::any("tags/search", array( 'uses' => 'HomeController@searchTag'))->addFilter('auth');
Router::any("tags/get", array( 'uses' => 'HomeController@getTags'))->addFilter('auth');
Router::any("set/language", array( 'uses' => 'HomeController@setLanguage'));

Router::any("api", array( 'uses' => 'ApiController@index'));
Router::any("developers", array( 'uses' => 'ApiController@developers'));

Router::any("social/auth/{id}", array( 'uses' => 'SocialController@index'))->where(array('id' => '[a-zA-Z0-9\-\_]+'));


Router::any("explore", array( 'uses' => 'HomeController@explore'))->addFilter('auth');
Router::any("explore/people", array( 'uses' => 'HomeController@explorePeople'))->addFilter('auth');
Router::any("explore/tag", array( 'uses' => 'HomeController@exploreTag'))->addFilter('auth');

Router::any("settings", array( 'uses' => 'UserController@settings'))->addFilter('auth');
Router::any("notification/load", array( 'uses' => 'UserController@loadNotification'))->addFilter('auth');
Router::any("notifications", array( 'uses' => 'UserController@notifications'))->addFilter('auth');
Router::any("check/events", array( 'uses' => 'UserController@checkEvents'))->addFilter('auth');
Router::any("user/block", array( 'uses' => 'UserController@block'))->addFilter('auth');
Router::any("user/unblock", array( 'uses' => 'UserController@unblock'))->addFilter('auth');
Router::any("report/content", array( 'uses' => 'UserController@report'))->addFilter('auth');

Router::any("search", array( 'uses' => 'UserController@search'))->addFilter('auth');

Router::any("post/add", array( 'uses' => 'PostController@add'))->addFilter('auth');
Router::any("like/process", array( 'uses' => 'PostController@like'))->addFilter('auth');
Router::any("favourite/process", array( 'uses' => 'PostController@favourite'))->addFilter('auth');
Router::any("comment/add", array( 'uses' => 'PostController@addComment'))->addFilter('auth');
Router::any("comment/remove", array( 'uses' => 'PostController@removeComment'))->addFilter('auth');
Router::any("comment/more", array( 'uses' => 'PostController@moreComment'))->addFilter('auth');
Router::any("post/load", array( 'uses' => 'PostController@loadPost'));
Router::any("post/save", array( 'uses' => 'PostController@savePost'))->addFilter('auth');
Router::any("post/delete", array( 'uses' => 'PostController@deletePost'))->addFilter('auth');

Router::any("story/load", array( 'uses' => 'PostController@loadStory'))->addFilter('auth');
Router::any("story/confirm", array( 'uses' => 'PostController@confirmStory'))->addFilter('auth');
Router::any("post/load/video", array( 'uses' => 'PostController@loadVideo'));
Router::any("post/load/more", array( 'uses' => 'PostController@morePost'));
Router::any("post/{id}", array( 'uses' => 'PostController@page'))->where(array('id' => '[0-9]+'));

Router::any("message/load", array( 'uses' => 'MessageController@load'))->addFilter('auth');
Router::any("message/send", array( 'uses' => 'MessageController@send'))->addFilter('auth');
Router::any("message/paginate", array( 'uses' => 'MessageController@paginate'))->addFilter('auth');


Router::get('admin', array('uses' => 'AdminController@index' ))->addFilter('admin');
Router::any('admin/settings', array('uses' => 'AdminController@settings' ))->addFilter('admin');
Router::any('admin/system/update', array('uses' => 'AdminController@updateSystem' ))->addFilter('admin');
Router::any('admin/tags', array('uses' => 'AdminController@tags' ))->addFilter('admin');
Router::any('admin/appearance', array('uses' => 'AdminController@appearance' ))->addFilter('admin');
Router::any('admin/users', array('uses' => 'AdminController@users' ))->addFilter('admin');
Router::any('admin/user/edit', array('uses' => 'AdminController@editUser' ))->addFilter('admin');
Router::any('admin/posts', array('uses' => 'AdminController@posts' ))->addFilter('admin');
Router::any('admin/requests', array('uses' => 'AdminController@requests' ))->addFilter('admin');
Router::any('admin/adverts', array('uses' => 'AdminController@adverts' ))->addFilter('admin');
Router::any('admin/pages', array('uses' => 'AdminController@pages' ))->addFilter('admin');
Router::any('admin/badges', array('uses' => 'AdminController@badges' ))->addFilter('admin');
Router::any('admin/badge', array('uses' => 'AdminController@badge' ))->addFilter('admin');
Router::any('admin/tag/delete', array('uses' => 'AdminController@tagDelete' ))->addFilter('admin');

Router::any("follow/process", array( 'uses' => 'ProfileController@processFollow'))->addFilter('auth');

Router::any("{username}", array( 'uses' => 'ProfileController@index'))->where(array('username' => '[a-zA-Z0-9\-\_]+'))->addFilter('profile');
Router::any("{username}/followers", array( 'uses' => 'ProfileController@follow'))->where(array('username' => '[a-zA-Z0-9\-\_]+'))->addFilter('profile');
Router::any("{username}/following", array( 'uses' => 'ProfileController@follow'))->where(array('username' => '[a-zA-Z0-9\-\_]+'))->addFilter('profile');
Router::any("{username}/favourites", array( 'uses' => 'ProfileController@favourites'))->where(array('username' => '[a-zA-Z0-9\-\_]+'))->addFilter('profile');