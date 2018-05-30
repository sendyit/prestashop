var from_name;
var from_vicinity;
var from_lat;
var from_long;
$(document).ready(function () {
    // put your jQuery code here
    $("#api_long").closest(".form-group").hide();
    $("#api_lat").closest(".form-group").hide();

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

    function dynamicallyLoadScript(url) {
        var script = document.createElement("script"); // Make a script DOM node
        script.src = url; // Set it's src to the provided URL

        document.head.appendChild(script); // Add it to the end of the head section of the page (could change 'head' to 'body' to add it to the end of the body section instead)
    }
    function sendData(from_name, from_lat, from_long) {
        var from_name = from_name;
        var from_lat = from_lat;
        var from_long = from_long;

        $("#api_lat").val(from_lat);
        $("#api_long").val(from_long);


        // $.ajax({
        //     type: "POST",
        //     url: '/prestashop/modules/sendyapimodule/custom/dataReceiver.php',
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