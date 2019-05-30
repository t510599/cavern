const cdnjs = "https://cdnjs.cloudflare.com/ajax/libs";

// Load Libraries
const libraries = [
    cdnjs + "/marked/0.5.1/marked.min.js",
    cdnjs + "/prettify/r298/prettify.min.js",
    cdnjs + "/raphael/2.2.7/raphael.min.js",
    cdnjs + "/underscore.js/1.9.1/underscore-min.js",
    cdnjs + "/flowchart/1.11.3/flowchart.min.js",
    "https://pandao.github.io/editor.md/lib/jquery.flowchart.min.js",
    cdnjs + "/js-sequence-diagrams/1.0.6/sequence-diagram-min.js"
];

loadJS(libraries).then(function () {
    editormd.$marked = marked;
    editormd.loadFiles.js.push(...libraries.map(url => url.slice(0, -3))); // remove ".js"
    parsePost();
    fetchComments();
    postProcess(sanitizeStyleTag());

    function sanitizeStyleTag() { // prevent the style tag in post from destorying the style of page
        return function() {
            var parser = new cssjs();
            let stylesheets = document.querySelectorAll('#post style');
            for (let style of stylesheets) {
                let ruleSource = style.innerHTML;
                let cssObject = parser.parseCSS(ruleSource);
                for (let rule of cssObject) {
                    let valid = false;
                    let validPrefix = ["#post ", "#post.markdown-body ", "#post.editormd-html-preview "];
                    validPrefix.forEach((e, _i) => {
                        valid = valid || rule.selector.startsWith(e);
                    });

                    if (!rule.selector.startsWith('@')) { // '@keyframe' & '@import'
                        if (valid) {
                            // do nothing
                        } else if (rule.selector.startsWith('.markdown-body ') || rule.selector.startsWith(".editormd-html-preview")) {
                            rule.selector = "#post" + rule.selector;
                        } else {
                            rule.selector = "#post " + rule.selector;
                        }
                    }
                }
                style.innerHTML = parser.getCSSForEditor(cssObject);
            }
        }
    }
});

function parsePost() {
    var postContent = document.querySelector('#post .markdown').textContent;

    if (postContent.search(/.{0}\[TOC\]\n/) != -1) { // if TOC is used in post
        $('#sidebar .ts.fluid.input').after(`<div class="ts tertiary top attached center aligned segment">目錄</div><div class="ts bottom attached loading segment" id="toc"></div>`);
    }

    parseMarkdown('post', postContent, {
        tocDropdown: false,
        tocContainer: '#toc'
    }).children('.markdown').hide();
    $('#toc').removeClass('loading');
}

// Delete post confirm message
$('.action.column .delete').on('click', function(e) {
    e.preventDefault();
    var el = this;
    var next = el.getAttribute('href');
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
                url: next
            }).then(function (res) {
                location.href = res.headers["axios-location"];
            });
        }
    });
});