window.reload_hooks = [];
function toggle_home_content(id) {
    $('.home-pane').hide();
    $(".home-access-links a").hide();
    if(id == '.home-signup') {
        $('.access-login-link').fadeIn();
    } else {
        $('.access-signup-link').fadeIn();
    }
    $(id).fadeIn();
    return false;
}

function addReloadHook(h) {
    window.reload_hooks.push(h);
}
function pre_process_result(r) {
    if (r == 'login-is-needed') {
        window.location = baseUrl + '?login=true'
        return true;
    }
    return false;

}
function runReloadHooks() {
    for(i=0;i<window.reload_hooks.length;i++) {
        var f = window.reload_hooks[i];
        //alert(window.reload_hooks.length);
        eval(f)();
    }
}

function show_notification_dropdown(t) {
    var container = $(".notification-dropdown");
    if (container.css('display') == 'none') {
        container.fadeIn();
        //$(t).find('notify')
        $.ajax({
            url :baseUrl + 'notification/load',
            success : function(c) {
                if (pre_process_result(c)) return false;
                container.find('.notification-lists').html(c);
            }
        })
    } else {
        container.fadeOut();
    }

    return false;
}
function my_confirm(f, m) {
    m = (m == undefined) ? window.lang.are_you_sure : m;
    swal({
      title: m,
      text: "",
      type: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: window.lang.yes,
      cancelButtonText: window.lang.cancel,
      confirmButtonClass: 'btn btn-confirm-ok',
      cancelButtonClass: 'btn btn-confirm-cancel',
      buttonsStyling: true
    }).then(f)
}

function showLoader() {
    $(".load-bar").fadeIn();
}

function hideLoader() {
    $(".load-bar").fadeOut();
}

function close_post_viewer() {
    var viewer = $("#post-viewer");
    viewer.fadeOut();
    viewer.find('.content').html('');
}
function view_post(id, nav) {
    var viewer = $("#post-viewer");
    var userid = '';
    var page = '';
    if (nav != undefined && nav) {
        var lists = $(".post-list");
        userid = lists.data('userid');
        page = lists.data('page');
    }
    showLoader();
    $.ajax({
        url : baseUrl + 'post/load?id=' + id,
        data : {userid:userid,page:page},
        success: function(t) {
            viewer.find('.content').html(t);
            viewer.fadeIn();
            viewer.find('.close').click(function() {
                viewer.fadeOut();
                viewer.find('.content').html('');
                return false;
            })
            viewer.find('.cover').click(function() {
                viewer.fadeOut();
                viewer.find('.content').html('');
                return false;
            })
            hideLoader();
        }
    });
    return false;
}
function view_photo(url) {
    var viewer = $("#photo-viewer");
    viewer.find('img').attr('src', url);
    viewer.fadeIn();
            viewer.find('.close').click(function() {
                viewer.fadeOut();
                return false;
            })
            viewer.find('.cover').click(function() {
                viewer.fadeOut();
                return false;
            })
    return false;
}
function validate_fileupload(fileName, type)
{
    var allowed_extensions = new Array("jpg","png","gif");
    allowed_extensions = supportImagesType.split(',');
    if (type == 'video') allowed_extensions = supportVideoType.split(',')
    var file_extension = fileName.split('.').pop().toLowerCase(); // split function will split the filename by dot(.), and pop function will pop the last element from the array which will give you the extension as well. If there will be no extension then it will return the filename.

    for(var i = 0; i <= allowed_extensions.length; i++)
    {
        if(allowed_extensions[i]==file_extension)
        {
            return true; // valid file extension
        }
    }

    return false;
}

