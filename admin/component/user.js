(() => {
    function create(tag) {
        return document.createElement(tag);
    }

    pageManager.register("user", function () {
        return {
            render: function (...args) {
                pageManager.setHeader("使用者");
                setTimeout(() => {
                    pageManager.document.innerHTML = args[0];
                    pageManager.setLoaderState(false)
                }, 500);
            }
        }
    });
})();
