let edtior;
editormd.urls = {
    atLinkBase : "user.php?username="
};

// create editor instance
editor = editormd('markdownEditor', {
    height: 450,
    path: "https://pandao.github.io/editor.md/lib/",
    markdown: document.edit.content.value,
    htmlDecode : "script,iframe|on*",
    placeholder: '',
    styleActiveLine: false,
    "font-size": '14px',
    emoji: true,
    taskList: true,
    tex: true,
    flowChart: true,
    sequenceDiagram: true,
    watch: false,
    lineNumbers: false,
    lineWrapping: false,
    toolbarAutoFixed: false,
    toolbarIcons : function() {
        return [
            "search", "|",
            "undo", "redo", "|",
            "bold", "del", "italic", "|",
            "list-ul", "list-ol", "emoji", "html-entities", "|",
            "link", "image", "|",
            "preview", "fullscreen", "||",
            "help", "info", 
        ]
    },
    toolbarIconsClass: {
        preview: 'fa-eye'
    },
    onload: function() {
        var __this__ = this;
        $('ul.editormd-menu').addClass('unstyled'); // remove style of TocasUI
        $('ul.editormd-menu i[name="emoji"]').parent().click(function () { // remove style of TocasUI from emoji tab (hack)
            setTimeout(()=>{ $('ul.editormd-tab-head').addClass('unstyled'); }, 300);
        });
        this.resize();
        loadDraft();

        document.edit.title.addEventListener("keydown", function () {
            saveDraft(__this__);
        })

        this.cm.on("change", function(_cm, _changeObj) {
            saveDraft(__this__);
        });
    },
    onresize: function() {
        if (this.state.preview) {
            requestAnimationFrame(()=>{
                this.previewed();
                this.previewing();
            });
        }
    },
    onpreviewing: function() {
        // use tocas-ui style tables
        $('table').each((_i,e) => {
            $(e).addClass('ts celled table').css('display','table');
        });

        // prevent user from destroying page style
        var parser = new cssjs();
        let stylesheets = document.querySelectorAll('.markdown-body style');
        for (let style of stylesheets) {
            let ruleSource = style.innerHTML;
            let cssObject = parser.parseCSS(ruleSource);
            for (let rule of cssObject) {
                let valid = false;
                let validPrefix = [".markdown-body ", ".editormd-preview-container ", ".markdown-body.editormd-preview-container ", ".editormd-preview-container.markdown-body "];
                validPrefix.forEach((e, _i) => {
                    valid = valid || rule.selector.startsWith(e);
                });

                if (!rule.selector.startsWith('@')) { // '@keyframe' & '@import'
                    if (!valid) {
                        rule.selector = ".editormd-preview-container " + rule.selector;
                    }
                }
            }
            style.innerHTML = parser.getCSSForEditor(cssObject);
        }
    }
});

// save draft data
function saveDraft(editor) {
    localStorage.setItem('cavern_draft_title', document.edit.title.value);
    localStorage.setItem('cavern_draft_id', document.edit.pid.value);
    localStorage.setItem('cavern_draft_content', editor.getMarkdown());
    localStorage.setItem('cavern_draft_time', new Date().getTime());
}

// Ask if user want to load draft
function loadDraft() {
    if ($('#pid').val() == localStorage.getItem('cavern_draft_id')) {
        swal({
            type: 'question',
            title: '要載入上次備份嗎？',
            showCancelButton: true,
            confirmButtonText: '載入',
            cancelButtonText: '取消',
        }).then((result) => {
            if (result.value) { // confirm
                document.edit.title.value = localStorage.getItem('cavern_draft_title');
                editor.setValue(localStorage.getItem('cavern_draft_content'));
            }
        });
    }
}

// Post an article
$(document.edit).on('submit', function(e) {
    e.preventDefault();
    var _this = this;
    axios.request({
        method: "POST",
        data: new FormData(this),
        url: "post.php",
        headers: {
            'Content-Type': "application/x-www-form-urlencoded"
        }
    }).then(function (res) {
        if (_this.pid.value == localStorage.getItem('cavern_draft_id')) {
            ['id', 'title', 'content', 'time'].forEach((name) => {
                localStorage.removeItem(`cavern_draft_${name}`);
            });
        }
        location.href = res.headers["axios-location"];
    }).catch(function (error) {
        if (error.response) {
            location.href = error.response.headers["axios-location"];
        }
    });
});

// Delete post confirm message
$('.action.column .delete').on('click', function(e) {
    e.preventDefault();
    var el = this;
    var href = el.getAttribute('href');
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
                url: href
            }).then(function (res) {
                location.href = res.headers["axios-location"];
            }).catch(function (error){
                if (error.response) {
                    location.href = error.response.headers["axios-location"];
                }
            });
        }
    });
});