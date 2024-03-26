jQuery(document).ajaxComplete(function () {
  const wc_intend = jQuery(".wc_payment_method.payment_method_intend");
  wc_intend.css({
    display: "flex",
    "align-items": "center",
    margin: "1em 0",
  });

  const label = jQuery("label[for='payment_method_intend']");
  label.css({
    display: "inline-flex",
    "flex-direction": "row-reverse",
    "align-items": "center",
    "justify-content": "start",
  });

  const intendPriceCheckout = jQuery(".intend_price_checkout");
  intendPriceCheckout.css({
    "font-weight": "bold",
    "margin-left": "0.5em",
  });
});
