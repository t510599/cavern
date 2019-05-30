const cdnjs = "https://cdnjs.cloudflare.com/ajax/libs";

// Load Libraries
const libraries = [
    cdnjs + "/marked/0.5.1/marked.min.js",
    cdnjs + "/prettify/r298/prettify.min.js",
    cdnjs + "/underscore.js/1.9.1/underscore-min.js"
];

loadJS(libraries).then(function () {
    editormd.$marked = marked;
    editormd.loadFiles.js.push(...libraries.map(url => url.slice(0, -3))); // remove ".js"
    document.querySelectorAll('.ts.card .description').forEach(function(el) {
        let id = el.getAttribute('id');
        parseMarkdown(id, el.children[0].textContent, {
            toc: false,
            flowChart: false,
            sequenceDiagram: false,
            htmlDecode : "script,iframe,style|on*"
        }).children('.markdown').hide();
    });
    postProcess();
    setTimeout(function () {
        // show cards
        $('.loading#cards').removeClass('loading');
        $('#content .active.loader').removeClass('active');
    }, 500);
});