function validate_image_size(input, type) {
    var files = input.files;
    if (type == 'image' && allowImagesUploaded != 0) {
        if (files.length > allowImagesUploaded) {
                notify(allowedImageError, 'error')
                $(input).val('');//yes
                return true;
        }
    }
    for(i = 0;i < files.length;i++) {
        var file = files[i];
        if (type == 'image') {
            if (!validate_fileupload(file.name, 'image')) {
                notify(window.lang.notImageError, 'error')
                $(input).val('');//yes
                return true;
            }
            if (file.size > allowPhotoSize) {
                //this file is more than allow photo file
                notify(allowImageSizeError, 'error');
                //empty the input
                $(input).val('');//yes
            }
        } else if (type == 'video') {
            if (!validate_fileupload(file.name, 'video')) {
                notify(window.lang.notVideoError, 'error')
                $(input).val('');//yes
                return true;
            }
            if (file.size > allowVideoSize) {
                //this file is more than allow photo file
                notify(allowVideoSizeError, 'error');
                //empty the input
                $(input).val('');//yes
            }
        } else if (type == 'both') {
            if (!validate_fileupload(file.name, 'image') && !validate_fileupload(file.name, 'video')) {
                  notify(window.lang.notStoryError, 'error')
                  $(input).val('');//yes
                  return true;
            } else {
                if (validate_fileupload(file.name, 'image')) {
                    if (file.size > allowPhotoSize) {
                        //this file is more than allow photo file
                        notify(allowImageSizeError, 'error');
                        //empty the input
                        $(input).val('');//yes
                    }
                }

                if (file.size > allowVideoSize) {
                    //this file is more than allow photo file
                    notify(allowVideoSizeError, 'error');
                    //empty the input
                    $(input).val('');//yes
                }
            }
        }
    }
}
function close_new_badge() {
        $('.new-badge-hover').remove();
        return false;
}
function show_post_editor(t) {
    var editor = $("#post-editor");
    editor.find('.post').hide()
    editor.fadeIn();
    if (t == 'photo') {
        editor.find('.post-photo').show();
        editor.find('.post-photo input').click();
    } else if(t == 'url') {
        editor.find('.post-url').show();
        editor.find('.post-url').focus();
    } else if(t == 'video') {
        editor.find('.post-video').show();
        editor.find('.post-video input').click();
    } else if(t == 'story') {
        editor.find('.post-story').show();
        editor.find('.post-story input').click();
    }

    return false;
}

function hide_post_editor() {
    $("#post-editor").fadeOut();
    return false;
}

function reset_post_editor() {
    hide_post_editor();
    var editor = $("#post-editor");
    editor.find('.post-photo input').val('')
    editor.find('.post-video input').val('')
    editor.find('.post-url').val('');
    editor.find('.emoji-input').val('')
    $(".emoji-input").data("emojioneArea").setText("");

    editor.find('.tag-input').tagsinput('removeAll');
}

function notify(m, type) {
    noty({
        text:m,
        type:type,
        progressBar : true,
        timeout:4000
    });
}

function reload_init(d) {
    $('.tool-tip').tooltip();
    if (d == undefined) {
    $('.edit-caption-modal').on('shown.bs.modal', function (e) {
        reload_init(true);
    })
    }


    const observer = lozad('.lozad', {
        rootMargin: '10px 0px', // syntax similar to that of CSS Margin
        threshold: 0.1 // ratio of element convergence
    });
    observer.observe();

    if($('#site-overview-chart').length  > 0) {
        var ctx = document.getElementById('site-overview-doughnut').getContext("2d");;
        var config = get_site_chart_config();
        new Chart(ctx, config);
    }


    $('.gif').gifplayer();

    $('body').swipe({
        swipeDown : function(event, direction, distance, duration, fingerCount, fingerData) {
            load_page(window.location.href)
        },
        preventDefaultEvents : false
    });

    if ($("#feeds-container").length > 0) {
        $('.sidebar__inner').sticky({
            topSpacing: 60,
            bottomSpacing: 100,
            center : true
          });
    }

    $('.emoji-input-top').each(function() {
            var target = $(this);
            $(this).emojioneArea({
                    pickerPosition :'top',
                            shortnames : true,
                            saveEmojisAs : 'unicode',
                                                                         //standalone : true,
                    events: {
                            keyup: function (editor, e) {
                                target.val(this.getText())
                                //e.stopPropagation();
                                //alert(this.getText());
                            },
                            click : function(editor,event) {
                                editor.focus();
                            }
                    }
            });

        });

    var tagNames = new Bloodhound({
      datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      prefetch: {
        url: baseUrl + 'tags/get',
        filter: function(list) {
          return $.map(list, function(name) {
            return { name: name }; });
        }
      }
    });
    tagNames.initialize();
    $('.tag-input').tagsinput({
        tagClass: function(item) {
            return 'badge badge-info'
        },
        source: tagNames.ttAdapter()
    });

    var jcarousel = $('.jcarousel');


        jcarousel.on('jcarousel:reload jcarousel:create', function () {
            var carousel = $(this),
                width = carousel.innerWidth();

            if (width >= 600) {
                width = width / 3;
            } else if (width >= 350) {
                width = width / 2;
            }

            carousel.jcarousel('items').css('width', Math.ceil(width) + 'px');
        })
        .jcarousel({
            wrap: null
        });

    $('.jcarousel-control-prev')
        .jcarouselControl({
            target: '-=1'
        });

    $('.jcarousel-control-next')
        .jcarouselControl({
            target: '+=1'
        });

    $('.jcarousel-pagination')
        .on('jcarouselpagination:active', 'a', function() {
            $(this).addClass('active');
        })
        .on('jcarouselpagination:inactive', 'a', function() {
            $(this).removeClass('active');
        })
        .on('click', function(e) {
            e.preventDefault();
        })
        .jcarouselPagination({
            perPage: 1,
            item: function(page) {
                return '<a href="#' + page + '">' + page + '</a>';
            }
        });

    $('#dataTable').DataTable();
    plyr.setup();
    $(".advance-select").select2();

    $('.color-picker').each(function() {
                var c = $(this);
                var input = c.find('input');
                var holder = c.find('.holder')
                input.ColorPicker({
                    onSubmit: function(hsb, hex, rgb, el) {
                    		$(el).val('#'+hex);
                    		$(el).ColorPickerHide();
                    		holder.css('background-color', '#'+hex);
                    	},
                    onBeforeShow: function () {
                    	$(this).ColorPickerSetColor(this.value);
                    },
                    onChange: function (hsb, hex, rgb) {
                    		$(el).val('#'+hex);
                    		holder.css('background-color', '#'+hex);
                    }
                }).bind('keyup', function(){
                  	$(this).ColorPickerSetColor(this.value);
                  });
        })

        $(".select2-container").each(function() {
                var n = $(this).next();
                if (n.hasClass('select2-container')) n.remove();
            });

        $('textarea').each(function () {
            $(this).on('keyup', RTLText.onTextChange);
            $(this).on('keydown', RTLText.onTextChange);
        })
    runReloadHooks();
}

