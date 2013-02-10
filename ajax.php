<?php

include('config.php');
include('template.php');

$result = array();
$error = array();
$price = 0;

$mail = new Template();

foreach ($allowed_textfields as $name => $required) {
    if (!empty($_POST[$name])) {
        $mail->add($name, $_POST[$name]);
    } elseif (REQUIRED == $required) {
        $error['req'] = 'You need to fill in all the required fields!';
    }
}
$selected_items = array();
$porto = 0;
foreach ($items as $item) {
    if (isset($_POST[$item['id']])) {
        foreach ($item['bundles'] as $bundle) {
            if (isset($_POST[$item['id']][$bundle['subid']])) {
                $count = (int)$_POST[$item['id']][$bundle['subid']];
                $count = max($count, $bundle['min_count']);
                $count = min($count, $bundle['max_count']);

                if (0 < $count) {
                    $price += $count * $bundle['price'];

                    $selected_items[] = array(
                        'name' => $item['name'],
                        'bundle' => $bundle['name'],
                        'count' => $count,
                        'price' => $count * $bundle['price'],
                    );
                    $porto = max($porto, $item['min_porto']);
                }
            }
        }
    }
}
$mail->add('selected_items', $selected_items);

if ( isset($_POST['collectionByTheCustomer']) ) {
    $porto = 0;
}

$result['price'] = number_format($price, 2, ',', '.') . ' '.CURRENCY;
$result['porto'] = number_format($porto, 2, ',', '.') . ' '.CURRENCY;

if (!isset($_GET['price_only'])) {
    $mail->add('porto', $result['porto']);
    $mail->add('total', $result['price']);
    if ( isset($_POST['collectionByTheCustomer']) ) {
        $mail->add('collectionByTheCustomer', true);
    }

    $result['mail'] = $mail->parse('mail.php', false);


    if (!empty($error)) {
        $result['error'] = implode("\n", $error);
    }
}

function mail_utf8($to, $from_user, $from_email, $subject = '(No subject)', $message = '')
{
    $from_user = "=?UTF-8?B?" . base64_encode($from_user) . "?=";
    $subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

    $headers = "From: $from_user <$from_email>\r\n" .
        "MIME-Version: 1.0" . "\r\n" .
        "Content-type: text/html; charset=UTF-8" . "\r\n";

    return mail($to, $subject, $message, $headers);
}

if (isset($_GET['mail']) && !isset($result['error'])) {
    $text = nl2br($result['mail']);

    mail_utf8(MAIL_ADDRESS, MAIL_USER, MAIL_ADDRESS, MAIL_SUBJECT, $text);
    mail_utf8($_POST['email'], MAIL_USER, MAIL_ADDRESS, MAIL_SUBJECT, $text);
}

echo json_encode($result);

?>