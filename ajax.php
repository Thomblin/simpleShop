<?php

if (!function_exists('json_encode')) {
    /**
     * @param bool|array $a
     * @return string
     */
    function json_encode($a = false)
    {
        if (is_null($a))
            return 'null';
        if ($a === false)
            return 'false';
        if ($a === true)
            return 'true';
        if (is_scalar($a)) {
            if (is_float($a)) {
                // Always use "." for floats.
                return floatval(str_replace(",", ".", strval($a)));
            }

            if (is_string($a)) {
                static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
                return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
            } else
                return $a;
        }
        $isList = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
            if (key($a) !== $i) {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList) {
            foreach ($a as $v)
                $result[] = json_encode($v);
            return '[' . join(',', $result) . ']';
        } else {
            foreach ($a as $k => $v)
                $result[] = json_encode($k) . ':' . json_encode($v);
            return '{' . join(',', $result) . '}';
        }
    }
}

include('autoload.php');
$config = new Config();
$db = new Db($config);
$items = new Items($db);

Translation::init($config);

$shopItems = $items->getItems();

$result = array();
$error = array();
$price = 0;

$mail = new Template();

foreach ($config->allowedTextfields as $name => $required) {
    if (!empty($_POST[$name])) {
        $mail->add($name, $_POST[$name]);
    } elseif (Config::REQUIRED == $required) {
        $error['req'] = t('error.fill_required');
    }
}

$selected_items = array();
$orders = array();
$anyOutOfStock = false;
$porto = 0;

foreach ($shopItems as $item) {
    if (!isset($_POST[$item['item_id']])) {
        continue;
    }

    // Determine selected option per group for this item
    $selectedOptionByGroup = [];
    if (!empty($item['option_groups'])) {
        foreach ($item['option_groups'] as $group) {
            $fieldName = 'item_' . $item['item_id'] . '_option_' . $group['group_id'];
            if (isset($_POST[$fieldName]) && $_POST[$fieldName] !== '') {
                $selectedOptionByGroup[$group['group_id']] = (int) $_POST[$fieldName];
            }
        }
    }

    foreach ($item['bundles'] as $bundle) {
        if (!isset($_POST[$item['item_id']][$bundle['bundle_id']])) {
            continue;
        }

        $postValue = $_POST[$item['item_id']][$bundle['bundle_id']];

        // If new nested structure (bundle_option_id => amount)
        if (is_array($postValue)) {
            foreach ($postValue as $boId => $rawAmount) {
                $amount = (int) $rawAmount;

                // Find exact option by bundle_option_id - all values come from bundle_options
                $effective = [
                    'price' => 0.0,
                    'min_count' => 0,
                    'max_count' => 1,
                    'inventory' => 0,
                    'bundle_option_id' => (int) $boId,
                    'option_description' => null
                ];

                if (!empty($item['option_groups'])) {
                    foreach ($item['option_groups'] as $group) {
                        foreach ($group['options'] as $opt) {
                            if ($opt['bundle_id'] == $bundle['bundle_id'] && isset($opt['bundle_option_id']) && (int) $opt['bundle_option_id'] === (int) $boId) {
                                $effective['price'] = (float) $opt['price'];
                                $effective['min_count'] = (int) $opt['min_count'];
                                $effective['max_count'] = (int) $opt['max_count'];
                                $effective['inventory'] = (int) $opt['inventory'];
                                $effective['option_description'] = !empty($opt['option_description']) ? $opt['option_description'] : $opt['option_name'];
                                break 2;
                            }
                        }
                    }
                } else {
                    // If no option_groups, get the first bundle_option for this bundle
                    $bundleOptions = $items->getBundleOptionsForBundle($bundle['bundle_id']);
                    if (empty($bundleOptions)) {
                        throw new RuntimeException("Bundle {$bundle['bundle_id']} has no bundle_options. Every bundle must have at least one bundle_option.");
                    }
                    $firstOption = $bundleOptions[0];
                    if ((int) $firstOption['bundle_option_id'] === (int) $boId) {
                        $effective['price'] = (float) $firstOption['price'];
                        $effective['min_count'] = (int) $firstOption['min_count'];
                        $effective['max_count'] = (int) $firstOption['max_count'];
                        $effective['inventory'] = (int) $firstOption['inventory'];
                    } else {
                        throw new RuntimeException("Bundle option ID {$boId} not found for bundle {$bundle['bundle_id']}");
                    }
                }

                // Apply limits and stock checks
                $amount = max($amount, $effective['min_count']);
                $amount = min($amount, $effective['max_count']);

                $outOfStock = $effective['inventory'] < $amount;
                if ($outOfStock) {
                    $anyOutOfStock = true;
                }

                if ($amount > 0) {
                    $price += $amount * $effective['price'];
                    $selected_items[] = array(
                        'name' => $item['name'],
                        'bundle' => $bundle['name'] . (!empty($effective['option_description']) ? ' - ' . $effective['option_description'] : ''),
                        'amount' => min($amount, $effective['inventory']),
                        'price' => min($amount, $effective['inventory']) * $effective['price'],
                        'out_of_stock' => $outOfStock
                    );
                    $porto = max($porto, $item['min_porto']);

                    $orders[] = [
                        'amount' => $amount,
                        'bundle_option_id' => (int) $boId
                    ];
                }
            }
        } else {
            // Legacy scalar structure
            $amount = (int) $postValue;

            // All values must come from bundle_options - no bundle-level fallback
            $effective = [
                'price' => 0.0,
                'min_count' => 0,
                'max_count' => 1,
                'inventory' => 0,
                'bundle_option_id' => null,
                'option_description' => null
            ];

            // Try to find a matching selected option for this bundle
            if (!empty($item['option_groups'])) {
                foreach ($item['option_groups'] as $group) {
                    $selectedOptionId = isset($selectedOptionByGroup[$group['group_id']]) ? $selectedOptionByGroup[$group['group_id']] : null;
                    if (!$selectedOptionId) {
                        continue;
                    }

                    foreach ($group['options'] as $opt) {
                        if ($opt['bundle_id'] == $bundle['bundle_id'] && $opt['option_id'] == $selectedOptionId) {
                            $effective['price'] = (float) $opt['price'];
                            $effective['min_count'] = (int) $opt['min_count'];
                            $effective['max_count'] = (int) $opt['max_count'];
                            $effective['inventory'] = (int) $opt['inventory'];
                            $effective['bundle_option_id'] = isset($opt['bundle_option_id']) ? (int) $opt['bundle_option_id'] : null;
                            $effective['option_description'] = !empty($opt['option_description']) ? $opt['option_description'] : $opt['option_name'];
                            break 2; // Found matching option for this bundle
                        }
                    }
                }
            } else {
                // If no option_groups, get the first bundle_option for this bundle
                $bundleOptions = $items->getBundleOptionsForBundle($bundle['bundle_id']);
                if (empty($bundleOptions)) {
                    throw new RuntimeException("Bundle {$bundle['bundle_id']} has no bundle_options. Every bundle must have at least one bundle_option.");
                }
                $firstOption = $bundleOptions[0];
                $effective['price'] = (float) $firstOption['price'];
                $effective['min_count'] = (int) $firstOption['min_count'];
                $effective['max_count'] = (int) $firstOption['max_count'];
                $effective['inventory'] = (int) $firstOption['inventory'];
                $effective['bundle_option_id'] = (int) $firstOption['bundle_option_id'];
            }

            // Apply min/max and stock checks with effective values
            $amount = max($amount, $effective['min_count']);
            $amount = min($amount, $effective['max_count']);

            $outOfStock = $effective['inventory'] < $amount;
            if ($outOfStock) {
                $anyOutOfStock = true;
            }

            if ($amount > 0) {
                $price += $amount * $effective['price'];

                $selected_items[] = array(
                    'name' => $item['name'],
                    'bundle' => $bundle['name'] . (!empty($effective['option_description']) ? ' - ' . $effective['option_description'] : ''),
                    'amount' => min($amount, $effective['inventory']),
                    'price' => min($amount, $effective['inventory']) * $effective['price'],
                    'out_of_stock' => $outOfStock
                );

                $porto = max($porto, $item['min_porto']);

                $order = ['amount' => $amount];
                if (!empty($effective['bundle_option_id'])) {
                    $order['bundle_option_id'] = $effective['bundle_option_id'];
                } else {
                    $order['bundle_id'] = $bundle['bundle_id'];
                }
                $orders[] = $order;
            }
        }
    }
}

