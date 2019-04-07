<?php
/*  引入  */
require_once "header.php";

/*  流程控制  */
$op      = isset($_REQUEST['op']) ? my_filter($_REQUEST['op'], "string") : '';
$user_sn = isset($_REQUEST['user_sn']) ? my_filter($_REQUEST['user_sn'], "int") : 0;
$user_id = isset($_REQUEST['user_id']) ? my_filter($_REQUEST['user_id'], "string") : '';

switch ($op) {
    case 'user_form':
        user_form($user_sn);
        break;

    case 'insert_user':
        $user_sn = insert_user();
        header("location:{$_SERVER['PHP_SELF']}?op=display_user&user_sn=$user_sn");
        exit;
        break;

    case 'update_user':
        update_user();
        header("location:{$_SERVER['PHP_SELF']}?op=display_user&user_sn=$user_sn");
        exit;
        break;

    case 'delete_user':
        delete_user($user_sn);
        header("location:{$_SERVER['PHP_SELF']}");
        exit;
        break;

    case 'user_login':
        $login = user_login($user_id);
        if ($login) {
            header("location:{$_SERVER['PHP_SELF']}");
        } else {
            header("location:index.php");
        }
        exit;
        break;

    case 'user_logout':
        user_logout();
        header("location:index.php");
        exit;
        break;

    case 'display_user':
    default:
        $op = 'display_user';
        display_user($user_sn);
        break;

}

/*  輸出  */
require_once "footer.php";

/*  本檔案使用函數  */

//註冊會員表單
function user_form($user_sn)
{
    global $mysqli, $smarty;
    if (empty($user_sn)) {
        $sql    = "EXPLAIN `users`";
        $result = $mysqli->query($sql) or die($mysqli->connect_error);
        while (list($field_name) = $result->fetch_row()) {
            $user[$field_name] = '';
        }
    } else {
        $sql    = "SELECT * FROM `users` WHERE `user_sn`='{$user_sn}'";
        $result = $mysqli->query($sql) or die($mysqli->connect_error);
        $user   = $result->fetch_assoc();
    }
    $smarty->assign('user', $user);
}

//儲存會員
function insert_user()
{
    global $mysqli;
    foreach ($_POST as $var_name => $var_val) {
        $$var_name = $mysqli->real_escape_string($var_val);
    }
    if ($key != $_SESSION['key']) {
        die("認證碼錯誤，請回上頁重新輸入。");
    }
    $user_passwd = password_hash($_POST['user_passwd'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO `users` (`user_name`, `user_id`, `user_passwd`, `user_email`, `user_sex`, `user_tel`, `user_zip`, `user_county`, `user_district`, `user_address`) VALUES ('{$user_name}' , '{$user_id}' , '{$user_passwd}', '{$user_email}', '{$user_sex}', '{$user_tel}', '{$user_zip}', '{$user_county}', '{$user_district}', '{$user_address}')";
    $mysqli->query($sql) or die($mysqli->connect_error);
    $user_sn             = $mysqli->insert_id;
    $_SESSION['user_sn'] = $user_sn;
    $_POST['user_sn']    = $user_sn;
    $_SESSION['user']    = $_POST;
    return $user_sn;
}

//觀看會員
function display_user($user_sn)
{
    global $mysqli, $smarty, $isAdmin, $isUser;
    if (empty($user_sn)) {
        $user_sn = $_SESSION['user_sn'];
    }

    if ($isUser) {
        $user_sn = $isAdmin ? $user_sn : $_SESSION['user_sn'];
    } else {
        die('非會員，請勿亂搞。');
    }
    $sql    = "SELECT * FROM `users` WHERE `user_sn`='{$user_sn}'";
    $result = $mysqli->query($sql) or die($mysqli->connect_error);
    $user   = $result->fetch_assoc();
    $smarty->assign('user', $user);

    $all_users = '';
    if ($isAdmin) {
        $sql       = "SELECT * FROM `users`";
        $result    = $mysqli->query($sql) or die($mysqli->connect_error);
        $all_users = $result->fetch_all(MYSQLI_ASSOC);
    }
    $smarty->assign('all_users', $all_users);
    $smarty->assign('now_user_sn', $user_sn);
}

//更新會員
function update_user()
{
    global $mysqli, $isAdmin, $isUser;

    foreach ($_POST as $var_title => $var_value) {
        $$var_title = $mysqli->real_escape_string($_POST[$var_title]);
    }
    $passwd_update = '';
    if (!empty($_POST['user_passwd'])) {
        $user_passwd   = password_hash($_POST['user_passwd'], PASSWORD_DEFAULT);
        $passwd_update = "`user_passwd` = '{$user_passwd}',";
    }
    if ($isUser) {
        $user_sn = $isAdmin ? $user_sn : $_SESSION['user_sn'];
    } else {
        return;
    }
    $sql = "UPDATE `users` SET
      `user_name` = '{$user_name}',
      `user_id` = '{$user_id}',
      {$passwd_update}
      `user_email` = '{$user_email}',
      `user_sex` = '{$user_sex}',
      `user_tel` = '{$user_tel}',
      `user_zip` = '{$user_zip}',
      `user_county` = '{$user_county}',
      `user_district` = '{$user_district}',
      `user_address` = '{$user_address}'
    WHERE `user_sn` = '{$user_sn}'";
    $mysqli->query($sql) or die($mysqli->connect_error);
}

//刪除會員
function delete_user($user_sn)
{
    global $mysqli, $isAdmin;

    if (!$isAdmin) {
        return;
    }
    $sql = "DELETE FROM `users` WHERE `user_sn` = '{$user_sn}'";
    $mysqli->query($sql) or die($mysqli->connect_error);
}

//會員登入
function user_login($user_id)
{
    global $mysqli;
    if (empty($user_id)) {
        return false;
    }

    $sql    = "SELECT * FROM `users` WHERE `user_id`='{$user_id}'";
    $result = $mysqli->query($sql) or die($mysqli->connect_error);
    $user   = $result->fetch_assoc();
    if (!empty($user)) {
        if (password_verify($_POST['user_passwd'], $user['user_passwd'])) {
            $_SESSION['user_sn'] = $user['user_sn'];
            $_SESSION['user']    = $user;
            return true;
        }
    }
    return false;
}

//會員登出
function user_logout()
{
    unset($_SESSION['user_sn']);
    unset($_SESSION['user']);
}
