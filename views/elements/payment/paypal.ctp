<p><?php __('Passer au paiement s�curis� avec Paypal.'); ?></p>

<?php debug($buttonData);  ?>
<?php echo $paypal->button('Checkout', $buttonData);  ?>