function navigate_post(l, type) {
    var container = l.parent();
    var images = container.find('.image');
    var imagesLength = images.length;
    var imageToLoad = null;
    for(i=0;i < imagesLength;i++) {
        if ($('.image-'+i).css('display') != 'none') {
            if (type == 'left') {
                imageToLoad = (i == 0) ? imagesLength - 1 : i -1;
            } else {
                imageToLoad = (i == (imagesLength - 1)) ? 0 : i + 1;
            }
        }
    }

    container.find('.image').hide();
    container.find('.image-' + imageToLoad).fadeIn();
    return false;
}

function check_events() {
    var userid = '';
    var lastcheck = '';
    if (isloggedin == 0) return false;

    if ($("#messagesDialog").css('display') == 'block') {
        if ($("#message-lists").length > 0) {
            userid = $("#message-lists").data('id');
            lastcheck = $("#message-lists").data('lastcheck');
        }
    }
    $.ajax({
        url : baseUrl + 'check/events',
        data : {userid:userid,lastcheck:lastcheck},
        success : function(r) {
            if (pre_process_result(r)) return false;
             var json = jQuery.parseJSON(r);
             if (json.notify != 0) {
                var a = $('.header-notification-icon');
                a.find('.notify').remove();
                a.append('<span class="notify"></span>');
             } else {
                 var a = $('.header-notification-icon');
                 a.find('.notify').remove();
             }

             if(json.newmessage != 0) {
                    var a = $('.header-message-icon');
                    a.find('.notify').remove();
                    a.append('<span class="notify"></span>');
             } else {

                var a = $('.header-message-icon');
                a.find('.notify').remove();
             }

            if ($("#messagesDialog").css('display') == 'block') {
                if ($("#message-lists").length > 0) {
                    $("#message-lists").data('lastcheck',json.lastcheck);
                    $("#message-lists").append(json.messagecontent);
                    scroll_bottom("#message-lists");
                }
            }
        }
    })
}

function open_search() {
    $("#search-viewer").fadeIn();
    $("#search-viewer .head input").focus();
    return false;
}


