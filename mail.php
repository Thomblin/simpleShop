New Order

<?php if (isset($name)): ?>
name: <?php echo $name ?>

<?php endif; ?>
<?php if (isset($email)): ?>
email: <?php echo $email ?>

<?php endif; ?>
<?php if (isset($street)): ?>
street: <?php echo $street ?>

<?php endif; ?>
<?php if (isset($zipcode_location)): ?>
postcode / city: <?php echo $zipcode_location ?>

<?php endif; ?>
<?php if (isset($comment)): ?>
comment: <?php echo $comment ?>

<?php endif; ?>

<?php if (isset($selected_items)): ?>
<?php foreach ($selected_items as $item): ?>
<?php echo $item['count'] ?> x <?php echo $item['name'] ?> (<?php echo $item['bundle'] ?>): <?php echo number_format($item['price'], 2, ',', '.') . ' ' . CURRENCY . PHP_EOL ?>
<?php endforeach; ?>
<?php endif; ?>

<b>porto</b>: <?php echo $porto ?>
<?php if ( isset($collectionByTheCustomer) ): ?>
<b> (collection by the customer)</b>
<?php endif; ?>

<?php if (isset($total)): ?>
<b>total</b>: <?php echo $total ?>

<?php endif; ?>