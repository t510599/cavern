(() => {
    let listData = {
        page: -1,
        limit: -1,
        allPostCount: -1,
        username: undefined
    };

    async function fetchPost(mode, data) {
        let queryData = {
            method: "GET",
            url: "../ajax/posts.php"
        }

        switch (mode) {
            case "page":
                queryData.url += `?page=${data}`;
                if (listData["page"] != -1 && data > Math.ceil(listData['allPostCount'] / listData['limit'])) {
                    // we don't have that much post
                    pageManager.snackbar('頁數不存在');
                    router.navigate('/post');
                    return;
                }
                break;
            case "user":
                queryData.url += `?username=${data}`;
                pageManager.setHeader(`${data} 的文章`);
                break;
            case "pid":
                queryData.url += `?pid=${data}`;
                break;
            default:
                break;
        }

        try {
            let res = await axios.request(queryData);
            if (mode == "page") {
                listData.allPostCount = parseInt(res.data["all_posts_count"]);
                listData.page = parseInt(res.data["page"]);
                listData.limit = parseInt(res.data["page_limit"]);
            }

            if (mode == "pid") {
                let likeData = await axios.request({
                    url: `../ajax/like.php?pid=${data}&fetch`,
                    method: "GET"
                });
                renderPostPage(data, res.data.post, likeData.data);
            } else {
                renderPage(mode, res.data);
            }

        } catch (error) {
            console.error(error);
            if (error.response) {
                if (error.response.status == 404) {
                    pageManager.snackbar("找不到文章");
                }
            } else {
                pageManager.snackbar("讀取資料時發生錯誤。");
            }
        } finally {
            bindListener(mode);
            finish();
        }
    }

    function renderPage(mode, data) {
        let doc = pageManager.document;
        doc.innerHTML = "";

        var wrapper = create('div', "table wrapper");
        var table = create('table', "ts sortable celled striped table");

        let thead = create('thead');
        (["標題", "作者", "讚", "留言", "日期", "管理"]).forEach((e, _i) => {
            let th = create('th'); th.textContent = e;
            thead.appendChild(th);
        })
        table.appendChild(thead);

        var tbody = create('tbody');
        for (post of data["posts"]) {
            let tr = create("tr");
            (["title", "author", "likes_count", "comments_count", "time"]).forEach((e, _i) => {
                let td = create('td');
                if (e != "title") td.classList.add("collapsing");
                if (e.indexOf("count") != -1) td.classList.add("center", "aligned");

                if (e == "title") {
                    td.innerHTML = `<a href="/post/${post['pid']}" data-pid="${post['pid']}" data-navigo>${post[e]}</a>`;
                } else if (e == "author") {
                    td.innerHTML = `<a href="/post/user/${post[e]}" data-username="${post[e]}" data-navigo>${post[e]}</a>`;
                } else {
                    td.textContent = post[e];
                }
                tr.appendChild(td);
            });

            let action = `<td class="right aligned collapsing"><a class="ts negative circular icon button" href="./post/delete/${post["pid"]}" data-pid="${post['pid']}"><i class="trash icon"></i></a></td>`;
            tr.insertAdjacentHTML("beforeend", action);

            tbody.appendChild(tr);
        }

        table.appendChild(tbody);
        wrapper.appendChild(table);
        doc.appendChild(wrapper);

        if (mode === "page") doc.appendChild(pagination(listData["allPostCount"], listData["limit"], listData["page"]));
    }

    function renderPostPage(pid, data, likeData) {
        let doc = pageManager.document;
        doc.innerHTML = "";

        let segment = create('div', "ts clearfix primary segment");
        let title = create('div', "ts big header");
        let cardContainer = create('div', "ts stackable three cards");
        title.innerHTML = data["title"];
        title.insertAdjacentHTML("beforeend", `<div class="sub header"><a href="/user/${data["author"]}" data-navigo>${data["name"]}</a></div>`);
        segment.appendChild(title);

        data["length"] = data["content"].length;
        let iconName = {
            "length": "font",
            "likes_count": "like outline",
            "comments_count": "comments"
        };

        let statLabel = {
            "length": "文章字數",
            "likes_count": "獲得讚數",
            "comments_count": "留言數量"
        };

        let statTemplate = `<div class="ts left aligned statistic"><div class="value">{{ value }}</div><div class="label">{{ label }}</div></div>`;
        Object.keys(iconName).forEach((e, _i) => {
            let card = create('div', "ts card");
            let content = create('div', "content");
            let symbol = create('div', "symbol");
            let icon = create('i', `${iconName[e]} icon`);

            content.innerHTML = statTemplate.replace("{{ value }}", data[e]).replace("{{ label }}", statLabel[e]);
            symbol.appendChild(icon);
            card.appendChild(content);
            card.appendChild(symbol);

            cardContainer.appendChild(card);
        });
        segment.appendChild(cardContainer);

        let likersDetail = create('details', "ts accordion");
        let detailContent = create('div', "content");
        likersDetail.insertAdjacentHTML("beforeend", `<summary><i class="dropdown icon"></i> 按讚的人</summary>`);
        let likersList = create('div', "ts link list");
        for (let username of likeData["likers"]) {
            let item = create('a', "item");
            item.href = `/user/${username}`;
            item.setAttribute("data-navigo", "");
            item.textContent = username;
            likersList.appendChild(item);
        }
        if (!likeData["likers"].length) {
            likersList.insertAdjacentHTML("beforeend", `<div class="item">(無)</div>`);
        }
        detailContent.appendChild(likersList);
        likersDetail.appendChild(detailContent);
        segment.appendChild(likersDetail);

        let actionsContainer = create('div', "ts separated right floated buttons");
        let actionClass = {
            "編輯": "basic",
            "預覽": "primary",
            "刪除": "negative"
        };
        let actionParams = {
            "編輯": `?edit=${pid}`,
            "預覽": `?pid=${pid}`,
            "刪除": `?del=${pid}`
        };
        Object.keys(actionClass).forEach((e, _i) => {
            let button = create('a', "ts button");
            button.classList.add(actionClass[e]);
            button.target = "_blank";
            button.href = "../post.php" + actionParams[e];
            button.textContent = e;
            actionsContainer.appendChild(button);
        });
        segment.appendChild(actionsContainer);
        doc.appendChild(segment);
        if (listData.username) {
            var backHref = `/post/user/${listData.username}`;
        } else {
            var backHref = `/post/page/${(listData["page"] > -1 ? listData["page"] : 1)}`;
        }
        doc.insertAdjacentHTML("beforeend", `<a class="ts button" href="${backHref}" data-navigo>返回列表</a>`);
    }

    function pagination(all, limit, currentPage) {
        let totalPage = Math.ceil(all / limit);
        let pagination = create('div'); pagination.className = "ts basic center aligned padded segment";
        let buttons = create('div'); buttons.className = "ts small separated buttons";

        if (currentPage > 1) buttons.innerHTML += `<a href="/post/page/${currentPage - 1}" class="ts icon button" data-navigo><i class="chevron left icon"></i></a>`;
        buttons.innerHTML += `<div class="ts basic button">${currentPage} / ${totalPage}</div>`;
        if (currentPage != totalPage) buttons.innerHTML += `<a href="/post/page/${currentPage + 1}" class="ts icon button" data-navigo><i class="chevron right icon"></i></a>`;

        pagination.appendChild(buttons);
        return pagination;
    }

    function bindListener(mode) {
        if (mode == "page" || mode == "user") {
            var selector = "tbody";
        } else if (mode == "pid") {
            var selector = ".ts.primary.segment";
        }

        $(selector).on('click', 'a.negative', function(e) {
            e.preventDefault();

            let el = e.currentTarget;
            let pid = el.dataset.pid;
            showModal(pid);
        });

        function showModal(pid) {
            swal({
                type: 'question',
                title: '確定要刪除嗎?',
                showCancelButton: true,
                confirmButtonText: '確定',
                cancelButtonText: '取消'
            }).then((result) => {
                if (result.value) { // confirm
                    axios.request({
                        method: "GET",
                        url: "../post.php?del=" + pid
                    }).then(function (res) {
                        pageManager.snackbar('刪除成功');
                        pageManager.load("post", "page", listData.page); // reload
                    }).catch(function (err) {
                        switch (err.response.status) {
                            case 404:
                                pageManager.snackbar('找不到文章');
                            case 403:
                                pageManager.snackbar('權限不足');
                        }
                    });
                }
            });
        }
    }

    function finish() {
        ts('table').tablesort();
        router.updatePageLinks();
        setTimeout(() => {
            pageManager.setLoaderState(false)
        }, 1000);
    }

    function create(tag, className="") {
        let el = document.createElement(tag);
        el.className = className;
        return el;
    }

    pageManager.register("post", function () {
        return {
            render: function (...args) {
                pageManager.setHeader("文章");
                fetchPost(...args);
            }
        }
    });
})();