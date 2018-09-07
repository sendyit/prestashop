/**

 * NOTICE OF LICENSE

 *

 * This file is licenced under the Software License Agreement.

 * With the purchase or the installation of the software in your application

 * you accept the licence agreement.

 *

 * You must not modify, adapt or create derivative works of this source code

 *

 *  @author    Dervine N

 *  @copyright Sendy Limited

 *  @license   LICENSE.txt

 */

function initMap() {} // now it IS a function and it is in global
$(document).ready(function () {
    $("#api_long").closest(".form-group").hide();
    $("#api_lat").closest(".form-group").hide();
    $("#name").prop("readonly", true);
    $("#delay_1").prop("readonly", true);
    $("#grade").prop("readonly", true);
    $("#range_behavior").attr("readonly", true);
    $("#max_weight").attr("readonly", true);
    $("#max_depth").attr("readonly", true);
    $("#max_height").attr("readonly", true);
    $("#max_width").attr("readonly", true);
    $("#id_tax_rules_group").attr("readonly", true);
    $('#url').html('<a href="https://apptest.sendyit.com/biz/coporate/track_order_new/@">Google</a>');
    $("#url").prop("readonly", true);
    $("#attachement_filename").attr("readonly", true);
    $('#billing_price').attr('checked', true);
    $('#groupBox_1').prop('checked', true);
    $('#groupBox_2').prop('checked', true);
    $('#groupBox_3').prop('checked', true);
    $('#zone_4').prop('checked', true);
    $('sendy_shipping').length || $('alert-success:first').attr('id','sendy_shipping');
    $('.alert-success').html("Congratulations! You completed your Sendy configuration. Sendy delivery should now be visible for your customers.");
    $('.module_confirmation').html("Congratulations! You completed this step. Go to \'Shipping -> Carriers'\ on the left side menu to continue the setup.");
    $('element').attr('id', 'value');
    $('[name="range_sup[0]"]').val("10000000.00");
    console.log('ready');
    $(() => {
      initMap = function() {
        console.log('initializing maps');
        let country = 'ke';
        let options = {componentRestrictions: {country: country}};
        let autocomplete = new google.maps.places.Autocomplete($("#api_from")[0], options);
        google.maps.event.addListener(autocomplete, 'place_changed',
            function () {
            let to_name = place.name;
            let place = autocomplete.getPlace();
            let from_lat = place.geometry.location.lat();
            let from_long = place.geometry.location.lng();
            sendData(from_lat, from_long);
        });
      }
    });

    function sendData(from_lat, from_long){
        $("#api_lat").val(from_lat);
        $("#api_long").val(from_long);
    }
});