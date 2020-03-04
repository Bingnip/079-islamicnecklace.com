/* Checkout wizard js */
function initShippingFormHandlers() {
    $("#order_ship_address_attributes_first_name").on("keyup",
    function() {
        $("#order_bill_address_attributes_first_name").val($("#order_ship_address_attributes_first_name").val())
    }),
	$("#order_ship_address_attributes_last_name").on("keyup",
    function() {
        $("#order_bill_address_attributes_last_name").val($("#order_ship_address_attributes_last_name").val())
    }),
	$("#order_ship_address_attributes_state").on("change",
    function() {
        $("#order_bill_address_attributes_state").val($("#order_ship_address_attributes_state").val())
    })
}
function initPaymentFormHandlers() {
    $("input#same_as_shipping_address").click(function() {
        $(this).prop("checked") ? copyShippingAddress() : clearShippingAddress()
    })
}
function copyShippingAddress() {
    $("#order_bill_address_attributes_first_name").val($("#order_ship_address_attributes_first_name").val()),
    $("#order_bill_address_attributes_last_name").val($("#order_ship_address_attributes_last_name").val()),
    $("#order_bill_address_attributes_address1").val($("#order_ship_address_attributes_address1").val()),
    $("#order_bill_address_attributes_address2").val($("#order_ship_address_attributes_address2").val()),
    $("#order_bill_address_attributes_city").val($("#order_ship_address_attributes_city").val()),
    $("#order_bill_address_attributes_state").val($("#order_ship_address_attributes_state").val()),
    $("#order_bill_address_attributes_postal_code").val($("#order_ship_address_attributes_postal_code").val()),
    $("#order_bill_address_attributes_country").val($("#order_ship_address_attributes_country").val()),
	$("#order_bill_state").val($("#order_ship_state").val())
	
}
function clearShippingAddress() {
    $("#order_bill_address_attributes_first_name").val(""),
    $("#order_bill_address_attributes_last_name").val(""),
    $("#order_bill_address_attributes_address1").val(""),
    $("#order_bill_address_attributes_address2").val(""),
    $("#order_bill_address_attributes_city").val(""),
    $("#order_bill_address_attributes_state").val(""),
    $("#order_bill_address_attributes_postal_code").val(""),
	$("#order_bill_state").val("")
}


$(document).ready(function() {
   
   
   initPaymentFormHandlers();
   initShippingFormHandlers();
    
});
