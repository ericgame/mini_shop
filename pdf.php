<?php
/*  引入  */
require_once "header.php";
require_once "class/tcpdf/tcpdf.php";

/*  流程控制  */
$op      = isset($_REQUEST['op']) ? my_filter($_REQUEST['op'], "string") : '';
$bill_sn = isset($_REQUEST['bill_sn']) ? my_filter($_REQUEST['bill_sn'], "int") : 0;

switch ($op) {
    case 'bill_pdf':
        bill_pdf($bill_sn);
        break;
}

function bill_pdf($bill_sn = "")
{
    global $mysqli;
    $sql = "SELECT a.*,b.* FROM `bill` AS a
    JOIN `users` AS b on a.`user_sn`=b.`user_sn`
    WHERE a.`bill_sn`='{$bill_sn}'";
    $result = $mysqli->query($sql) or die($mysqli->connect_error);
    $bill   = $result->fetch_assoc();

    $bill_detail = "";
    $sql         = "SELECT a.* , b.* FROM `bill_detail` AS a
    LEFT JOIN `goods` AS b on a.`goods_sn`=b.`goods_sn`
    WHERE a.`bill_sn`='{$bill_sn}'";
    $result = $mysqli->query($sql) or die($mysqli->connect_error);
    while ($all = $result->fetch_assoc()) {
        $bill_detail .= "
        <tr>
          <td>{$all['goods_title']}</td>
          <td>{$all['goods_price']}</td>
          <td>{$all['goods_amount']}</td>
          <td>{$all['goods_total']} 元</td>
        </tr>";
    }

    $html = "
    <h2>{$bill['bill_date']} 出貨單</h2>
    <p>收貨人：{$bill['user_name']}{$bill['user_sex']}</p>
    <p>收貨地址：{$bill['user_zip']}{$bill['user_county']}{$bill['user_district']}{$bill['user_address']}</p>
    <p>收貨電話：{$bill['user_tel']}</p>
    <table border=\"1\" cellpadding=\"4\">
        <tr>
          <th>商品名稱</th><th>單價</th><th>數量</th><th>小計</th>
        </tr>
        $bill_detail
        <tr>
          <td></td><td></td><td></td><td>{$bill['bill_total']} 元</td>
        </tr>
    </table>
    ";

    $pdf = new TCPDF("P", "mm", "A4", true, 'UTF-8', false);
    $pdf->SetMargins(20, 30);
    $pdf->setHeaderMargin(10);
    $pdf->setPrintHeader(true);
    $pdf->setHeaderFont(array('droidsansfallback', '', 10));
    $pdf->setHeaderData('', 0, "{$bill['user_name']}{$bill['user_sex']}的出貨單", date("Y年m月d日 H:i:s"));
    $pdf->SetAutoPageBreak(true);
    $pdf->setFontSubsetting(true);
    $pdf->SetFont('droidsansfallback', '', 12, '', true);
    // $fontname = TCPDF_FONTS::addTTFfont('class/tcpdf/fonts/font.ttf', 'TrueTypeUnicode');
    // $pdf->SetFont($fontname, '', 12, '', false);

    $pdf->AddPage();
    $pdf->writeHTML($html);
    $pdf->setTextShadow(array('enabled' => true, 'depth_w' => 0.4, 'depth_h' => 0.4, 'color' => array(196, 196, 196)));
    $pdf->Cell(40, 10, "售後服務", 1, 1, 'C', false, '', 2);
    $pdf->setTextShadow(array('enabled' => false));
    $pdf->Write('6', '商品到貨享十天猶豫期之權益（注意！猶豫期非試用期），辦理退貨商品必須是全新狀態且包裝完整，否則將會影響退貨權限。');
    $pdf->Write('6', '如您收到商品，請依正常程序儘速檢查商品，若商品發生新品瑕疵之情形，您可申請更換新品或退貨，請直接點選聯絡我們。');
    $pdf->Write('6', '若您對於購買流程、付款方式、退貨及商品運送方式有疑問，你可直接點選會員中心。', '', false, '', 1);

    $pdf->ln(20);
    $pdf->MultiCell(70, 10, _SHOP_NAME, 0, 'L', 0, 0);
    $pdf->MultiCell(20, 10, '主管', 0, 'L', 0, 0);
    $pdf->Image('images/ck2.png', $pdf->getX(), $pdf->getY() - 10, 30, 30);
    $pdf->MultiCell(30, 10, '', 0, 'L', 0, 0);
    $pdf->MultiCell(20, 10, '承辦', 0, 'L', 0, 0);
    $pdf->MultiCell(30, 10, '', 0, 'L', 0, 1);

    $pdf->Output('出貨單.pdf', 'D');
}
