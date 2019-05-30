(() => {
    function fetchStatistics() {
        axios.request({
            method: "GET",
            url: "ajax/statistics.php"
        }).then(function (res) {
            renderPage(res.data);
        }).catch(function (err) {
            if (err.response) {
                console.error(err.response.status);
            }
        });
    }

    function renderPage(data) {
        let iconName = {
            "post": "file text",
            "user": "users",
            "comment": "comments"
        };

        let statLabel = {
            "post": "文章總數",
            "user": "使用者總數",
            "comment": "留言總數"
        };

        let statTemplate = `<div class="ts left aligned statistic"><div class="value">{{ value }}</div><div class="label">{{ label }}</div></div>`

        let cardContainer = create('div'); cardContainer.className = "ts stackable three cards";

        for (let key in data) {
            if (Object.keys(statLabel).indexOf(key) != -1) {
                let card = create('div'); card.className = "ts card";
                let content = create('div'); content.className = "content";
                let symbol = create('div'); symbol.className = "symbol";
                let icon = create('i'); icon.className = `${iconName[key]} icon`;

                content.innerHTML = statTemplate.replace("{{ value }}", data[key]).replace("{{ label }}", statLabel[key]);
                symbol.appendChild(icon);
                card.appendChild(content);
                card.appendChild(symbol);

                cardContainer.appendChild(card);
            } else if (key === "name") {
                // blog name
            }
        }

        // finish up
        setTimeout(() => {
            pageManager.document.innerHTML = "";
            pageManager.document.appendChild(cardContainer);
            pageManager.setLoaderState(false)
        }, 500);
    }

    function create(tag) {
        return document.createElement(tag);
    }

    pageManager.register("statistics", function () {
        return {
            render: function (...args) {
                pageManager.setHeader("總覽");
                fetchStatistics();
            }
        }
    });
})();
