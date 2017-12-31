<?php

if (!function_exists('json_encode'))
{
    function json_encode($a=false)
    {
        if (is_null($a)) return 'null';
        if ($a === false) return 'false';
        if ($a === true) return 'true';
        if (is_scalar($a))
        {
            if (is_float($a))
            {
                // Always use "." for floats.
                return floatval(str_replace(",", ".", strval($a)));
            }

            if (is_string($a))
            {
                static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
                return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
            }
            else
                return $a;
        }
        $isList = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a))
        {
            if (key($a) !== $i)
            {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList)
        {
            foreach ($a as $v) $result[] = json_encode($v);
            return '[' . join(',', $result) . ']';
        }
        else
        {
            foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
            return '{' . join(',', $result) . '}';
        }
    }
}

include('autoload.php');
$config = new Config();
$db = new Db($config);
$items = new Items($db);

$shopItems = $items->getItems();

$result = array();
$error = array();
$price = 0;

$mail = new Template();

foreach ($config->allowedTextfields as $name => $required) {
    if (!empty($_POST[$name])) {
        $mail->add($name, $_POST[$name]);
    } elseif (Config::REQUIRED == $required) {
        $error['req'] = 'You need to fill in all the required fields!';
    }
}
$selected_items = array();
$orders = array();
$anyOutOfStock = false;
$porto = 0;

foreach ($shopItems as $item) {
    if (isset($_POST[$item['item_id']])) {
        foreach ($item['bundles'] as $bundle) {
            if (isset($_POST[$item['item_id']][$bundle['bundle_id']])) {
                $amount = (int)$_POST[$item['item_id']][$bundle['bundle_id']];
                $amount = max($amount, $bundle['min_count']);
                $amount = min($amount, $bundle['max_count']);

                $outOfStock = $bundle['inventory'] < $amount;

                if ($outOfStock) {
                    $anyOutOfStock = true;
                }

                if (0 < $amount) {
                    $price += $amount * $bundle['price'];

                    $selected_items[] = array(
                        'name' => $item['name'],
                        'bundle' => $bundle['name'],
                        'amount' => min($amount, $bundle['inventory']),
                        'price' => min($amount, $bundle['inventory']) * $bundle['price'],
                        'out_of_stock' => $outOfStock
                    );
                    $porto = max($porto, $item['min_porto']);

                    $orders[] = [
                      'amount' => $amount,
                      'bundle_id' => $bundle['bundle_id']
                    ];
                }
            }
        }
    }
}
$mail->add('selected_items', $selected_items);

if ( isset($_POST['collectionByTheCustomer']) ) {
    $porto = 0;
}

$price += $porto;
$result['price'] = number_format($price, 2, ',', '.') . ' '.Config::CURRENCY;
$result['porto'] = number_format($porto, 2, ',', '.') . ' '.Config::CURRENCY;
$result['order'] = $anyOutOfStock ? 0 : 1;

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

    if (!$items->orderItem($orders)) {
        $result['error'] = 'Could not proceed. No items left';
    } else {
        $text = nl2br($result['mail']);

        mail_utf8(Config::MAIL_ADDRESS, Config::MAIL_USER, Config::MAIL_ADDRESS, Config::MAIL_SUBJECT, $text);
        mail_utf8($_POST['email'], Config::MAIL_USER, Config::MAIL_ADDRESS, Config::MAIL_SUBJECT, $text);
    };
}

echo json_encode($result);