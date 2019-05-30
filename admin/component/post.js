(() => {
    function create(tag) {
        return document.createElement(tag);
    }

    pageManager.register("post", function () {
        return {
            render: function (...args) {
                pageManager.setHeader("文章");
                setTimeout(() => {
                    pageManager.document.innerHTML = args[0] + args[1];
                    pageManager.setLoaderState(false)
                }, 500);
            }
        }
    });
})();
