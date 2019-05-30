$('#content').on('click', 'button.like.button', function(e){
    var el = e.currentTarget;
    var id = el.dataset.id;
    axios.request({
        method: "GET",
        url: "./ajax/like.php?pid=" + id,
        responseType: "json",
    }).then(function (res) {
        var data = res.data;
        if (data.status == true) {
            $(`button.like.button[data-id="${data.id}"]`).html(
                '<i class="thumbs up icon"></i> ' + data.likes
            );
        } else if (data.status == false) {
            $(`button.like.button[data-id="${data.id}"]`).html(
                '<i class="thumbs outline up icon"></i> ' + data.likes
            );
        }
    }).catch(function (error) {
        if (error.response) {
            let data = error.response.data;
            if (data.status == 'nologin') {
                $(`button.like.button[data-id="${data.id}"]`).html(
                    '<i class="thumbs outline up icon"></i> ' + data.likes
                );
                swal({
                    type: 'warning',
                    title: '請先登入!',
                    text: '登入以按讚或發表留言。',
                    showCancelButton: true,
                    confirmButtonText: '登入',
                    cancelButtonText: '取消',
                }).then((result) => {
                    if (result.value) { // confirm
                        location.href = 'login.php';
                    }
                });
            }
        } else {
            $(`button.like.button[data-id="${id}"]`).html(
                '<i class="thumbs outline up icon"></i> ' + "--"
            );
            console.error(`An error occurred when get likes of pid ${id}, status ${error.response.status}`);
        }
    });
});