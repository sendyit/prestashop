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

            google.maps.event.addListener(autocomplete, 'place_changed', function () {
                var place = autocomplete.getPlace();
                to_name = place.name;
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
        $('#delivery').append('<div><label>Please select below the day and time you would like to have your order delivered.</label><div id=\'deliveryDay\'>\n' +
            '   <select>\n' +
            '   </select>\n' +
            '</div></div>');
        let delivery_day = ["Today", "Tomorrow"];
        //let delivery_time = ["six", "eight","ten","noon","two","four","late"];
        let delivery_time = [
            {text:"6.00 AM - 8.00 AM", val:"six"},
            {text:"8.00 AM - 10.00 AM", val:"eight"},
            {text:"10.00 AM - 12.00 PM", val:"ten"},
            {text:"12.00 PM - 2.00 PM", val:"noon"},
            {text:"2.00 PM - 4.00 PM", val:"two"},
            {text:"4.00 PM - 6.00 PM", val:"four"},
            {text:"6.00 PM - 8.00 PM", val:"late"}
            ];

        let arr = [];
        for( let i=6; i<20; i++ ) {
            arr.push(i+":00");
        }

        let date = new Date,
            hour = date.getHours(),
            hourIndex = arr.indexOf(hour+":00");

        let pastHours = arr.slice(0,hourIndex);
        let futureHours = arr.slice(hourIndex);

        console.log(pastHours);
        console.log(futureHours);
        for (let i=0;i<delivery_day.length;i++){
            $('<option/>').val(delivery_day[i]).html(delivery_day[i]).appendTo('#deliveryDay select');
        }
        //     '<div style="display: inline-block">' +
        //     '<label for="pickup-day">Day:</label>\n' +
        //     '<select style="color: #232323; font-size: .875rem;" id="day">\n' +
        //     // '  <option value="">select a day</option>\n' +
        //     '  <option value="today">Today</option>\n' +
        //     '  <option value="kesho">Tomorrow</option>\n' +
        //     '</select>' +
        //     '</div>' +
        //     '<div style="display: inline-block; margin-left: 26px">' +
        //     '<label for="pickup-time">Time:</label>\n' +
        //     '<select style="color: #232323; font-size: .875rem;" id="time">' +
        //     // '<option value="">select a time slot</option>' +
        //     '</select>' +
        //     '</div>' +
        //     '</div>');
        // $(".day").change(function () {
        //     let pickupDay = $(this).val();
        //     $.cookie("pickupDay", pickupDay);
        //     localStorage.setItem("pickupDay", $(this).val());
        //     //console.log($.cookie('pickupDay'));
        //     switch ($(this).val()) {
        //         case 'today':
        //             $("#time").html("<option value='morning'>11AM - 1PM</option><option value='lunch'>1PM - 3PM</option><option value='evening'>3PM - 5PM</option><option value='late'>5PM - 7PM</option>");
        //             break;
        //         case 'kesho':
        //             $("#time").html("<option value='morning'>11AM - 1PM</option><option value='lunch'>1PM - 3PM</option><option value='evening'>3PM - 5PM</option><option value='late'>5PM - 7PM</option>");
        //             break;
        //         default:
        //             $("#time").html("<option value=''>select a time slot</option>");
        //     }
        // });
        // $('#time').change(function () {
        //     let pickupTime = $(this).val();
        //     $.cookie("pickupTime", pickupTime);
        //     localStorage.setItem("pickupTime", $(this).val());
        //     //console.log($.cookie('pickupTime'));
        // });
    }

    setDeliveryMessage();

    // if ($('#day').length) {
    //     $('#day').val(localStorage.getItem("pickupDay"));
    // }
    // if ($('#time').length) {
    //     $('#time').val(localStorage.getItem("pickupTime"));
    // }

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
                    $('#api_to').val("");
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
                console.log(msg);
                location.reload(true);
            }

        });

    }

    let tracking_link = $.cookie('tracking');
    console.log(tracking_link);
    $("<section id='track_delivery'><h3 class='card-title h3'>TRACK YOUR SENDY ORDER</h3><p>Click <a href=\" \">here </a> to track your delivery.</p></section>").insertAfter("#content-hook_order_confirmation");
});
