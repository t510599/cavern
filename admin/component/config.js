(() => {
    function fetchConfig() {
        axios.request({
            method: "GET",
            url: "ajax/config.php"
        }).then(function (res) {
            renderPage(res.data);
        }).catch(function (err) {
            if (err.response) {
                console.error(err.response.status);
            }
        });
    }

    function renderPage(data) {
        let doc = pageManager.document;
        let fieldName = {
            "name": "網站名稱",
            "limit": "顯示數量",
            "register": "開放註冊"
        };
        let pattern = {
            "limit": "[0-9]+"
        };

        let formContainer = create('div');  formContainer.className = "ts form";
        let form = create('form'); form.method = "POST"; form.action = "ajax/config.php"; form.name = "config"; form.id = "config";

        for (key in data) {
            if (["name", "limit", "register"].indexOf(key) != -1) {
                let field = create('div'); field.className = "field";
                let label = create('label');
                label.textContent = fieldName[key];
                if (key == "register") {
                    var input = create('select'); input.name = key;
                    for (let value of [true, false]){
                        let option = create('option');
                        option.value = option.textContent = value;
                        if (data[key] == value) {
                            option.selected = "selected";
                        }
                        input.appendChild(option);
                    }
                } else {
                    var input = create('input'); input.name = key; input.type = "text";
                    input.value = data[key];
                }

                if (pattern[key]) {
                    input.pattern = pattern[key];
                }

                field.appendChild(label);
                field.appendChild(input);
                form.appendChild(field);
            }
        }
        
        form.insertAdjacentHTML("beforeend", `<input class="ts right floated primary button" value="送出" type="submit">`);

        formContainer.appendChild(form);

        setTimeout(() => {
            doc.innerHTML = "";
            doc.appendChild(formContainer);
            bindListener();
            pageManager.setLoaderState(false)
        }, 1000);
    }

    function bindListener() {
        // form
        document.config.addEventListener('submit', function(e) {
            e.preventDefault();

            // validate data
            let data = new FormData(this);
            if (!/^[0-9]+$/.test(data.get("limit"))) {
                snackbar("請按照格式輸入!");
                return;
            }

            axios.request({
                url: "ajax/config.php",
                method: "POST",
                data: data
            }).then((res) => {
                pageManager.snackbar('儲存成功!');
                pageManager.load("config"); // reload
            }).catch((err) => {
                if (err.response) {
                    pageManager.snackbar(`儲存失敗: ${err.response.data["status"]}`);
                } else {
                    pageManager.snackbar('儲存失敗。');
                }
            });
        });
    }

    function create(tag) {
        return document.createElement(tag);
    }

    pageManager.register("config", function () {
        return {
            render: function (...args) {
                pageManager.setHeader("設定");
                fetchConfig();
            }
        }
    });
})();
