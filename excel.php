<?php
require_once "header.php";
require_once 'class/PHPExcel.php';
require_once 'class/PHPExcel/IOFactory.php';
$objPHPExcel = new PHPExcel();
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename=' . iconv('UTF-8', 'big5', '出貨單') . '.xlsx');
header('Cache-Control: max-age=0');
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objPHPExcel->setActiveSheetIndex(0);
$objActSheet = $objPHPExcel->getActiveSheet();
$objActSheet->setTitle("出貨單");
$objPHPExcel->createSheet();
// $objPHPExcel->getDefaultStyle()->getFont()->setName('微軟正黑體')->setSize(12);

$bill_sn = isset($_REQUEST['bill_sn']) ? my_filter($_REQUEST['bill_sn'], "int") : 0;
$sql     = "SELECT a.*,b.* FROM `bill` AS a
JOIN `users` AS b on a.`user_sn`=b.`user_sn`
WHERE a.`bill_sn`='{$bill_sn}'";
$result = $mysqli->query($sql) or die($mysqli->connect_error);
$bill   = $result->fetch_assoc();

$objActSheet->setCellValue("A1", '收貨人：')->setCellValue("B1", "{$bill['user_name']}{$bill['user_sex']}");
$objActSheet->setCellValue("A2", '收貨地址：')->mergeCells("B2:D2")->setCellValue("B2", "{$bill['user_zip']}{$bill['user_county']}{$bill['user_district']}{$bill['user_address']}");
$objActSheet->setCellValue("A3", '收貨電話：')->setCellValueExplicit("B3", $bill['user_tel'], PHPExcel_Cell_DataType::TYPE_STRING);

$objActSheet->getColumnDimension('A')->setWidth(15);
$objActSheet->getColumnDimension('B')->setAutoSize(true);

$sql = "SELECT a.* , b.* FROM `bill_detail` AS a
    LEFT JOIN `goods` AS b on a.`goods_sn`=b.`goods_sn`
    WHERE a.`bill_sn`='{$bill_sn}'";
$result = $mysqli->query($sql) or die($mysqli->connect_error);

$objActSheet
    ->setCellValue("A4", '商品名稱')
    ->setCellValue("B4", '單價')
    ->setCellValue("C4", '數量')
    ->setCellValue("D4", '小計');
$objActSheet->getStyle('A4:D4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('D7E2F2');
$objActSheet->getStyle('A4:D4')->getFont()->setBold(true)->getColor()->setARGB(PHPExcel_Style_Color::COLOR_BLUE);
$objActSheet->getStyle('A4:D4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$i = 5;
while ($all = $result->fetch_assoc()) {
    $objActSheet
        ->setCellValue("A{$i}", $all['goods_title'])
        ->setCellValue("B{$i}", $all['goods_price'])
        ->setCellValue("C{$i}", $all['goods_amount'])
        ->setCellValue("D{$i}", "=B{$i}*C{$i}");
    $objActSheet->getStyle("A{$i}:D{$i}")->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $i++;
}
$j = $i - 1;
$objActSheet
    ->setCellValue("A{$i}", '')
    ->setCellValue("B{$i}", '')
    ->setCellValue("C{$i}", '')
    ->setCellValue("D{$i}", "=sum(D5:D{$j})");

$objWriter->save('php://output');
exit;