function open_story(userid) {
    var c = $("#story-viewer");
    var content = c.find('.content');
    content.html('');
    c.fadeIn();
    $.ajax({
        url : baseUrl + 'story/load?userid=' + userid,
        success : function(data) {
            if (pre_process_result(data)) return false;
            var data = jQuery.parseJSON(data);
            window.storyData = data;


                content.append("<a onclick='return nav_story_left()' href='' class='story-nav story-nav-left'><i class='icons icon-arrow-left'></i></a>");
                content.append("<a onclick='return nav_story_right()' href='' class='story-nav story-nav-right'><i class='icons icon-arrow-right'></i></a>");

            var info = $("<div class='avatar'><img src='"+data.avatar+"'/> <span>"+data.name+"</span> - <span style='font-size:11px' class='story-time'></span></div>");
            content.append(info);

            var indicators = $("<div class='indicator'></div>");
            var width = (100 / data.count);
            for(i = 0; i<data.count;i++) {
                indicators.append("<a onclick='return nav_story("+i+")' style='width:"+width+"%' href='' class='story-indicator-"+i+"'><div class='progress' style='background:#868282'><div style='width:0%;height:2px;background:#FFFFFF' class='progress-bar'></div></div></a>");
            }
            content.append(indicators);
            var dContent = $("<div class='story-main-content'></div>");
            dContent.on("taphold", {duration: 10000}, function() {

            }).on('click', function() {

            });
            content.append(dContent);
            nav_story(0)
        }
    });
    return false;
}

function nav_story_left() {
    var i = window.currentStoryLine - 1;
    if (i < 0) {
        if (window.storyData.prev != false) {
            open_story(window.storyData.prev);
            return false;
        }
        i = window.storyData.count - 1;
    }
    nav_story(i);
    return false;
}
function nav_story_right(close) {
    var i = window.currentStoryLine + 1;
    if (i >= window.storyData.count) {
        if (window.storyData.next != false) {
            open_story(window.storyData.next);
            return false;
        }
        if (close != undefined) {
            close_story();
            return false;
        }
        i = 0;
    }
    nav_story(i);
    return false;
}
function nav_story(c, restart) {
    clearInterval(window.storyInterval);
    var post = window.storyData.posts[c];
    window.currentStoryLine = c;
    if (post == undefined) {

        return false;
    }
    $('.story-main-content').html('');
    $('.story-time').html(post.time);
    $('.story-main-content').css('background-image', 'none')
    for(i = 0; i<window.storyData.count;i++) {
        if (i < c) {
            $(".story-indicator-" + i).find('.progress-bar').css('width', '100%');
        }
        if (i > c) {
            $(".story-indicator-" + i).find('.progress-bar').css('width', '0%');
        }
    }

    dContent = $('.story-main-content');
    $.ajax({
        url : baseUrl + 'story/confirm?id=' + post.id,
        success : function(r) {
        if (pre_process_result(r)) return false;
            if (r == 1) {
                if (post.type == 'image') {
                    $('.story-main-content').css('background-image', 'url('+post.image+')');
                    animate_story_nav(c, 1, 50);
                    dContent.on("mousedown", function() {
                        window.storyIntervalPaused = true;
                    }).on('mouseup', function() {
                        window.storyIntervalPaused = false;
                    });
                    $(window).focus(function() {
                        window.storyIntervalPaused = false;
                    });
                    $(window).blur(function() {
                        window.storyIntervalPaused = true;
                    });
                } else {
                    $('.story-main-content').append('<video class="story-player" data-plyr=\'{ "autoplay": "true", "volume": 10 }\' poster="" controls><source src="'+post.video+'" type="video/mp4"></video>');
                    var player = plyr.setup('.story-player', {
                        controls : []
                    });
                    player[0].on('loadeddata', function(event) {
                          var instance = event.detail.plyr;
                          var duration = instance.getDuration();
                          animate_story_nav(c, 1, 100 * (100/ duration) );
                          window.storyPlayingInstance = instance;
                    });
                    player[0].on('seeking', function(event) {
                        window.storyPlayingInstance.pause();
                        window.storyIntervalPaused = true;
                    });

                    player[0].on('seeked', function(event) {
                        window.storyIntervalPaused = false;
                    });
                    dContent.on("mousedown", function() {
                        window.storyPlayingInstance.pause();
                        window.storyIntervalPaused = true;

                    }).on('mouseup', function() {
                        //window.storyPlayingInstance.play();
                        window.storyIntervalPaused = false;
                    });
                    $(window).focus(function() {
                        window.storyIntervalPaused = false;
                    });
                    $(window).blur(function() {
                        window.storyPlayingInstance.pause();
                        window.storyIntervalPaused = true;
                    });
                }
            } else {
                 $('.story-main-content').append("<span class='not-available'>"+r+"</span>");
            }
        }
    })
    return false;
}

