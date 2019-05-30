(() => {
    function create(tag) {
        return document.createElement(tag);
    }

    pageManager.register("config", function () {
        return {
            render: function (...args) {
                pageManager.setHeader("設定");
                setTimeout(() => {
                    pageManager.document.innerHTML = "config";
                    pageManager.setLoaderState(false)
                }, 500);
            }
        }
    });
})();
