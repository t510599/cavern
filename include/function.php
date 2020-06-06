<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function cavern_login($username, $password) {
    global $SQL;
    if (isset($username) && isset($password)) {
        $login = $SQL->query("SELECT `username`, `pwd` FROM `user` WHERE `username` = '%s' AND `pwd` = '%s'",array($username, cavern_password_hash($password, $username)));
        if ($login->num_rows > 0) {
            $_SESSION['cavern_username'] = $username;
            return 1;
        }
        else {
            return -1;
        }
    } else {
        return -1;
    }
}

function cavern_logout() {
    $_SESSION['cavern_username'] = NULL;
    unset($_SESSION['cavern_username']);
    return 1;
}

function cavern_password_hash($value, $salt) {
    $temp = substr(sha1(strrev($value).$salt), 0, 24);
    return hash('sha512', $temp.$value);
}

function cavern_query_result($query, $data=array()) { 
    global $SQL;
    $result['query'] = $SQL->query($query, $data);
    $result['row'] = $result['query']->fetch_assoc();
    $result['num_rows'] = $result['query']->num_rows;
    
    return $result;
}

function cavern_level_to_role($level) {
    switch ($level) {
        case 9:
            $role = "站長";
            break;
        case 8:
            $role = "管理員";
            break;
        case 1:
            $role = "作者";
            break;
        case 0:
            $role = "會員";
            break;
        default:
            $role = "麥克雞塊";
            break;
    }
    return $role;
}

function cavern_greeting() {
    $hour = date('G');
    if ($hour >= 21 || $hour < 5) {
        $greeting = "晚安";
    } else if ($hour >= 12) {
        $greeting = "午安";
    } else if ($hour >= 5 && $hour < 12) {
        $greeting = "早安";
    }
    return $greeting;
}

function cavern_pages($now_page, $total, $limit) {
    $text='<div class="ts basic center aligned segment" id="pages">';
    $text.='<select class="ts basic dropdown" onchange="location.href=this.options[this.selectedIndex].value;">';
    $now_page = abs($now_page);
    $page_num = ceil($total / $limit);
    for ($i = 1; $i <= $page_num; $i++) {
        if ($now_page != $i) {
            $text.='<option value="index.php?page='.$i.'">第 '.$i.' 頁</option>';
        } else {
            $text.='<option value="index.php?page='.$i.'" selected="selected">第 '.$i.' 頁</option>';
        }
    }
    $text.='</select>';
    $text.='</div>';
    return $text;
}

function sumarize($string, $limit) {
    $count = 0;
    $text = "";
    $content_start = FALSE;
    $lines = explode("\n", $string);

    if (sizeof($lines) > $limit) {
        foreach ($lines as $line) {
            if (trim($line) != "" && $content_start == FALSE) {
                $content_start = TRUE; // don't count the empty line until the main content
            }
            if (!$content_start) {
                continue;
            }
            $count++;
            $text.=$line."\n";
            if ($count == $limit || mb_strlen($text) >= 200) {
                if (mb_strlen($text) >= 200) {
                    $text = mb_substr($text, 0, 200)."...\n";
                }
                $text.="...(還有更多)\n";
                break;
            }
        }
        return $text;
    } else {
        return $string;
    }
} 