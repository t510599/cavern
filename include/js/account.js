 if (document.newacc) {
    // Register
    let form = document.newacc;
    eventListenerInitialize(form, [form.password, form.repeat]);
    $(form.username).on('change', function () {
        let self = this;
        if (!/^[a-z][a-z0-9_-]*$/.test(self.value) || (self.value.length > 20 || self.value == "")) {
            setFieldStatus(self, "error", "請依照格式輸入");
            setFieldLabel(self, "");
        } else {
            setFieldStatus(self, ""); // reset
            setFieldLabel(self, "");

            axios.request({
                method: "GET",
                url: `ajax/user.php?username=${this.value}`,
                responseType: "json"
            }).then(function (_res) {
                // username exist
                setFieldStatus(self, "error", "此帳號已被使用");
                setFieldLabel(self, "此帳號已被使用");
            }).catch(function (_error) {
                // username not exist
                setFieldStatus(self, "success");
                setFieldLabel(self, "此帳號可以使用");
            });
        }
    });
} else if (document.editacc) {
    // Manage Profile
    let form = document.editacc;
    eventListenerInitialize(form, [form.new, form.repeat]);
    $(form.new).on('input', function () {
        if (this.value == "") {
            form.repeat.removeAttribute("required");
            setFieldStatus(form.repeat, "", "", false);
        } else {
            form.repeat.setAttribute("required", "required");
        }
    });
}

function eventListenerInitialize (form, inputs) {
    // first is password input, second is repeat input
    inputs.forEach(function (el) {
        $(el).on('input', function (_e) {
            if (inputs[0].value == inputs[1].value && inputs[0].value != "") {
                setFieldStatus(inputs[1], "success");
            } else {
                setFieldStatus(inputs[1], "error", "密碼不正確，請再試一次。");
            }
        });
    });

    $(form).on('submit', function (e) {
        e.preventDefault();
        if (inputs[0].value != inputs[1].value) {
            inputs[1].setCustomValidity("密碼不正確，請再試一次。");
            inputs[1].focus();
            return undefined;
        }

        axios.request({
            method: "POST",
            data: new FormData(this),
            url: "account.php",
            headers: {
                'Content-Type': "application/x-www-form-urlencoded"
            }
        }).then(function (res) {
            location.href = res.headers["axios-location"];
        }).catch(function (error) {
            if (error.response) {
                location.href = error.response.headers["axios-location"];
            } else {
                ts('.snackbar').snackbar({
                    content: "發送失敗。"
                });
            }
        });
    });
}

function setFieldStatus(el, status, validity="", required=true) {
    el.parentElement.className = (required) ? `${status} required field` : `${status} field`;
    el.setCustomValidity(validity);
}

function setFieldLabel(el, text) {
    let sibling = el.nextElementSibling;
    if (sibling.tagName == "SMALL" && text != "") {
        let span = document.createElement('span');
        span.className = "message";
        span.innerText = text;
        el.parentElement.insertBefore(span, sibling);
    } else if (sibling.tagName == "SPAN" && text == "") {
        $(sibling).remove();
    } else if (sibling.tagName != "SMALL") {
        sibling.innerText = text;
    }
}