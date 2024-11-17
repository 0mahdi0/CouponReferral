jQuery(document).ready(function($) {
    $('.product-item').each(function() {
        var $productItem = $(this);
        var productId = $productItem.data('product_id');
        var $quantityInput = $productItem.find('.quantity-input');

        $productItem.find('.minus-btn').on('click', function(e) {
            e.preventDefault();
            var quantity = parseInt($quantityInput.val());
            if (quantity > 0) {
                quantity--;
                $quantityInput.val(quantity);
                updateCart(productId, quantity);
            }
        });

        $productItem.find('.plus-btn').on('click', function(e) {
            e.preventDefault();
            var quantity = parseInt($quantityInput.val());
            quantity++;
            $quantityInput.val(quantity);
            updateCart(productId, quantity);
        });
    });

    function updateCart(productId, quantity) {
        $.ajax({
            type: 'POST',
            url: doctorProductList.ajax_url,
            data: {
                action: 'update_cart_quantity',
                product_id: productId,
                quantity: quantity
            },
            success: function(response) {
                if (response.success) {
                    // Optionally update cart totals or notify the user
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('An error occurred while updating the cart.');
            }
        });
    }
});
