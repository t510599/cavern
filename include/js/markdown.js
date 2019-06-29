editormd.urls = {
    atLinkBase : "user.php?username="
};

function postProcess(...callbacks) {
    tableStyling();
    linkSanitize();
    
    callbacks.forEach(func => {
        func();
    });
    
    function linkSanitize() {
        $('.markdown-body a').each((_i, e) => {
            href = (e.href) ? _.unescape(e.href.toLowerCase()) : "";
            if (href.indexOf('javascript:') != -1) {
                e.href = '#';
            } else {
                e.href = e.href.replace(/%40/, '@');
            }
        });
    }

    function tableStyling() {
        $('table').each((_i,e) => {
            $(e).addClass("ts celled table").css('display', 'table').wrap('<div class="table wrapper"></div>');
        });
    }
}

function parseMarkdown(id, markdown, options) {
    let defaultOptions = {
        htmlDecode : "script,iframe|on*",
        toc: true,
        emoji: true,
        taskList: true,
        tex: true,
        flowChart: true,
        sequenceDiagram: true
    }
    return editormd.markdownToHTML(id, $.extend(true, defaultOptions, options, { markdown: markdown }));
}