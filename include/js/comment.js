const pid = $('#post').attr('data-id');

var post = { // post cache
    fetchTime: undefined,
    comments: [],
    idList: [] // ids of comment
}

// Fetch
$('.fetch.button').click(function(_e) {
    fetchComments();
});

var fetchTimer = setInterval(fetchComments, 5*60*1000); // polling comments per 5 minutes

function fetchComments() {
    $('.ts.inline.loader').addClass('active');
    if (!pid) {
        console.error('An error occurred while fetching comments.');
        snackbar("無法載入留言。");
        return undefined;
    }

    if (post.fetchTime) document.cookie = `cavern_commentLastFetch=${post.fetchTime}; Max-Age=10`;

    axios.request({
        method: "GET",
        url: `ajax/comment.php?pid=${pid}`,
        responseType: "json"
    }).then(function (res) {
        let data = res.data;
        let t = new Date(data.fetch);
        post.fetchTime = Math.ceil(data.fetch / 1000); // php timestamp
        $('span.fetch.time').text(`Last fetch: ${t.getHours() < 10 ? '0' + t.getHours() : t.getHours()}:${ t.getMinutes() < 10 ? '0' + t.getMinutes() : t.getMinutes() }`);
        parseComments(data);
    }).catch(function (error) {
        if (error.response) {
            let res = error.response;
            console.error(`An error occurred while fetching comments of pid ${pid}, status ${res.status}`);
        } else {
            console.error(`An error occurred while fetching comments of pid ${pid}`);
        }
        snackbar("無法載入留言。");
    });
    setTimeout(() => { $('.ts.inline.loader').removeClass('active'); }, 250);
}

function parseComments(data) {
    const commentTemplate = `<div class="comment" id="comment-{{ id }}" data-comment="{{ id }}"><a class="avatar" href="user.php?username={{ username }}"><img src="https://www.gravatar.com/avatar/{{ hash }}?d=https%3A%2F%2Ftocas-ui.com%2Fassets%2Fimg%2F5e5e3a6.png"></a><div class="content"><a class="author" href="user.php?username={{ username }}">{{ name }}</a><div class="middoted metadata"><div class="time">{{ time }}</div></div><div class="text" id="markdown-comment-{{ id }}"></div></div></div>`;

    let add = data.idList.filter(function(item) { return post.idList.indexOf(item) < 0 }); // id list of new comments
    let remove = post.idList.filter(function(item) { return data.idList.indexOf(item) < 0 }); // id list of removed comments

    for (postId of remove) {
        $(`.ts.comments div[data-comment="${postId}"]`).remove();
    }

    for (c of data.comments) {
        if (add.indexOf(c.id) == -1 && data.modified.indexOf(c.id) == -1) {
            continue;
        }

        if (add.indexOf(c.id) != -1) {
            // render new comment
            let node = commentTemplate.replace(/{{ id }}/gm, c.id).replace('{{ time }}', c.time).replace(/{{ username }}/gm, c.username).replace('{{ name }}', data.names[c.username]).replace('{{ hash }}', data.hash[c.username]);
            $(node).appendTo('.ts.comments');
            if (c.actions.length != 0) {
                let actions = document.createElement('div');
                actions.className = "actions";
                $(`div[data-comment="${c.id}"] .content`).append(actions);
                for (act of c.actions) {
                    switch (act) {
                        case "reply":
                            actions.insertAdjacentHTML('beforeend',`<a class="reply" data-username="${c.username}">回覆</a>`);
                            break;
                        case "del":
                            actions.insertAdjacentHTML('beforeend',`<a class="delete" data-comment="${c.id}">刪除</a>`);
                            break;
                        case "edit":
                            actions.insertAdjacentHTML('beforeend',`<a class="edit" data-comment="${c.id}">編輯</a>`);
                            break;
                        default:
                            break;
                    }
                }
            }
        } else if (data.modified.indexOf(c.id) != -1) {
            // empty the old content
            $(`#markdown-comment-${c.id}`).html('');
        }

        if (c.modified) {
            let $metadata = $(`div[data-comment="${c.id}"] .metadata`);
            if ($metadata.children('.modified').length) {
                $metadata.children('.modified').attr('title', c.modified);
            } else {
                $metadata.append(`<div class="modified" title="${c.modified}">已編輯</div>`);
            }
        }

        parseMarkdown(`markdown-comment-${c.id}`, _.unescape(c.markdown), {
            toc: false
        });
    }

    post.comments = data.comments; // cache data
    post.idList = data.idList; // cache data
    postProcess();

    /* jump to the comment and emphasize it */
    if (location.hash && location.hash.startsWith("#comment-")) {
        let commentID = location.hash;
        if (!$(commentID).length) {
            snackbar("留言已刪除或是不存在。")
        } else {
            $(window).scrollTop($(commentID).offset().top - $('.comment.header').outerHeight() - 10);
            $(commentID).addClass('emphasized');
        }
    }
    
    if (data.idList.length == 0) {
        $('.ts.no-comment.segment').addClass('active');
    } else {
        $('.ts.no-comment.segment').removeClass('active');
    }
}