window.storyIntervalPaused = false;
function animate_story_nav(c, secs, interval) {
    var i = secs;
    $(".story-indicator-" + c).data('width', 0);
    window.storyInterval = setInterval(function() {
        if(window.storyIntervalPaused) return false;
        var w = $(".story-indicator-" + c).data('width') + i;
        $(".story-indicator-" + c).data('width', w);
         $(".story-indicator-" + c).find('.progress-bar').css('width', w + '%');
         if (w >= 100) {
           nav_story_right(true);
         }
    }, interval)
}
function close_story() {
    var c = $("#story-viewer");
    var content = c.find('.content');
    content.html('');
    c.fadeOut();
    return false;
}
function hide_search() {
    $("#search-viewer").fadeOut();
    return false;
}

function do_search(t) {
    var i = $(t);

    if (i.val().length > 0) {
    showLoader();
        $.ajax({
            url :baseUrl + 'search',
            data : {term:i.val()},
            success : function(r) {
                hideLoader();
                $("#search-viewer .content").html(r);
                reload_init();
            }
        })
    }
}

function close_message_dialog() {
     $("#messagesDialog").modal('hide');
     return false;
}

function scroll_bottom(div) {
    var scroller = $(div);
    var height = scroller[0].scrollHeight - $(scroller).height();
    height += 3000;
    $(div).animate({ scrollTop: height}, "slow");
}

function read_more(t, id) {
    var o = $(t);
    var container = $('#' + id);
    container.find('span').hide();
    container.find('.text-full').fadeIn();
    if (container.find('.text-full').find('span').length > 0) {
        container.find('.text-full').find('span').fadeIn();
    }
    o.hide();
    return false;
}

function open_message_dialog(id, from) {
    var modal = $("#messagesDialog");
    if (modal.find('.modal-content').html() == '' || id != undefined || id == '') {
        showLoader();
        if (from == undefined) modal.modal('show');
        id = (id == undefined) ? '' : id;
        $.ajax({
            url : baseUrl + 'message/load',
            data : {id:id},
            success : function(r) {
            if (pre_process_result(r)) return false;
                modal.find('.modal-content').html(r);
                if (modal.find('textarea')) {
                    modal.find('textarea').emojioneArea({
                        pickerPosition :'top',
                                shortnames : true,
                                saveEmojisAs : 'unicode',
                                                                             //standalone : true,
                        events: {
                                keyup: function (editor, e) {
                                    target.val(this.getText())
                                    //e.stopPropagation();
                                    //alert(this.getText());
                                },
                                click : function(editor,event) {
                                    editor.focus();
                                }
                        }
                    });
                }
                hideLoader();
                reload_init();
                if ($("#message-lists").length > 0 ) {
                    $("#message-lists").scroll(function() {
                        if($(this).scrollTop() == 0) {
                        var userid  = $("#message-lists").data('userid');
                        var offset = $("#message-lists").data('offset');
                            showLoader();
                            $.ajax({
                                url : baseUrl + 'message/paginate',
                                data : {userid:userid,offset:offset},
                                success : function(s) {
                                    var s = jQuery.parseJSON(s);
                                    $("#message-lists").data('offset', s.offset)
                                    $("#message-lists").prepend(s.content);
                                    hideLoader();
                                }
                            })
                        }
                    })
                    scroll_bottom("#message-lists");
                }


            }
        })
    } else {
        modal.modal('show');
        //silent refresh for index
        if ($('#messages-list-container').length > 0) {
            $.ajax({
                    url : baseUrl + 'message/load',
                    success : function(r) {
                        if (pre_process_result(r)) return false;
                        modal.find('.modal-content').html(r);
                        hideLoader();
                        reload_init();

                    }
                })
        }
    }
    return false;
}

function show_new_message() {
    $("#messages-list-container").hide();
    $("#new-message-container").show();
    reload_init();
    return false;
}
function go_back_message(fromMessage) {
    if (fromMessage != undefined) {
            $("#new-message-container").hide();
            $("#messages-list-container").show();
    } else {
        open_message_dialog(false, true);
    }
    return false;
}
function delete_post(id) {
    my_confirm(function() {
        showLoader();
        $.ajax({
            url : baseUrl + 'post/delete',
            data : {id:id},
            success : function(r) {
                if (pre_process_result(r)) return false;
                notify(r, 'success');
                hideLoader();
                $("#each-post-" + id).remove();
                if($('.post-page-' + id).length > 0 ) {
                    $('.post-page-' + id).remove();
                    $("#post-viewer").fadeOut();
                }
            }
        })
    });
    return false;
}

