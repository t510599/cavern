(() => {
    function fetchStatistics() {
        axios.request({
            method: "GET",
            url: "ajax/statistics.php"
        }).then(function (res) {
            renderPage(res.data);
        }).catch(function (err) {
            if (err.response) {
                switch (err.response.data.status) {
                    case "nopermission":
                        pageManager.snackbar("沒有權限，請確認登入狀態");
                        break;
                    default:
                        pageManager.snackbar("發生錯誤");
                        console.error(err);
                        break;
                }
            }
            setTimeout(() => {
                pageManager.setLoaderState(false);
            }, 1000);
        });
    }

    function renderPage(data) {
        let doc = pageManager.document;

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

        let overviewSegment = create('div', "ts primary segment");
        let blogNameContainer = create('div', "ts large header");
        let cardContainer = create('div', "ts stackable three cards");
        let statTemplate = `<div class="ts left aligned statistic"><div class="value">{{ value }}</div><div class="label">{{ label }}</div></div>`

        for (let key in data) {
            if (Object.keys(statLabel).indexOf(key) != -1) {
                let card = create('div', "ts card");
                let content = create('div', "content");
                let symbol = create('div', "symbol");
                let icon = create('i', `${iconName[key]} icon`);

                content.innerHTML = statTemplate.replace("{{ value }}", data[key]).replace("{{ label }}", statLabel[key]);
                symbol.appendChild(icon);
                card.appendChild(content);
                card.appendChild(symbol);

                cardContainer.appendChild(card);
            } else if (key === "name") {
                // blog name
                blogNameContainer.textContent = data[key];
            }
        }

        // finish up
        setTimeout(() => {
            doc.innerHTML = "";
            overviewSegment.appendChild(blogNameContainer);
            overviewSegment.appendChild(cardContainer);
            doc.appendChild(overviewSegment);
            pageManager.setLoaderState(false)
        }, 1000);
    }

    function create(tag, className="") {
        let el = document.createElement(tag);
        el.className = className;
        return el;
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