// Comment Editor & Preview
(function () {
    let commentContainer = document.querySelector('#comment');
    let textarea = commentContainer.querySelector('textarea');
    $('.ts.tabbed.menu a.item[data-tab="preview"]').click(function() {
        let comment = textarea.value;
        if (comment.trim() != '') {
            // reset the container
            $('#preview').html('');
            parseMarkdown('preview', comment, {
                toc: false
            });
            postProcess();
        } else {
            $('#preview').html('Nothing to preview!');
        }
    });
    
    $('#comment textarea').keydown(function (e) {
        if (e.ctrlKey && (e.keyCode == 10 || e.keyCode == 13)) { // Ctrl-Enter pressed; Chrome: keyCode == 10
            document.querySelector('#comment div[data-tab="textarea"] button.submit.positive').click(); // send comment
        }
    });
    
    // Edit
    $('.ts.comments').on('click', '.edit', function(e) {
        if (!textarea.disabled) {
            let el = e.currentTarget;
            let id = el.dataset.comment;
            editorEditComment(textarea, id);
        } else {
            snackbar("你已被禁言。");
        }
    });

    // Reply
    $('.ts.comments').on('click', '.reply', function(e) {
        if (!textarea.disabled) {
            let el = e.currentTarget;
            textarea.value += ` @${el.dataset.username} `;
            textarea.focus();
        } else {
            snackbar("你已被禁言。");
        }
    });

    function editorInitialize(edtior) {
        delete commentContainer.dataset.editId;
        if ($('#comment .action.buttons button.cancel').length) {
            $('#comment .action.buttons button.cancel').remove();
        }
        if ($('#comment .menu .indicator').length) {
            $('#comment .menu .indicator').remove();
        }
        edtior.value = ""; // empty the textarea
    }

    function editorEditComment(editor, commentId) {
        if (post.idList.indexOf(commentId) == -1) {
            snackbar('留言已刪除。');
            return undefined;
        }
        commentContainer.dataset.editId = commentId;
        if (!$('#comment .action.buttons button.cancel').length) {
            let cancelButton = document.createElement('button');
            cancelButton.classList.add('ts', 'cancel', 'button');
            cancelButton.innerText = "取消";
            commentContainer.querySelector('.action.buttons').appendChild(cancelButton);
            cancelButton.addEventListener('click', function () {
                editorInitialize(editor);
            });
        }
        if (!$('#comment .menu .indicator').length) {
            let indicator = document.createElement('div');
            indicator.classList.add('right', 'indicator', 'item');
            indicator.innerText = `Editing: ${commentId}`;
            commentContainer.querySelector('.menu').appendChild(indicator);
        } else {
            $('#comment .menu .indicator').text(`Editing: ${commentId}`);
        }
        editor.value = _.unescape(post.comments[post.idList.indexOf(commentId)].markdown);
        editor.focus();
    }

    // Send Comment
    let commentLock = false;
    const commentRate = 10; // 1 comment per 10 seconds

    $('#comment div[data-tab="textarea"] button.submit.positive').click(function() {
        var _this = this;
        let content = textarea.value;
        if (content.trim() == "") {
            snackbar("留言不能為空！");
            return false;
        }
        if (commentLock) {
        	if (!commentContainer.dataset.editId) {
                snackbar(`每 ${commentRate} 秒只能發一則留言。`);
                return false;
            }
        } else if (!commentContainer.dataset.editId) {
            // only new comment should be limited
            commentLock = true;
        }

        if (commentContainer.dataset.editId) {
            // edit comment
            var commentData = new URLSearchParams({
                "edit": commentContainer.dataset.editId,
                "content": content
            }).toString();
        } else {
            // new comment
            var commentData = new URLSearchParams({
                "pid": pid,
                "content": content
            }).toString();
        }

        axios.request({
            method: "POST",
            url: "ajax/comment.php",
            data: commentData,
            responseType: "json"
        }).then(function (res) {
            editorInitialize(textarea);
            console.log(`Comment sent succeessfully! Comment id is ${res.data["comment_id"]}`);
            setTimeout(function() { commentLock = false }, commentRate * 1000); // sec -> microsecond
            fetchComments();
        }).catch(function (error) {
            commentLock = false; // unlock the textarea
            if (error.response) {
                let res = error.response;
                let data = res.data;
                console.error(`An error occurred while sending comments of pid ${pid}, status ${res.status}`);
                switch (data.status) {
                    case "empty":
                        snackbar("留言不能為空！");
                        break;
                    case "ratelimit":
                        let remainSeconds = res.headers['retry-after'];
                        snackbar(`每 ${commentRate} 秒只能發一則留言。請 ${remainSeconds} 秒後再試！`);
                        break;
                    case "muted":
                        snackbar("你已被禁言。");
                        $('#comment .ts.fluid.input').addClass('disabled');
                        $(textarea).attr("placeholder", "你被禁言了。").val(""); // empty the textarea
                        $(_this).addClass('disabled').text("你被禁言了");
                        break;
                    case "author":
                        snackbar("你不能編輯別人的留言！");
                        break;
                    case "nologin":
                        snackbar("請先登入。");
                        break;
                    default:
                        snackbar("發送失敗。");
                        break;
                }
                fetchComments();
            } else {
                console.error(`An error occurred while sending comments of pid ${pid}`);
            }
        });
    });
})();

// Delete
$('.ts.comments').on('click', '.delete', function(e) {
    let el = e.currentTarget;
    let id = el.dataset.comment;
    swal({
        type: 'question',
        title: '確定要刪除嗎?',
        showCancelButton: true,
        confirmButtonText: '確定',
        cancelButtonText: '取消',
    }).then((result) => {
        if (result.value) { // confirm
            axios.request({
                method: "GET",
                url: "ajax/comment.php?del=" + id,
                responseType: "json"
            }).then(function (_res) {
                fetchComments();
            }).catch(function (error) {
                if (error.response) {
                    let res = error.response;
                    console.error(`An error occurred while deleting comment of id ${id}, status ${res.status}`);
                } else {
                    console.error(`An error occurred while deleting comment of id ${id}`);
                }
                snackbar('刪除失敗。');
            });
        }
    });
});

function snackbar(message) {
    ts('.snackbar').snackbar({
        content: message
    });
}