function block_user(id) {
    my_confirm(function() {
        showLoader();
        $.ajax({
            url : baseUrl + 'user/block?id=' +id,
            success : function(r) {
                if (pre_process_result(r)) return false;
                notify(r, 'success');
                load_page(baseUrl);
                hideLoader();
            }
        })
    });
    return false;
}
function unblock_user(id) {
    my_confirm(function() {
        showLoader();
        $.ajax({
            url : baseUrl + 'user/unblock?id=' +id,
            success : function(r) {
                if (pre_process_result(r)) return false;
                notify(r, 'success');
                load_page(location.href);
            }
        })
    });
    return false;
}

function do_report(type,id) {
    var modal = $("#reportModal");
    modal.modal("show");
    modal.find('#send-report-btn').click(function() {
            $(".loader-container").fadeIn();
            $.ajax({
                url : baseUrl + 'report/content',
                data : {type:type,id:id,text:modal.find('textarea').val()},
                success : function(r) {
                    if (pre_process_result(r)) return false;
                    modal.modal("hide");
                    $(".loader-container").fadeOut();
                    notify(r, 'success');
                    text:modal.find('textarea').val('')
                }
            })
    });

    return false;
}
function load_page(link) {
    showLoader();
    $('body').click();

    window.onpopstate = function(e) {
            load_page(window.location, true);
        }
        $.ajax({
            url : link,
            success : function(data) {
            hideLoader();
                var data = jQuery.parseJSON(data);
                document.title = data.title;
                $("#page-content").html(data.content);
                window.history.pushState({},'New URL:' + link, link);
                $(window).scrollTop(0);
                $("#header-nav li").removeClass('active');
                //alert(data.active_menu);
                if (data.active_menu != '' ) {
                    $("#"+data.active_menu + "-nav").addClass('active');
                }

            },
            complete : function() {
                reload_init();
                $(".notification-dropdown").fadeOut();
                hide_search();
                close_story();
                close_post_viewer();
            }
        });
}

