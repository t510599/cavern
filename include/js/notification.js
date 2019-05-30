var notifications = {
    toFetch: true,
    unreadCount: 0,
    feeds: []
};

$('#menu .notification.icon.item').on('click', function (e) {
    e.preventDefault();
    let el = e.currentTarget;

    let $wrapper = $('#notification-wrapper');
    let $container = $('.notification.container');

    if ($container.hasClass('active')) {
        // dismiss the notification window
        $('.notification.click.handler').remove();
    } else {
        // render the notification window
        let handler = document.createElement('div')
        handler.className = "notification click handler";
        $wrapper.after(handler);
        handler.addEventListener('click', function (e) {
            el.click();
        });
        setNotificationCounter(0); // remove counter
        if (notifications.toFetch){
            fetchNotification();
        }
    }

    el.classList.toggle('active');
    $container.toggleClass('active');
});

function fetchNotificationCount() {
    axios.request({
        method: 'GET',
        url: "./ajax/notification.php?count",
        responseType: 'json'
    }).then(function (res) {
        let count = res.data['unread_count'];
        setNotificationCounter(count);
        if (count != notifications.unreadCount) {
            // if count changes, fetching notifications while next click
            notifications.toFetch = true;
            notifications.unreadCount = count;
        }
    }).catch(function (_error) {
        console.error("Error occurred while fetching notification count.");
    });
}

window.addEventListener('load', function () {
    fetchNotificationCount();
});
var notificationFetchTimer = setInterval(fetchNotificationCount, 1 * 5 * 1000); // fetch notification count every 1 minute

function fetchNotification() {
    axios.request({
        method: 'GET',
        url: './ajax/notification.php?fetch',
        responseType: 'json'
    }).then(function (res) {
        parseNotification(res.data);
        notifications.toFetch = false;
    }).catch(function (error) {
        console.log("Error occurred while fetching notification count.");
    });
}

function parseNotification(data) {
    const feedTemplate = `<div class="event"><div class="label"><i class="volume up icon"></i></div><div class="content"><div class="date">{{ time }}</div><div class="summary">{{ message }}</div></div></div>`;
    let $feed = $('.ts.feed');

    $feed.html(""); // container clean up

    for (f of data.feeds) {
        let message = parseMessage(f.message, f.url);
        let node = feedTemplate.replace("{{ time }}", f.time).replace("{{ message }}", message);
        $node = $(node).appendTo($feed);

        if (f.read == 0) {
            $node.addClass('unread');
        }
    }

    notifications.feeds = data.feeds; // cache data

    function parseMessage(message, url) {
        let regex = {
            "username": /\{([^\{\}]+)\}@(\w+)/g,
            "url": /\[([^\[\[]*)\]/g
        };

        return message.replace(regex.username, function (_match, name, id, _offset, _string) {
            return `<a href="user.php?username=${id}">${name}</a>`;
        }).replace(regex.url, function (_match, title, _offset, _string) {
            return `<a href="${url}">${title}</a>`;
        });
    }
}

function setNotificationCounter(count) {
    let $notify = $('#menu .notification.icon.item');
    let $icon = $notify.children('i.icon');
    let $counter = $notify.children('span.counter');

    if (count == 0) {
        if ($counter.length) {
            $counter.remove();
        }
        $icon.toggleClass('outline', true); // set icon style
    } else {
        if ($counter.length) {
            $counter.text(count);
        } else {
            let counter = document.createElement('span');
            counter.className = "counter";
            counter.textContent = count;
            $notify.append(counter);
        }
        $icon.toggleClass('outline', false); // set icon style
    }

    return count;
}