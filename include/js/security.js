axios.defaults.withCredentials = true;

axios.interceptors.request.use(function (config) {
    var crypto = window.crypto || window.msCrypto;
    let csrfToken = btoa(String(crypto.getRandomValues(new Uint32Array(1))[0]));
    document.cookie = `${axios.defaults.xsrfCookieName}=${csrfToken}; max-age=10; path=/`;
    return config;
}, function (error) {
    return Promise.reject(error);
});

$("#logout").on("click", function (e) {
    e.preventDefault();
    axios.get("login.php?logout").then(function (res) {
        location.href = res.headers["axios-location"];
    }).catch(function (error) {
        console.log(error);
    });
});