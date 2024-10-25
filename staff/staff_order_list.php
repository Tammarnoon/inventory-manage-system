<?php
// คิวรี่ข้อมูลจาก tbl_order มาแสดงในตาราง
$selectOrder = $condb->prepare("SELECT o.id, o.bill_number, c.name, c.tel, o.order_date, o.order_status, o.payment_mode 
FROM tbl_order AS o
JOIN tbl_customer AS c ON o.ref_cus_id = c.cus_id
ORDER BY o.id ASC");
$selectOrder->execute();

// ดึงข้อมูลทั้งหมด
$orderData = $selectOrder->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>รายการ Order
          <a href="staff_order.php?act=insert" class="btn btn-primary">เพิ่มข้อมูล</a>
          </h1>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="card card-outline card-info">
          <div class="card-body">
            <div class="card card-primary">

            <!-- ตารางแสดงรายการ Order -->
            <table id="example1" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th width="5%">No.</th>
                  <th width="15%">หมายเลขรายการ</th>
                  <th width="20%">ชื่อ</th>
                  <th width="10%">เบอร์</th>
                  <th width="15%">วันทำรายการ</th>
                  <th width="10%">สถานะ</th>
                  <th width="10%">การชำระเงิน</th>
                  <th width="10%">Action</th>
                </tr>
              </thead>

              <tbody>
                <?php
                // แสดงข้อมูลแต่ละแถว
                foreach ($orderData as $key => $order) {
                  // echo '<pre>';
                  // print_r($orderData);
                  // exit;
                ?>
                  <tr>
                    <td><?php echo $key + 1; ?></td>
                    <td><?php echo $order['bill_number']; ?></td>
                    <td><?php echo $order['name']; ?></td>
                    <td><?php echo $order['tel']; ?></td>
                    <td><?php echo $order['order_date']; ?></td>
                    <td><?php echo $order['order_status']; ?></td>
                    <td><?php echo $order['payment_mode']; ?></td>
                    <td>
                      <a href="staff_order.php?order_id=<?php echo $order['id']; ?>&act=detail" 
                      class="btn btn-info btn-sm">ดูรายละเอียด</a>
                    </td>
                  </tr>
                <?php
                }
                ?>
              </tbody>

            </table>

          </div>
        </div>
      </div>
    </div>
  </section>
</div>