$(function() {
    reload_init();
    //notify("Twalo is here", "error");
    check_events();
    $(document).on("click", ".ajax-link", function() {
            var link = $(this).attr('href');
            load_page(link);
            return false;
        });
    $(document).on("submit", "#signup-form", function() {
        $(".loader-container").fadeIn();
        $(this).ajaxSubmit({
            url : baseUrl + 'signup',
            success : function(result) {
                var json = jQuery.parseJSON(result);
                if (json.status == 1) {
                    //we can redirect to the next destination
                    window.location.href = json.url;
                    notify(json.message, 'success');
                } else {
                    notify(json.message, 'error');
                    $(".loader-container").fadeOut();
                }
            }
        })
        return false;
    });

     $(document).on("submit", "#login-form", function() {
            $(".loader-container").fadeIn();
            $(this).ajaxSubmit({
                url : baseUrl + 'login',
                success : function(result) {
                    var json = jQuery.parseJSON(result);
                    if (json.status == 1) {
                        //we can redirect to the next destination
                        window.location.href = json.url;
                        notify(json.message, 'success');
                    } else {
                        notify(json.message, 'error');
                        $(".loader-container").fadeOut();
                    }
                }
            })
            return false;
        });

        $(document).on('change', '#getstarted-step-1-form input', function() {
            var form = $("#getstarted-step-1-form");
            $(".loader-container").fadeIn();
            form.ajaxSubmit({
                url : baseUrl + 'getstarted?step=1',
                success : function(result) {
                    if (pre_process_result(result)) return false;
                    var json = jQuery.parseJSON(result);
                    if (json.status == 1) {
                        //we can redirect to the next destination
                        load_page(json.url)
                        notify(json.message, 'success');
                    } else {
                        notify(json.message, 'error');
                    }
                    $(".loader-container").fadeOut();
                }
            })
        });

        $(document).on('submit', '#getstarted-step-2-form', function() {
            $(".loader-container").fadeIn();
            $(this).ajaxSubmit({
                url : baseUrl + 'getstarted?step=2',
                success : function(result) {
                    if (pre_process_result(result)) return false;
                    var json = jQuery.parseJSON(result);
                    if (json.status == 1) {
                        //we can redirect to the next destination
                        load_page(json.url)
                        notify(json.message, 'success');
                    } else {
                        notify(json.message, 'error');
                    }
                    $(".loader-container").fadeOut();
                }
            })
            return false;
        });

        $(document).on('click', '.tag-select', function() {
            if ($(this).hasClass('tag-default')) {
                $(this).removeClass('tag-default')
                $(this).addClass('badge-default');
                $(this).append("<input type='hidden' name='tags[]' value='"+$(this).data('id')+"'/>")
            } else {
                $(this).addClass('tag-default')
                $(this).removeClass('badge-default');
                $(this).find('input').remove();
            }
            return false;
        });

        $(document).on('keyup', '.tag-input-general', function() {
            var i = $(this);
            var target = $(i.data('target'));
            var container = $(i.data('container'))
            if (i.val().length > 0) {
                showLoader();
                $.ajax({
                    url : baseUrl + 'tags/search?tag=' +i.val(),
                    success : function(r) {
                        hideLoader();
                        if (r != '') {
                            target.html(r);
                            target.fadeIn();
                            target.find('a').click(function() {
                                var tag = $(this);
                                container.append("<a href='javascript:void(0)' class='badge badge-default badge-pill tags-lg'>"+tag.html()+'</a>');
                                target.hide();
                                return false;
                            });
                        } else {
                            target.hide();
                        }

                        $(document).click(function() {
                            target.hide();
                        })
                    }
                })
            }
        });

        $(document).on('submit', '#post-editor-form', function() {
            var image = $('.post-photo input').val();
            var video = $('.post-video input').val();
            var story = $('.post-story input').val();
            var url = $(".post-url ").val();
            if (image == '' && video == '' && url == '' && story == '') {
                notify(window.lang.post_empty_error, 'error')
                return false;
            }

            showLoader();
            $(".loader-container").fadeIn();
            $(this).ajaxSubmit({
                url : baseUrl + 'post/add',
                success : function(r) {
                    if (pre_process_result(r)) return false;
                    hideLoader();
                    $(".loader-container").fadeOut();
                    var result = jQuery.parseJSON(r);
                    if (result.status == 1) {
                        notify(result.message, 'success');
                        if ($("#posts-list").length > 0) {
                            $("#empty-post-content").remove();
                            $("#posts-list").prepend(result.post);
                        }
                        reset_post_editor();
                        reload_init();
                    } else {
                        notify(result.message, 'error');
                    }

                }
            })
            return false;
        });

        $(document).on('click', '.nav-left', function() {
            return navigate_post($(this), 'left');
        });

        $(document).on('click', '.nav-right', function() {
                    return navigate_post($(this), 'right');
                });

    $(document).on('click', '.like-btn', function() {
        var l = $(this);
           if (l.hasClass('liked')) {
              l.removeClass('liked');
           } else {
                l.addClass('liked')
           }
           showLoader();
           $.ajax({
                url : baseUrl + 'like/process',
                data : {id:l.data('id')},
                success : function(r) {
                    if (pre_process_result(r)) return false;
                    hideLoader();
                    json = jQuery.parseJSON(r);
                    $('.likes-count-' + l.data('id')).html(json.count);
                }
           });
           return false;
    });

    $(document).on('click', '.favourite-btn', function() {
        var l = $(this);
           if (l.hasClass('favourite')) {
              l.removeClass('favourite');
           } else {
                l.addClass('favourite')
           }
           showLoader();
           $.ajax({
                url : baseUrl + 'favourite/process',
                data : {id:l.data('id')},
                success : function(r) {
                    if (pre_process_result(r)) return false;
                    hideLoader();
                    json = jQuery.parseJSON(r);
                    notify(json.message, 'success');
                }
           });
           return false;
    });

    $(document).on('click', '.post-comment-btn', function() {
        var l = $(this);
        $(".comment-form-" + l.data('id')+ " input").focus();
        return false;
    });

    $(document).on('submit', '.comment-form form', function() {

        var form = $(this);
        id = form.data('id');
        if (form.find('input').val() == '') return false;
        showLoader();
        form.ajaxSubmit({
            url : baseUrl + 'comment/add',
            data : {id:id},
            success : function(c) {
                if (pre_process_result(c)) return false;
                hideLoader();
                $('.comments-lists-' + id).append(c);
                form.find('input').val('')
            }
        })
        return false;
    });

    $(document).on('click', '.comment-remove-btn', function() {
        var id = $(this).data('id');
         my_confirm(function() {
            $('.comment-' + id).remove();
            $.ajax({
                url : baseUrl + 'comment/remove',
                data :{id:id},
                success : function(r) {
                    if (pre_process_result(r)) return false;
                }
            })
         });
         return false;
    });
    $('.emoji-input').each(function() {
        var target = $(this);
        $(this).emojioneArea({
                    pickerPosition :'bottom',
                            shortnames : true,
                            saveEmojisAs : 'unicode',
                                                                         //standalone : true,
                    events: {
                            keyup: function (editor, e) {
                                //target.val(this.getText())
                                //e.stopPropagation();
                                //alert(this.getText());
                            },
                            click : function(editor,event) {
                                editor.focus();
                            }
                    }
        });

    });
    $(document).on('click', '.load-more-comment', function() {
        showLoader();
        l = $(this);
        id = $(this).data('id');
        offset = $(this).data('offset');
        container = $(".comments-lists-" + id);
        $.ajax({
            url : baseUrl + 'comment/more',
            data : {id:id,offset:offset},
            success: function(r) {
                r = jQuery.parseJSON(r);
                l.data('offset', r.offset);
                container.prepend(r.content);
                hideLoader();
                if (r.content == '')l.fadeOut();
            }
        })
        return false;
    });

    $(document).on('mouseover', '.post-grid', function() {
        $(this).find('.info').fadeIn();
    });

    $(document).on('mouseleave', '.post-grid', function() {
        $(this).find('.info').fadeOut();
    });

    $(document).on('click', '.follow-btn', function() {
        showLoader();
        id = $(this).data('id');
        $.ajax({
            url : baseUrl + 'follow/process?type=follow',
            data : {id:$(this).data('id')},
            success : function(r){
                if (pre_process_result(r)) return false;
                notify(r, 'success')
                hideLoader();
                $(".follow-btn-"+id).hide();
                $(".unfollow-btn-"+id).fadeIn();
            }
        })
        return false;
    });

    $(document).on('click', '.unfollow-btn', function() {
        showLoader();
        id = $(this).data('id');
        $.ajax({
            url : baseUrl + 'follow/process?type=unfollow',
            data : {id:$(this).data('id')},
            success : function(r){
                if (pre_process_result(r)) return false;
                notify(r, 'success')
                hideLoader();
                $(".follow-btn-"+id).fadeIn();
                $(".unfollow-btn-"+id).hide();
            }
        })
        return false;
    });


    $(document).on('submit', '.edit-caption-form', function(){
        form  = $(this);
        id = form.data('id');
        showLoader();

        form.ajaxSubmit({
            url : baseUrl + 'post/save',
            data : {id:id},
            success : function(r) {
                if (pre_process_result(r)) return false;
                notify(r, 'success');
                hideLoader();
                $('.caption-'+id).html(form.find('textarea').val());
                $('.caption-'+id).parent().fadeIn();
                $("#caption-edit-modal-" + id).modal('hide')
            }
        })
        return false;
    });

    window.postPaginating = false;
    $(window).scroll(function() {
       if($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
           if($('.post-list').length > 0 && !window.postPaginating) {
                var l = $('.post-list');
                window.postPaginating = true;
                showLoader();
                userid = l.data('userid');
                page = l.data('page');
                type = l.data('types');
                offset = l.data('offset');
                $.ajax({
                    url : baseUrl + 'post/load/more',
                    data :{userid:userid,page:page,type:type,offset:offset},
                    success : function(r){
                        if (pre_process_result(r)) return false;
                        hideLoader();
                        window.postPaginating = false;
                        json = jQuery.parseJSON(r);

                        $('.post-list').append(json.content);
                        $('.post-list').data('offset', json.offset);
                        if (type == 'list') {
                            setTimeout(function() {
                                d.fadeIn();
                            }, 500);
                        }
                        reload_init();
                    }
                })
           }
       }
    });

    setInterval(function() {
        check_events();

    }, checkTimeInterval);


    $(document).on('submit', '#newMessageForm', function() {
        var id = $(this).find('select').val();
        if (id != '') {
            open_message_dialog(id, true);
        }
        return false;
    });

    $(document).on("submit", '#chat-message-container form', function() {
        var form = $(this);
        form.css('opacity', '0.6');
        showLoader();
        form.ajaxSubmit({
            url : baseUrl + 'message/send',
            success : function(r) {
                if (pre_process_result(r)) return false;
                $("#chat-message-container .modal-body").append(r);
                form.css('opacity', 1);
                form.find('textarea').val('');
                form.find('textarea').data("emojioneArea").setText("");
                scroll_bottom("#message-lists");
                hideLoader();
            }
        })
        return false;
    });



    $(document).on('click', '.video-player-placeholder', function() {
        var holder = $(this);
        $.ajax({
            url : baseUrl + 'post/load/video?id='+holder.data('id'),
            success : function(d){
                holder.hide();
                holder.after(d);
                reload_init();
            }
        });
        return false;
    });

})