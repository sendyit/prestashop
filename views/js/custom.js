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

var from_name;
var from_vicinity;
var from_lat;
var from_long;
function initMap() {} // now it IS a function and it is in global

$(document).ready(function () {
    // put your jQuery code here
    $("#api_long").closest(".form-group").hide();
    $("#api_lat").closest(".form-group").hide();

   
    console.log('ready');
    $(() => {
      initMap = function() {
        console.log('initializing maps');
        // put your jQuery code here
        var country = 'ke';
        var options = {componentRestrictions: {country: country}};
        var autocomplete = new google.maps.places.Autocomplete($("#api_from")[0], options);

        google.maps.event.addListener(autocomplete, 'place_changed', function () {
            var place = autocomplete.getPlace();
            from_name = place.name;
            from_vicinity = place.vicinity;
            from_lat = place.geometry.location.lat();
            from_long = place.geometry.location.lng();
            sendData(from_name, from_lat, from_long);

        });

      }
    });

    function sendData(from_name, from_lat, from_long) {
        var from_name = from_name;
        var from_lat = from_lat;
        var from_long = from_long;

        $("#api_lat").val(from_lat);
        $("#api_long").val(from_long);


        // $.ajax({
        //     type: "POST",
        //     url: '/prestashop/modules/sendyapimodule1/custom/dataReceiver.php',
        //     data: {
        //         from_name: from_name,
        //         from_lat: from_lat,
        //         from_long: from_long
        //     }
        // })
        //     .success(function (res) {
        //         console.log(res);
        //
        //     })
        //     .fail(function (er) {
        //             console.log(er);
        //         })
    }
});