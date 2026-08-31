<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --------------------------------------------------
// LOAD PHPMailer
// --------------------------------------------------

require __DIR__ . '/vendor/autoload.php';


// --------------------------------------------------
// DATABASE CONNECTION
// --------------------------------------------------

// CHANGE THESE TO YOUR LIVE SERVER DATABASE DETAILS

$db_host = "sql306.infinityfree.com";
$db_user = 'if0_42355711';
$db_pass = "Propravin123";
$db_name = "if0_42355711_inventory";

$conn = new mysqli(
    $db_host,
    $db_user,
    $db_pass,
    $db_name
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
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


// --------------------------------------------------
// CHECK IF LOW STOCK PRODUCTS EXIST
// --------------------------------------------------

if ($result_low_stock && $result_low_stock->num_rows > 0) {

    $productNames = "";

    while ($low_stock_row = $result_low_stock->fetch_assoc()) {

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
                - Quantity: <strong>{$quantity}</strong>
            </p>
        ";
    }


    // --------------------------------------------------
    // SEND EMAIL
    // --------------------------------------------------

    $mail = new PHPMailer(true);

    try {

        // SMTP
        $mail->isSMTP();

        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        // Gmail account
        $mail->Username   = 'pravinsathe946@gmail.com';

        // NEW Gmail App Password
        $mail->Password   = 'gfet wmuc muly npio';

        // Gmail STARTTLS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;


        // --------------------------------------------------
        // SENDER
        // --------------------------------------------------

        $mail->setFrom(
            'pravinsathe946@gmail.com',
            'IMS Website'
        );


        // --------------------------------------------------
        // RECEIVER
        // --------------------------------------------------

        $mail->addAddress(
            'pravinsathe946@gmail.com',
            'vinay.patil080@gmail.com ',
            'Admin'
        );


        // --------------------------------------------------
        // EMAIL BODY
        // --------------------------------------------------

        $mail->isHTML(true);

        $mail->Subject = 'Low Stock Product Alert';

        $mail->Body = "
            <html>
            <body>

                <h2 style='color:#dc3545;'>
                    ⚠️ Low Stock Product Alert
                </h2>

                <p>Hello Admin,</p>

                <p>
                    The following products have a quantity
                    <strong>less than 5</strong>:
                </p>

                {$productNames}

                <p>
                    Please check the inventory and replenish
                    the stock if required.
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

        echo "
            <p style='color:green;'>
                <strong>Email sent successfully.</strong>
            </p>
        ";

    } catch (Exception $e) {

        echo "
            <p style='color:red;'>
                <strong>Email could not be sent.</strong>
            </p>
        ";

        echo "
            <p>
                Mailer Error:
                " . htmlspecialchars($mail->ErrorInfo) . "
            </p>
        ";
    }

} else {

    echo "
        <p style='color:green;'>
            No products have quantity less than 5.
        </p>
    ";
}


// --------------------------------------------------
// CLOSE DATABASE
// --------------------------------------------------

$conn->close();

?>