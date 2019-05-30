$(document).ready(function () {
    var $sidebar = $('.ts.sidebar');
    let mq = window.matchMedia("(max-width: 768px)");
    mq.addListener(sidebarOnMobile);

    sidebarOnMobile(mq); // run first
    function sidebarOnMobile(q) {
        if (q.matches) {
            /* mobile -> hide sidebar */
            $sidebar.toggleClass("animating", true);
            $sidebar.toggleClass("visible", false);
        } else {
            /* non-mobile -> show sidebar */
            $sidebar.toggleClass("animating", false);
            $sidebar.toggleClass("visible", true);
        }
    }
    
    $('button#toggleSidebar').on('click', function (e) {
        $sidebar.toggleClass('visible');
    });
});

function Manager(element) {
    this.components = {};
    this.cache = {}; // args cache for components just be on load

    this.document = element;
}

Manager.prototype = {
    load: function(name, ...args) {
        this.setLoaderState(true);
        if (Object.keys(this.components).indexOf(name) == -1) {
            // cache args
            this.cache[name] = args;
            script = document.createElement("script");
            script.src = `component/${name}.js`;
            document.body.appendChild(script);
        } else {
            this.components[name].render(...args);
        }
    },
    register: function(name, init) {
        this.components[name] = init();
        let args = this.cache[name];
        this.components[name].render(...args);
        delete this.cache[name];
    },
    setHeader: function(title) {
        $('#header').text(title);
    },
    setLoaderState: function(state) {
        $('.pusher > .progress').toggleClass('invisible', !state);
    },
    snackbar: function(message) {
        ts('.snackbar').snackbar({
            content: message
        });
    }
}

let pageManager = new Manager(document.querySelector('#content'));

var root = "./", useHash = true, hash = "#";
let router = new Navigo(root, useHash, hash);

if (location.href.slice(-1) === "/") {
    // catch to navigo
    router.navigate("/");
}

router.on({
    "/": function () {
        // system overview
        render("statistics");
    },
    "/post": function () {
        render("post", "page", 1);
    },
    "/post/:pid": function (params) {
        render("post", "pid", params.pid);
    },
    "/post/page/:page": function (params) {
        render("post", "page", params.page);
    },
    "/user": function () {
        render("user", "list");
    },
    "/user/add": function () {
        render("user", "add");
    },
    "/user/:username": function (params) {
        render("user", "username", params.username);
    },
    "/config": function () {
        render("config");
    }
}).resolve();

router.updatePageLinks();

function render(page, ...args) {
    switch (page) {
        case "user":
            pageManager.load("user", ...args);
            break;
        case "post":
            pageManager.load("post", ...args);
            break;
        case "config":
            pageManager.load("config", ...args);
            break;
        case "statistics": 
        default:
            pageManager.load("statistics", ...args);
            break;
    }
}