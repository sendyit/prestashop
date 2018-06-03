<div id="sendyapimodule" class="block">
    {*<p class="title_block">{l s='Sendy API Module' mod='sendyapimodule'}</p>*}
    <div class="block_content">
        <blockquote>
            <div class="dot"></div>
           <div class="input-block">
               <input class="input" id="api_to" type="text" placeholder="Set destination">
            </div>
            <div class="loader"></div>
            <div id="pricing" class="divHidden">
                <div class="imagey" >
                    {*<img class=d"image-direct" src="../../images/express.png" >*}
                </div>
                <div class="show-type" >Direct</div>
                <div class="show-currency" >KES</div>
                <div class="show-price" >240</div>
                <div>
                <input class="btnContinue" id="continue" type="submit" value="Continue">
                <input class="btnCancel" id="cancel" onclick="hideDiv()" type="submit" value="Cancel">
                </div>
            </div>
            <div>
                <input class="btn" id="submitBtn" type="submit" value="Get a Shipping Price Estimate">
            </div>
        </blockquote>
    </div>
</div>
<script>
    function hideDiv() {
        $(".divHidden").hide('slow');
        $("#submitBtn").css("background-color","#1782c5");
        $("#submitBtn").val('Get a Shipping Price Estimate');
        $("#submitBtn").css("display", "block");

    }
</script>