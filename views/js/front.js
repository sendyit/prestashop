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

var to_name;
var to_vicinity;
var to_lat;
var to_long;

function initMap() {
} // now it IS a function and it is in global


$(document).ready(function () {
    $(() => {
        initMap = function () {
            console.log("initiliazing maps");
            // put your jQuery code here
            var country = 'ke';
            var options = {componentRestrictions: {country: country}};
            var autocomplete = new google.maps.places.Autocomplete($("#api_to")[0], options);
            var previousAddress = $.cookie('deliveryAddress');
            if (previousAddress) {
                $('#api_to').val($.cookie('deliveryAddress'));
            }
            google.maps.event.addListener(autocomplete, 'place_changed', function () {
                var place = autocomplete.getPlace();
                to_name = place.name;
                $.cookie("deliveryAddress", to_name);
                to_vicinity = place.vicinity;
                to_lat = place.geometry.location.lat();
                to_long = place.geometry.location.lng();
                sendRequest(to_name, to_lat, to_long);
                getLink();
            });
        }
    });

    setPhoneRequired();

    function setPhoneRequired() {
        console.log('making phone required');
        $('input[name=phone]').prop('required', true);
        $('input[name=phone]').parents(".form-group").find(".form-control-comment").html('');
    }

    function setDeliveryMessage() {
        $('label[for=delivery_message]').html('Include more information i.e (building, room) or extra details about your order below.');
        $('#delivery_message').attr("placeholder", "Max 300 characters");
        $('#delivery_message').css("font-size", "12px");
        $('#delivery_message').attr('maxlength', '300');
        $('#delivery').append('<div><label>Please select below the day and time you would like to have your order delivered.</label>' +
            '<div style="display: inline-block">' +
            '<label for="pickup-day">Day:</label>\n' +
            '<select style="color: #232323; font-size: .875rem;" id="day">\n' +
            '  <option value="today">Today</option>\n' +
            '  <option value="nextday">Tomorrow</option>\n' +
            '</select>' +
            '</div>' +
            '<div style="display: inline-block; margin-left: 26px">' +
            '<label for="pickup-time">Time:</label>\n' +
            '<select style="color: #232323; font-size: .875rem;" id="time">' +
            '</select>' +
            '</div>' +
            '</div>');

        let format = 'HH:mm';

        let now = moment();
        let currentHour = now.hour();
        let shopSlots = $.cookie('pickupSlots');
        if($.cookie('pickupSlots') !== null || $.cookie('pickupSlots') !== ""
            || $.cookie('pickupSlots') !== "null" || $.cookie('pickupSlots') !== undefined)
        {
            let array = shopSlots.split(",");
            for (i=0;i<array.length;i++){
                let shopOpenTime = array[0];
                if (shopOpenTime === "6:00 - 8:00") {
                    var openHour = "6";
                }
                else if (shopOpenTime === "8:00 - 10:00") {
                    var openHour = "8";
                }
                else if (shopOpenTime === "10:00 - 12:00") {
                    var openHour = "10";
                }
                else if (shopOpenTime === "12:00 - 14:00") {
                    var openHour = "12";
                }
                else if (shopOpenTime === "14:00 - 16:00") {
                    var openHour = "14";
                }
                else if (shopOpenTime === "16:00 - 18:00") {
                    var openHour = "16";
                }
                else if (shopOpenTime === "18:00 - 20:00") {
                    var openHour = "18";
                }
                let shopCloseTime = array[array.length-1];
                if (shopCloseTime === "6:00 - 8:00") {
                    var closeHour = "8";
                }
                else if (shopCloseTime === "8:00 - 10:00") {
                    var closeHour = "10";
                }
                else if (shopCloseTime === "10:00 - 12:00") {
                    var closeHour = "12";
                }
                else if (shopCloseTime === "12:00 - 14:00") {
                    var closeHour = "14";
                }
                else if (shopCloseTime === "14:00 - 16:00") {
                    var closeHour = "16";
                }
                else if (shopCloseTime === "16:00 - 18:00") {
                    var closeHour = "18";
                }
                else if (shopCloseTime === "18:00 - 20:00") {
                    var closeHour = "20";
                }
            }
            if (openHour >= currentHour){
                var startHour = openHour;
            }else if (openHour < currentHour) {
                var startHour = currentHour;
            }
            if(startHour % 2 !== 0){
                startHour = startHour-1;
                //console.log(startHour);
            }
            let endDay = moment(closeHour, format);
            let endHour = endDay.hour();

            let diff = endHour - startHour;

            let slots = Math.round(diff/2);

            let deliverySlots = [];
            let startTime = moment(startHour+":00:00",format);
            for(let i = 0; i < slots; i++) {
                let slot = {
                    "start": startTime.format(format),
                    "end": startTime.add(2, 'hours').format(format)
                }

                deliverySlots.push(slot);
            }

            $('#day').val($.cookie('pickupDay'));
            $('#time').val($.cookie('pickupTime'));

            $("#day").change(function () {
                let pickupDay = $(this).val();
                $.cookie("pickupDay", pickupDay);
                switch ($(this).val()) {
                    case 'today':
                        $('#time').html("");
                        $.each(deliverySlots, function(key, value) {
                            $("#time").append("<option>" + value.start + " - " + value.end + "</option>");
                        });
                        $('#time').change(function(){
                            var time = $(this).find("option:selected").val();
                            $.cookie("pickupTime", time);
                        });
                        break;
                    case 'nextday':
                        $('#time').html("");
                        for (i=0;i<array.length;i++){
                            $('#time').append('<option value="'+array[i]+'">'+array[i]+'</option>');
                        }
                        $('#time').change(function(){
                            var time = $(this).find("option:selected").val();
                            $.cookie("pickupTime", time);
                        });
                        break;
                }
            });
        }

    }

    setDeliveryMessage();


    function sendRequest(to_name, to_lat, to_long) {
        var to_name = to_name;
        var to_lat = to_lat;
        var to_long = to_long;
        var url = "/modules/sendyapimodule/custom/dataReceiver.php";
        url = getLink(url);
        $.ajax({
            type: "POST",
            url: url,
            data: {
                to_name: to_name,
                to_lat: to_lat,
                to_long: to_long
            },
            beforeSend: function () {
                $('#info-block').hide();
                $('.loader').show();
                $("#submitBtn").css("background-color", "grey");
                $("#submitBtn").val('PRICING...');
            },
            success: function (res) {
                // console.log(res);
                let data = JSON.parse(res);
                let price = data.data.amount;
                if (price) {
                    $('.loader').hide();
                    $('.divHidden').show();
                    $(".show-price").text(price);
                    setShipping(price);
                }
                else {
                    console.log('not in range');
                    $('.loader').hide();
                    $("#submitBtn").css("background-color", "#1782c5");
                    $('#api_to').attr("placeholder", "Change delivery destination");
                    $('#info-block').show();
                }
            }
        })
            .fail(function (er) {
                console.log(er);
                $('.loader').hide();
            })
    }

    function getLink(url) {
        var loc = window.location.pathname;
        var dir = loc.substring(0, loc.lastIndexOf('/'));
        //console.log(dir+url);
        return dir + url;
    }

    function setShipping(price) {
        let url = "/modules/sendyapimodule/custom/setShipping.php";
        $.ajax({
            type: "POST",
            url: getLink(url),
            data: {
                action: 'getPackageShippingCost',
                shipping_cost: price
            },

            dataType: 'json',
            cache: false,
            success: function (msg) {
                //console.log(msg);
                location.reload(true);
            }

        });

    }
    function setTrackingLink() {
        let url = "/modules/sendyapimodule/custom/sendyTracking.php";
        $.ajax({
            type: "POST",
            url: getLink(url),
            data: {

            },

            dataType: 'json',
            cache: false,
            success: function (msg) {
                //console.log(msg);
                $( '<section id="track_delivery"><h3 class="card-title h3">TRACK YOUR SENDY ORDER</h3><p>Click <a target="_blank" href='+ msg.tracking_url + '>here </a> to track your delivery.</p></section>' ).insertAfter( "#content-hook_order_confirmation" );
            },
            error: function(err) {
              console.log(err);
           }

        });

    }
    setTrackingLink();

});