$mail->add('selected_items', $selected_items);

if (isset($_POST['collectionByTheCustomer'])) {
    $porto = 0;
}

$price += $porto;
$result['price'] = number_format($price, 2, ',', '.') . ' ' . Config::CURRENCY;
$result['porto'] = number_format($porto, 2, ',', '.') . ' ' . Config::CURRENCY;
$result['order'] = $anyOutOfStock ? 0 : 1;

if (!isset($_GET['price_only'])) {
    $mail->add('porto', $result['porto']);
    $mail->add('total', $result['price']);
    if (isset($_POST['collectionByTheCustomer'])) {
        $mail->add('collectionByTheCustomer', true);
    }

    $result['mail'] = $mail->parse('mail.php', false);


    if (!empty($error)) {
        $result['error'] = implode("\n", $error);
    }
}

function mail_utf8($to, $from_user, $from_email, $subject = '', $message = '')
{
    // Use MailService instead of calling mail() directly
    $mailService = new MailService();
    return $mailService->send($to, $subject, $message, $from_email, $from_user);
}

if (isset($_GET['mail']) && !isset($result['error'])) {

    if (!$items->orderItem($orders)) {
        $result['error'] = t('error.out_of_stock');
    } else {
        $text = nl2br($result['mail']);

        // Use MailService for sending emails
        $mailService = new MailService();
        $mailService->send(Config::MAIL_ADDRESS, t('mail.subject'), $text, Config::MAIL_ADDRESS, Config::MAIL_USER);
        $mailService->send($_POST['email'], t('mail.subject'), $text, Config::MAIL_ADDRESS, Config::MAIL_USER);
    }
    ;
}

echo json_encode($result);