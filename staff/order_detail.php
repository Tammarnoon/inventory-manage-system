<?php
// รับ order_id จาก URL
$orderId = $_GET['order_id'];

// คิวรี่เพื่อดึงข้อมูลบิลและข้อมูลลูกค้า รวมถึง order_placed_by_id และ order_paid_confirm_user
$selectOrder = $condb->prepare("
    SELECT o.bill_number, o.order_date, o.order_status, o.payment_mode, 
           o.paid_date, c.name, c.tel, o.order_placed_by_id, o.order_paid_confirm_user
    FROM tbl_order AS o
    JOIN tbl_customer AS c ON o.ref_cus_id = c.cus_id
    WHERE o.id = :orderId
");
$selectOrder->bindParam(':orderId', $orderId, PDO::PARAM_INT);
$selectOrder->execute();
$orderData = $selectOrder->fetch(PDO::FETCH_ASSOC);

// ตรวจสอบว่าพบข้อมูลบิลและข้อมูลลูกค้าหรือไม่
if (!$orderData) {
    echo "ไม่พบข้อมูลรายการออเดอร์";
    exit;
}

// เก็บเลขบิลและข้อมูลลูกค้า
$billNumber = $orderData['bill_number'];
$orderDate = $orderData['order_date'];
$orderStatus = $orderData['order_status'];
$paymentMode = $orderData['payment_mode'];
$customerName = $orderData['name'];
$customerTel = $orderData['tel'];
$orderPlacedById = $orderData['order_placed_by_id'];
$orderPaidConfirmUser = $orderData['order_paid_confirm_user'];


// echo '<pre>';
// print_r($orderPlacedById);
// exit;

// คิวรี่เพื่อดึงข้อมูลผู้ทำรายการเพิ่มจาก tbl_user
$selectUserPlacedBy = $condb->prepare("
    SELECT title_name, name, surname
    FROM tbl_user
    WHERE username = :orderPlacedById
");

$selectUserPlacedBy->bindParam(':orderPlacedById', $orderPlacedById, PDO::PARAM_STR); // เปลี่ยนเป็น PDO::PARAM_STR
$selectUserPlacedBy->execute();
$userPlacedByData = $selectUserPlacedBy->fetch(PDO::FETCH_ASSOC);

if ($userPlacedByData) {
    $userPlacedByFullName = $userPlacedByData['title_name'] . ' ' . $userPlacedByData['name'] . ' ' . $userPlacedByData['surname'];
} else {
    $userPlacedByFullName = "ไม่พบข้อมูลผู้ทำรายการ";
}

// echo '<pre>';
// print_r($userPlacedByData);
// exit;


// คิวรี่เพื่อดึงข้อมูลผู้ทำรายการชำระจาก tbl_user
$selectUserPaidBy = $condb->prepare("
    SELECT title_name, name, surname
    FROM tbl_user
    WHERE username = :orderPaidConfirmUser
");
$selectUserPaidBy->bindParam(':orderPaidConfirmUser', $orderPaidConfirmUser, PDO::PARAM_STR);
$selectUserPaidBy->execute();
$userPaidByData = $selectUserPaidBy->fetch(PDO::FETCH_ASSOC);

if ($userPaidByData) {
    $userPaidByFullName = $userPaidByData['title_name'] . ' ' . $userPaidByData['name'] . ' ' . $userPaidByData['surname'];
} else {
    $userPaidByFullName = "";
}

// คิวรี่เพื่อดึงข้อมูล product_id, quantity จาก tbl_order_item ตาม bill_number
$selectOrderItems = $condb->prepare("
    SELECT ref_product_id, quantity 
    FROM tbl_order_item 
    WHERE ref_bill_number = :billNumber
");
$selectOrderItems->bindParam(':billNumber', $billNumber, PDO::PARAM_STR);
$selectOrderItems->execute();
$orderItems = $selectOrderItems->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบว่าพบข้อมูลสินค้าหรือไม่
if (!$orderItems) {
    echo "ไม่พบรายการสินค้าในบิลนี้";
    exit;
}

// เตรียม array เก็บข้อมูลสินค้า
$productDetails = [];
$totalPrice = 0;

foreach ($orderItems as $item) {
    // ดึงข้อมูลสินค้าแต่ละชิ้นจาก tbl_product
    $selectProduct = $condb->prepare("
        SELECT p.product_name, p.product_qty, p.product_price, p.product_img, 
               COALESCE(c.cate_name, 'ไม่มีหมวดหมู่') AS cate_name
        FROM tbl_product AS p
        LEFT JOIN tbl_category AS c ON p.ref_cate_id = c.cate_id
        WHERE p.product_id = :productId
    ");
    $selectProduct->bindParam(':productId', $item['ref_product_id'], PDO::PARAM_INT);
    $selectProduct->execute();
    $productData = $selectProduct->fetch(PDO::FETCH_ASSOC);

    // เก็บข้อมูลสินค้าที่ดึงมาไว้ใน array พร้อมกับจำนวนสินค้า
    if ($productData) {
        $productDetails[] = array_merge($productData, ['quantity' => $item['quantity']]);
        $itemTotal = $productData['product_price'] * $item['quantity'];
        $totalPrice += $itemTotal;
    }
}

// คำนวณ VAT และราคาสุทธิ์
$vatRate = 0.07; // VAT 7%
$vatAmount = $totalPrice * $vatRate;
$netPrice = $totalPrice + $vatAmount;
?>

<!-- HTML ส่วนที่แสดงผลข้อมูลสินค้า -->
<div class="content-wrapper">
    <!-- ข้อมูลลูกค้าและข้อมูลบิล -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-11">
                    <h1>รายละเอียด - <?php echo $billNumber; ?></h1>
                </div>

                <a href="staff_order.php" class="btn btn-secondary">
                    ย้อนกลับ
                </a>
            </div>
        </div>
    </section>

    <!-- แสดงข้อมูลลูกค้าและข้อมูลบิล -->
    <section class="content">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-header">
                        <strong>ข้อมูลลูกค้า</strong>
                    </div>
                    <div class="card-body">
                        <p><strong>ชื่อ :</strong> <?php echo $customerName; ?></p>
                        <p><strong>เบอร์โทร :</strong> <?php echo $customerTel; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-8 mb-3">
                <div class="card">
                    <div class="card-header">
                        <strong>ข้อมูลบิล</strong> &nbsp; &nbsp;
                        <strong>สถานะ :</strong> <?php echo $orderStatus; ?>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card-body">
                                <p><strong>เลขที่บิล :</strong> <?php echo $billNumber; ?></p>
                                <p><strong>วันที่ทำรายการ :</strong> <?php echo $orderDate; ?></p>
                                <p><strong>วันที่ชำระเงิน :</strong> <?php echo isset($orderData['paid_date']) ?
                                $orderData['paid_date'] : 'ยังไม่มีข้อมูล'; ?></p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <p><strong>วิธีชำระเงิน :</strong> <?php echo $paymentMode; ?></p>
                                <p><strong>ผู้เพิ่มรายการ :</strong> <?php echo $userPlacedByFullName; ?></p>
                                <p><strong>ผู้ทำรายการชำระ :</strong> <?php echo $userPaidByFullName; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- แสดงรายละเอียดสินค้าในบิล -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <strong>รายละเอียดสินค้า</strong>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">No.</th>
                                    <th width="40%">ชื่อสินค้า</th>
                                    <th width="15%">หมวดหมู่</th>
                                    <th width="10%">จำนวน</th>
                                    <th width="15%">ราคา</th>
                                    <th width="15%">รวมราคา</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($productDetails as $key => $product) {
                                    $itemTotal = $product['product_price'] * $product['quantity'];
                                ?>
                                    <tr>
                                        <td><?php echo $key + 1; ?></td>
                                        <td><?php echo $product['product_name']; ?></td>
                                        <td><?php echo $product['cate_name']; ?></td>
                                        <td><?php echo $product['quantity']; ?></td>
                                        <td><?php echo number_format($product['product_price'], 2); ?></td>
                                        <td><?php echo number_format($itemTotal, 2); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>รวมทั้งหมด:</strong></td>
                                    <td><?php echo number_format($totalPrice, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>VAT (7%):</strong></td>
                                    <td><?php echo number_format($vatAmount, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right"><strong>ราคาสุทธิ์:</strong></td>
                                    <td><?php echo number_format($netPrice, 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- ฟอร์มสำหรับการชำระเงิน -->
    <section class="content">
        <?php if ($orderStatus !== 'จ่ายแล้ว') { ?>
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <label for="paidmoney">จำนวนเงินที่ชำระ:</label>
                        <input type="number" name="paidmoney" class="form-control" step="0.01" required>
                    </div>
                    <div class="col-md-6">
                        <label for="payment_mode">วิธีการชำระเงิน:</label>
                        <select name="payment_mode" class="form-control" required>
                            <option value="เงินสด">เงินสด</option>
                            <option value="บัตรเครดิต">บัตรเครดิต</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <button type="submit" name="submit-pay" class="btn btn-success btn-block">
                            ยืนยันการชำระเงิน
                        </button>
                    </div>
                    <!-- ปุ่มดูบิล -->
                    <div class="col-md-6">
                        <a href="staff_order.php?order_id=<?php echo $orderId; ?>&act=paid" target="_blank" class="btn btn-info btn-block">
                            ดูบิล
                        </a>
                    </div>
                </div>
            </form>
        <?php } else { ?>
            <!-- แสดงข้อความว่าบิลถูกชำระแล้ว -->
            <div class="alert alert-success">
                บิลนี้ได้รับการชำระเงินแล้ว
            </div>
            <!-- ปุ่มดูบิล -->
            <div class="row">
                <div class="col-md-12">
                    <a href="staff_order.php?order_id=<?php echo $orderId; ?>&act=paid" target="_blank" class="btn btn-info btn-block">
                        ดูบิล
                    </a>
                </div>
            </div>
        <?php } ?>
    </section>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-pay'])) {
    // รับค่าจากฟอร์ม
    $paymentMode = $_POST['payment_mode'];
    $amount = $_POST['paidmoney'];
    $orderPaidConfirmUser = $_SESSION['username']; // สมมุติว่าเก็บชื่อผู้ใช้ใน session

    // ตรวจสอบว่าจำนวนเงินไม่ต่ำกว่าราคาสุทธิ์
    if ($amount < $netPrice) { // เปลี่ยนจาก $totalPrice เป็น $netPrice
        echo '<script>
                  setTimeout(function() {
                      swal({
                          title: "ยอดเงินไม่เพียงพอ",
                          type: "warning"
                      }, function() {
                          window.location = "staff_order.php?order_id=' . $orderId . '&act=detail";
                      });
                  }, 1000);
              </script>';
        exit;
    }

    // คำนวณเงินทอน
    $change = $amount - $netPrice;
    $paidDate = date('d-m-Y');

    // เก็บข้อมูลเข้าฐานข้อมูล
    $updateOrder = $condb->prepare("
    UPDATE tbl_order 
    SET order_status = 'จ่ายแล้ว', 
        payment_mode = :paymentMode, 
        paid_money = :amount, 
        money_change = :change, 
        order_paid_confirm_user = :confirmUser,
        paid_date = :paidDate
        WHERE id = :orderId
        ");
    $updateOrder->bindParam(':paymentMode', $paymentMode);
    $updateOrder->bindParam(':amount', $amount);
    $updateOrder->bindParam(':change', $change);
    $updateOrder->bindParam(':confirmUser', $orderPaidConfirmUser);
    $updateOrder->bindParam(':orderId', $orderId);
    $updateOrder->bindParam(':paidDate', $paidDate);

    if ($updateOrder->execute()) {
        echo '<script>
                  setTimeout(function() {
                      swal({
                          title: "ทำรายการสำเร็จ",
                          type: "success"
                      }, function() {
                          window.location = "staff_order.php?order_id=' . $orderId . '&act=detail";
                      });
                  }, 1000);
              </script>';
    } else {
        echo "เกิดข้อผิดพลาดในการทำรายการ";
    }
}
