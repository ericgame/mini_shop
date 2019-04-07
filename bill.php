<?php
/*  引入  */
require_once "header.php";
if (!$isUser) {
    header("location:index.php?msg=請先登入");
    exit;
}

/*  流程控制  */
$op      = isset($_REQUEST['op']) ? my_filter($_REQUEST['op'], "string") : '';
$bill_sn = isset($_REQUEST['bill_sn']) ? my_filter($_REQUEST['bill_sn'], "int") : 0;
$user_sn = isset($_REQUEST['user_sn']) ? my_filter($_REQUEST['user_sn'], "int") : 0;

//流程控制
switch ($op) {
    case "check_out":
        check_out();
        break;

    case 'save_bill':
        $bill_sn = save_bill();
        header("location:bill.php?op=display_bill&bill_sn={$bill_sn}");
        exit;
        break;

    case 'display_bill':
        display_bill($bill_sn);
        break;

    case 'delete_bill':
        delete_bill($bill_sn);
        header("location:bill.php");
        exit;
        break;

    case 'finish_bill':
        finish_bill($bill_sn);
        header("location:bill.php?op=display_bill&bill_sn={$bill_sn}");
        exit;
        break;

    case 'list_bill':
    default:
        $op = "list_bill";
        list_bill($user_sn);
        break;
}

/*  輸出  */
require_once "footer.php";

/*  本檔案使用函數  */

//結帳功能
function check_out()
{
    global $smarty, $mysqli;
    $bill_total = 0;
    foreach ($_POST['goods_amount'] as $goods_sn => $goods_amount) {
        $sql    = "SELECT `goods_title`,`goods_price` FROM `goods` WHERE `goods_sn` ='{$goods_sn}'";
        $result = $mysqli->query($sql) or die($mysqli->connect_error);

        list($goods_title, $goods_price)     = $result->fetch_row();
        $cart_arr[$goods_sn]['goods_title']  = $goods_title;
        $cart_arr[$goods_sn]['goods_price']  = $goods_price;
        $cart_arr[$goods_sn]['goods_amount'] = $goods_amount;
        $cart_arr[$goods_sn]['goods_total']  = $goods_amount * $goods_price;
        $bill_total += $cart_arr[$goods_sn]['goods_total'];
    }
    $smarty->assign('cart_arr', $cart_arr);
    $smarty->assign('bill_total', $bill_total);
}

//儲存帳單
function save_bill()
{
    global $mysqli;
    $bill_total = intval($_POST['bill_total']);
    $now        = date("Y-m-d H:i:s");

    $sql = "INSERT INTO `bill` (`user_sn`, `bill_total`, `bill_date`) VALUES ('{$_SESSION['user_sn']}', '{$bill_total}', '{$now}')";
    $mysqli->query($sql) or die($mysqli->connect_error);
    $bill_sn = $mysqli->insert_id;

    foreach ($_POST['goods_amount'] as $goods_sn => $goods_amount) {
        $goods_total = intval($_POST['goods_total'][$goods_sn]);
        $sql         = "INSERT INTO `bill_detail` (`bill_sn`, `goods_sn`, `goods_amount`, `goods_total`) VALUES ('{$bill_sn}', '{$goods_sn}', '{$goods_amount}', '{$goods_total}')";
        $mysqli->query($sql) or die($mysqli->connect_error);
        setcookie("cart[$goods_sn][goods_amount]", "", time() - 3600);
        setcookie("cart[$goods_sn][goods_title]", "", time() - 3600);
    }
    return $bill_sn;
}

//帳單顯示
function display_bill($bill_sn)
{
    global $smarty, $mysqli;
    $sql = "SELECT a.*,b.* FROM `bill` AS a
    JOIN `users` AS b on a.`user_sn`=b.`user_sn`
    WHERE a.`bill_sn`='{$bill_sn}'";
    $result = $mysqli->query($sql) or die($mysqli->connect_error);
    $bill   = $result->fetch_assoc();
    $smarty->assign('bill', $bill);

    $sql = "SELECT a.* , b.* FROM `bill_detail` AS a
    LEFT JOIN `goods` AS b on a.`goods_sn`=b.`goods_sn`
    WHERE a.`bill_sn`='{$bill_sn}'";
    $result = $mysqli->query($sql) or die($mysqli->connect_error);
    while ($all = $result->fetch_assoc()) {
        $bill_detail[] = $all;
    }
    $smarty->assign('bill_detail', $bill_detail);
    $smarty->assign('bill_sn', $bill_sn);
}

//刪除帳單
function delete_bill($bill_sn)
{
    global $mysqli, $isAdmin;

    if (!$isAdmin) {
        return;
    }
    $sql = "DELETE FROM `bill_detail` WHERE `bill_sn` = '{$bill_sn}'";
    $mysqli->query($sql) or die($mysqli->connect_error);
    $sql = "DELETE FROM `bill` WHERE `bill_sn` = '{$bill_sn}'";
    $mysqli->query($sql) or die($mysqli->connect_error);
}

//帳單列表
function list_bill($user_sn = "")
{
    global $smarty, $mysqli, $isAdmin;
    if (empty($user_sn)) {
        $user_sn = $_SESSION['user_sn'];
    }
    $user_sn  = $isAdmin ? $user_sn : $_SESSION['user_sn'];
    $sql      = "SELECT * FROM `bill` WHERE `user_sn`='{$user_sn}' order by `bill_date` desc";
    $result   = $mysqli->query($sql) or die($mysqli->connect_error);
    $bill_arr = "";
    while ($all = $result->fetch_assoc()) {
        $bill_arr[] = $all;
    }
    $smarty->assign('bill_arr', $bill_arr);

    $all_users = '';
    if ($isAdmin) {
        $sql       = "SELECT * FROM `users`";
        $result    = $mysqli->query($sql) or die($mysqli->connect_error);
        $all_users = $result->fetch_all(MYSQLI_ASSOC);
    }
    $smarty->assign('all_users', $all_users);
    $smarty->assign('now_user_sn', $user_sn);
}

//已出貨
function finish_bill($bill_sn = "")
{

    global $smarty, $mysqli, $isAdmin;
    if (!$isAdmin) {
        return;
    }
    $sql = "UPDATE `bill` SET `bill_status`='已出貨' WHERE `bill_sn`='{$bill_sn}'";
    $mysqli->query($sql) or die($mysqli->connect_error);
}
