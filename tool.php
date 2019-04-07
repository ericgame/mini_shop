<?php
/*  引入  */
require_once "header.php";
if (!$isAdmin) {
    header("location:index.php");
    exit;
}

/*  流程控制  */
$op       = isset($_REQUEST['op']) ? my_filter($_REQUEST['op'], "string") : '';
$goods_sn = isset($_REQUEST['goods_sn']) ? my_filter($_REQUEST['goods_sn'], "int") : 0;
switch ($op) {
    case 'goods_form':
        goods_form($goods_sn);
        break;

    case 'update_goods':
        update_goods($goods_sn);
        header("location:index.php?goods_sn={$goods_sn}");
        exit;
        break;

    case 'delete_goods':
        delete_goods($goods_sn);
        header("location:index.php");
        exit;
        break;

    case 'insert_goods':
        $goods_sn = insert_goods();
        header("location:index.php?goods_sn={$goods_sn}");
        exit;
        break;
}

/*  輸出  */
require_once "footer.php";

/*  本檔案使用函數  */

//商品編輯表單
function goods_form($goods_sn)
{
    global $mysqli, $smarty;
    if (empty($goods_sn)) {
        $sql    = "EXPLAIN `goods`";
        $result = $mysqli->query($sql) or die($mysqli->connect_error);
        while (list($field_name) = $result->fetch_row()) {
            $goods[$field_name] = '';
        }
    } else {
        $sql          = "SELECT * FROM `goods` WHERE `goods_sn`='{$goods_sn}'";
        $result       = $mysqli->query($sql) or die($mysqli->connect_error);
        $goods        = $result->fetch_assoc();
        $goods['pic'] = get_goods_pic($goods_sn, 'thumbs');
    }
    $smarty->assign('goods', $goods);
}

//儲存商品
function insert_goods()
{
    global $mysqli;
    foreach ($_POST as $var_name => $var_val) {
        $$var_name = $mysqli->real_escape_string($var_val);
    }
    $goods_date = date("Y-m-d H:i:s");

    $sql = "INSERT INTO `goods` (`goods_title`, `goods_content`, `goods_price`, `goods_counter`, `goods_date`) VALUES ('{$goods_title}', '{$goods_content}', '{$goods_price}', '0', '{$goods_date}')";
    $mysqli->query($sql) or die($mysqli->connect_error);
    $goods_sn = $mysqli->insert_id;
    save_goods_pic($goods_sn);
    return $goods_sn;
}

//儲存圖片
function save_goods_pic($goods_sn = "")
{
    include_once "class/upload/class.upload.php";
    $pic = new Upload($_FILES['goods_pic'], 'zh_TW');
    if ($pic->uploaded) {
        //大圖
        $pic->file_new_name_body = $goods_sn;
        $pic->file_overwrite     = true;
        $pic->image_resize       = true;
        $pic->image_x            = 600;
        $pic->image_y            = 400;
        $pic->image_convert      = 'png';
        $pic->image_ratio_crop   = true;
        $pic->Process('uploads/goods/');
        if (!$pic->processed) {
            return 'error : ' . $pic->error;
        }
        //縮圖
        $pic->file_new_name_body = $goods_sn;
        $pic->file_overwrite     = true;
        $pic->image_resize       = true;
        $pic->image_x            = 300;
        $pic->image_y            = 200;
        $pic->image_convert      = 'png';
        $pic->image_ratio_crop   = true;
        $pic->Process('uploads/thumbs/');
        if ($pic->processed) {
            $pic->Clean();
        } else {
            return 'error : ' . $pic->error;
        }
    }
}

//上傳圖片（舊）
// function save_goods_pic($goods_sn = "")
// {
//     if (!isset($_FILES['goods_pic']['tmp_name'])) {
//         return;
//     } elseif (empty($goods_sn)) {
//         die("沒有編號呢！");
//     }

//     mk_dir('uploads');
//     if (move_uploaded_file($_FILES['goods_pic']['tmp_name'], "uploads/{$goods_sn}.jpg")) {
//         return true;
//     }
//     return false;
// }

//建立目錄
function mk_dir($dir = "")
{
    //若無目錄名稱秀出警告訊息
    if (empty($dir)) {
        die("無目錄名稱");
    }

    //若目錄不存在的話建立目錄
    if (!is_dir($dir)) {
        umask(000);
        //若建立失敗秀出警告訊息
        if (!mkdir($dir, 0777)) {
            die("無法建立目錄");
        }

    }
}

function update_goods($goods_sn)
{
    global $mysqli;
    foreach ($_POST as $var_name => $var_val) {
        $$var_name = $mysqli->real_escape_string($var_val);
    }

    $goods_date = date("Y-m-d H:i:s");

    $sql = "UPDATE `goods` SET
    `goods_title`='{$goods_title}',
    `goods_content`='{$goods_content}',
    `goods_price`='{$goods_price}',
    `goods_date`='{$goods_date}'
    WHERE `goods_sn`='{$goods_sn}'";
    $mysqli->query($sql) or die($mysqli->connect_error);
    save_goods_pic($goods_sn);
}

//刪除商品
function delete_goods($goods_sn)
{
    global $mysqli;
    $sql = "DELETE FROM `goods` WHERE `goods_sn`='{$goods_sn}'";
    $mysqli->query($sql) or die($mysqli->connect_error);
    delete_goods_pic($goods_sn);
}

//刪除圖片
function delete_goods_pic($goods_sn = "")
{
    if (file_exists("uploads/goods/{$goods_sn}.png")) {
        unlink("uploads/goods/{$goods_sn}.png");
    }
    if (file_exists("uploads/thumbs/{$goods_sn}.png")) {
        unlink("uploads/thumbs/{$goods_sn}.png");
    }
}
