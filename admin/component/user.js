(() => {
    async function fetchUser(mode, data="") {
        try {
            switch (mode) {
                case "list":
                    pageManager.setHeader("使用者列表");
                    var res = await axios.request({
                        url: "ajax/user.php",
                        method: "GET"
                    });
                    renderList(res.data);
                    break;
                case "username":
                    pageManager.setHeader("修改資料");
                    var res = await axios.request({
                        url: "../ajax/user.php?username=" + data,
                        method: "GET"
                    });
                    renderForm(mode, res.data)
                    break;
                case "add":
                    pageManager.setHeader("新增使用者");
                    renderForm(mode);
                    break;
            }
        } catch (e) {
            switch (e.response.data.status) {
                case "nopermission":
                    pageManager.snackbar("沒有權限，請確認登入狀態");
                    break;
                default:
                    pageManager.snackbar("發生錯誤");
                    console.error(e);
                    break;
            }
        }
        bindListener(mode);
        setTimeout(() => {
            pageManager.setLoaderState(false)
        }, 1000);
    }

    function renderList(data) {
        let doc = pageManager.document;
        doc.innerHTML = "";

        var wrapper = create('div', "table wrapper");
        var table = create('table', "ts sortable celled striped table");

        let thead = create('thead');
        (["帳號", "暱稱", "信箱", "權限", "禁言", "管理"]).forEach((e, _i) => {
            let th = create('th'); th.textContent = e;
            thead.appendChild(th);
        })
        table.appendChild(thead);

        var tbody = create('tbody');
        for (user of data["list"]) {
            let tr = create("tr");
            (["username", "name", "email", "role", "muted"]).forEach((e, _i) => {
                let td = create('td');
                if (e != "name") td.classList.add("collapsing");

                if (e == "muted") {
                    td.classList.add("center", "aligned");
                    if (user[e]) td.innerHTML = `<i class="negative ban icon"></i>`;
                } else if (e == "email") {
                    td.innerHTML = `<a href="mailto:${user[e]}">${user[e]}</a>`;
                } else {
                    td.textContent = user[e];
                }
                tr.appendChild(td);
            });

            let action = `<td class="left aligned collapsing">` +
                         `<a class="ts circular icon button" href="/user/${user['username']}" data-navigo><i class="pencil icon"></i></a>`;
            if (user.level != 9) {
                action += `<a class="ts negative circular icon button" href="./users/delete/${user['username']}" data-username="${user['username']}"><i class="user delete icon"></i></a>`;
            }
            action += `</td>`;
            tr.insertAdjacentHTML("beforeend", action);

            tbody.appendChild(tr);
        }

        table.appendChild(tbody);
        wrapper.appendChild(table);
        doc.appendChild(wrapper);
    }

    function renderForm(mode, data={}) {
        let doc = pageManager.document;
        doc.innerHTML = "";

        let fieldName = {
            "username": "帳號",
            "name": "暱稱",
            "email": "信箱",
            "password": "密碼",
            "role": "權限",
            "muted": "禁言"
        };

        let levelRole = {
            0: "會員",
            1: "作者",
            8: "管理員",
            9: "站長"
        };

        let formContainer = create('div');  formContainer.className = "ts form";
        let form = create('form'); form.method = "POST"; form.action = "ajax/user.php"; form.name = "config"; form.id = "config"; form.className = "clearfix";
        form.autocomplete = "new-password";

        if (mode == "username") {
            var avatarSrc = `https://www.gravatar.com/avatar/${data["hash"]}?d=https%3A%2F%2Ftocas-ui.com%2Fassets%2Fimg%2F5e5e3a6.png&amp;s=500`;
            form.insertAdjacentHTML("beforeend", `<div class="field">` +
                `<label>頭貼</label><div class="ts center aligned flatted borderless segment">` +
                `<img src="${avatarSrc}" class="ts rounded image" id="avatar"></div></div>`
            );
        }

        Object.keys(fieldName).forEach((e, _i) => {
            let field = create('div', "field");
            let input;
            let fieldLabel = create('label');
            fieldLabel.textContent = fieldName[e];

            if (e == "role") {
                input = create('select'); input.name = e;
                [0, 1, 8, 9].forEach((opt, _i) => {
                    let option = create('option');
                    option.value = opt;
                    option.textContent = levelRole[opt];
                    if (data["level"] == opt) {
                        option.selected = "selected";
                    }
                    input.appendChild(option);
                });
            } else if (e == "password") {
                input = create('input'); input.name = e; input.type = "password";
            } else if (e == "muted") {
                input = create('div', "ts toggle checkbox");
                let checkbox = create('input'); checkbox.id = "muted"; checkbox.type = "checkbox";
                checkbox.value = "on";
                let label = create('label'); label.setAttribute("for", "muted");
                if (data[e]) {
                    checkbox.checked = "checked";
                }
                input.appendChild(checkbox);
                input.appendChild(label);
            } else {
                input = create('input'); input.name = e; input.type = "text";
                if (mode == "username") {
                    input.value = data[e];
                }
            }

            if (e == "username") {
                if (mode == "username") {
                    field.classList.add("disabled");
                } else {
                    field.classList.add("required");
                }
            }

            input.autocomplete = "off";

            field.appendChild(fieldLabel);
            field.appendChild(input);
            form.appendChild(field);
        });
        
        form.insertAdjacentHTML("beforeend", `<input class="ts right floated primary button" value="送出" type="submit">`);

        formContainer.appendChild(form);
        doc.appendChild(formContainer);
    }

    function sendData(mode, form) {
        let data = new FormData(form);
        if (form.muted.checked) {
            data.append("muted", "1");
        }
        let fd = new URLSearchParams(data).toString();
        axios.request({
            method: (mode == "add" ? "POST" : "PATCH"),
            url: "ajax/user.php",
            data: fd
        }).then((res) => {
            pageManager.snackbar("操作成功!");
            router.navigate("/user");
        }).catch((err) => {
            let code = err.response.data.status;
            let messages = {
                // user
                "userexists": "使用者已經存在",
                "nouser": "使用者不存在",
                // username
                "nousername": "請填寫帳號",
                "badusername": "帳號格式不符合",
                // name
                "noname": "請填寫暱稱",
                "badname": "暱稱格式不符合",
                // password
                "nopassword": "請填寫新帳號密碼",
                // email
                "noemail": "請填寫信箱",
                "bademail": "信箱格式不符合",
                "emailused": "信箱已被其他使用者使用",
                "lowlevel": "權限不足"
            }

            if (Object.keys(messages).includes(code)) {
                pageManager.snackbar(messages[code]);
            } else {
                pageManager.snackbar("發生錯誤");
            }
        });
    }

    function deleteUser(username) {
        axios.request({
            method: "DELETE",
            url: "ajax/user.php?username=" + username
        }).then((res) =>{
            pageManager.snackbar("刪除成功。");
            pageManager.load("user", "list");
        }).catch((err) => {
            switch (err.response.data.status) {
                case "nouser":
                    pageManager.snackbar("使用者不存在");
                    break;
                case "deleteowner":
                    pageManager.snackbar("你不能刪掉站長!");
                    break;
                default:
                    pageManager.snackbar("刪除使用者時發生錯誤")
                    break;
            }
        });
    }

    function bindListener(mode) {
        router.updatePageLinks();
        if (mode == "list") {
            $('tbody').on('click', 'a.negative', function(e) {
                e.preventDefault();
    
                let el = e.currentTarget;
                let username = el.dataset.username;
                showModal(username);
            });
    
            function showModal(username) {
                swal({
                    type: 'question',
                    title: '確定要刪除嗎?',
                    showCancelButton: true,
                    confirmButtonText: '確定',
                    cancelButtonText: '取消'
                }).then((result) => {
                    if (result.value) { // confirm
                        deleteUser(username);
                    }
                });
            }
        } else {
            $('form').on('submit', function (e) {
                e.preventDefault();
                sendData(mode, this);
            })
        }
    }

    function create(tag, className) {
        let el = document.createElement(tag);
        if (className) {
            el.className = className;
        }
        return el;
    }

    pageManager.register("user", function () {
        return {
            render: function (...args) {
                fetchUser(...args);
            }
        }
    });
})();
