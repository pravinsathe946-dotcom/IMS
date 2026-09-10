
<?php
file_put_contents(
    __DIR__ . '/cron_log.txt',
    date('Y-m-d H:i:s') . " - SCRIPT STARTED\n",
    FILE_APPEND
);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
file_put_contents(
    __DIR__ . '/cron_log.txt',
    date('Y-m-d H:i:s') .
    " | SOURCE=" .
    ($_GET['source'] ?? 'manual') .
    " | METHOD=" .
    $_SERVER['REQUEST_METHOD'] .
    " | USER_AGENT=" .
    ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') .
    "\n",
    FILE_APPEND
);

// --------------------------------------------------
// SECURITY: CRON SECRET
// --------------------------------------------------

// Create your own secret value.
// Example: MyIMS_2026_Alert_8392
$cronSecret = 'Q+C394wAkFH/24w1glTM2p3O7hXamFWaQjMYLUa/SiA=';

// Only allow cron-job.org to execute this file
//if (
//    !isset($_GET['key']) ||
 //   $_GET['key'] !== $cronSecret
//) {
 //   http_response_code(403);
 //   exit('Access denied');
//}


// --------------------------------------------------
// LOAD PHPMailer
// --------------------------------------------------

require __DIR__ . '/vendor/autoload.php';


// --------------------------------------------------
// DATABASE CONNECTION
// --------------------------------------------------

$db_host = "sql306.infinityfree.com";
$db_user = "if0_42355711";
$db_pass = "Propravin123";
$db_name = "if0_42355711_inventory";

$conn = new mysqli(
    $db_host,
    $db_user,
    $db_pass,
    $db_name
);

if ($conn->connect_error) {
    exit("Database connection failed");
}

$conn->set_charset("utf8mb4");


// --------------------------------------------------
// FIND LOW STOCK PRODUCTS
// --------------------------------------------------

$sql_low_stock = "
    SELECT name, quantity
    FROM products
    WHERE quantity < 5
    ORDER BY quantity ASC
";

$result_low_stock = $conn->query($sql_low_stock);
file_put_contents(
    __DIR__ . '/cron_log.txt',
    date('Y-m-d H:i:s') .
    " - SQL RESULT: " .
    ($result_low_stock ? "SUCCESS" : "FAILED") .
    " - ROWS: " .
    ($result_low_stock ? $result_low_stock->num_rows : 0) .
    "\n",
    FILE_APPEND
);


// --------------------------------------------------
// CHECK IF LOW STOCK PRODUCTS EXIST
// --------------------------------------------------

if (
    $result_low_stock &&
    $result_low_stock->num_rows > 0
) {

    $productNames = "";

    while (
        $low_stock_row =
        $result_low_stock->fetch_assoc()
    ) {

        $productName = htmlspecialchars(
            $low_stock_row['name'],
            ENT_QUOTES,
            'UTF-8'
        );

        $quantity = htmlspecialchars(
            $low_stock_row['quantity'],
            ENT_QUOTES,
            'UTF-8'
        );

        $productNames .= "
            <p style='
                padding:10px;
                margin:5px 0;
                background-color:#fff3cd;
                border:1px solid #ffc107;
                border-radius:5px;
            '>
                <strong>{$productName}</strong>
                - Quantity:
                <strong>{$quantity}</strong>
            </p>
        ";
    }

file_put_contents(
    __DIR__ . '/cron_log.txt',
    date('Y-m-d H:i:s') . " - ABOUT TO SEND EMAIL\n",
    FILE_APPEND
);
    // --------------------------------------------------
    // SEND EMAIL
    // --------------------------------------------------

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // Gmail account
        $mail->Username = 'pravinsathe946@gmail.com';

        // NEW Gmail App Password
        $mail->Password = 'gfet wmuc muly npio';

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;


        // --------------------------------------------------
        // SENDER
        // --------------------------------------------------

        $mail->setFrom(
            'pravinsathe946@gmail.com',
            'IMS Application'
        );


        // --------------------------------------------------
        // RECEIVERS
        // --------------------------------------------------

        $mail->addAddress(
            'pravinsathe946@gmail.com',
            'vinay.patil080@gmail.com ',
            'Admin'
        );

        // Add another recipient like this:
        // $mail->addAddress(
        //     'another@example.com',
        //     'Another Admin'
        // );


        // --------------------------------------------------
        // EMAIL BODY
        // --------------------------------------------------

        $mail->isHTML(true);

        $mail->Subject =
            'Low Stock Product Alert';

        $mail->Body = "
            <html>
            <body>

                <h2 style='color:#dc3545;'>
                    ⚠️ Low Stock Product Alert
                </h2>

                <p>Hello Admin,</p>

                <p>
                    The following products have a
                    quantity <strong>less than 5</strong>:
                </p>

                {$productNames}

                <p>
                    Please check the inventory and
                    replenish the stock if required.
                </p>

                <p>
                    Regards,<br>
                    <strong>IMS Website</strong>
                </p>

            </body>
            </html>
        ";


        // --------------------------------------------------
        // SEND
        // --------------------------------------------------

        $mail->send();
        file_put_contents(
    __DIR__ . '/cron_log.txt',
    date('Y-m-d H:i:s') . " - EMAIL SENT SUCCESSFULLY\n",
    FILE_APPEND
);

        echo "Low stock email sent successfully.";

    } catch (Exception $e) {
    file_put_contents(
        __DIR__ . '/cron_log.txt',
        date('Y-m-d H:i:s') .
        " - EMAIL FAILED: " .
        $mail->ErrorInfo .
        "\n",
        FILE_APPEND
    );
        http_response_code(500);

        echo "Email could not be sent.";
    }

} else {

    echo "No products have quantity less than 5.";
}


// --------------------------------------------------
// CLOSE DATABASE
// --------------------------------------------------

$conn->close();

?>
```
