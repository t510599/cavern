<?php
function validate_csrf() {
    if (isset($_COOKIE["XSRF-TOKEN"]) && isset($_SERVER["HTTP_X_XSRF_TOKEN"]) && ($_COOKIE["XSRF-TOKEN"] === $_SERVER["HTTP_X_XSRF_TOKEN"])) {
        return TRUE;
    } else {
        return FALSE;
    }
}
?>