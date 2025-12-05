<?php echo t('mail.new_order') ?>

<?php if (isset($name)): ?>
    <?php echo t('name') ?>: <?php echo $name ?>

<?php endif; ?>
<?php if (isset($email)): ?>
    <?php echo t('email') ?>: <?php echo $email ?>

<?php endif; ?>
<?php if (isset($street)): ?>
    <?php echo t('street') ?>: <?php echo $street ?>

<?php endif; ?>
<?php if (isset($zipcode_location)): ?>
    <?php echo t('zip_city') ?>: <?php echo $zipcode_location ?>

<?php endif; ?>
<?php if (isset($comment)): ?>
    <?php echo t('comment') ?>: <?php echo $comment ?>

<?php endif; ?>

<?php if (isset($selected_items)): ?>
    <?php foreach ($selected_items as $item): ?>
        <?php echo $item['amount'] ?> x <?php echo $item['name'] ?> (<?php echo $item['bundle'] ?>):
        <?php echo number_format($item['price'], 2, ',', '.') . ' ' . $currency . ($item['out_of_stock'] ? ' <b style="color:red">out of stock</b>' : '') . PHP_EOL ?>
    <?php endforeach; ?>
<?php endif; ?>

<b><?php echo t('porto') ?></b>: <?php echo $porto ?>
<?php if (isset($collectionByTheCustomer)): ?>
    <b> <?php echo t('form.will_collect') ?></b>
<?php endif; ?>

<?php if (isset($total)): ?>
    <b><?php echo t('total') ?></b>: <?php echo $total ?>

<?php endif; ?>