<div id="sendyapimodule" class="block">
    {*<p class="title_block">{l s='Sendy API Module' mod='sendyapimodule'}</p>*}
    <div class="block_content">
        <blockquote>
            <div class="dot"></div>
           <div class="input-block">
               <input class="input" id="api_to" type="text" placeholder="Set destination">
            </div>
            <div class="loader"></div>
            <div id="pricing" style="display: none; color: #1782c5;" class="divHidden">
                <div class="imagey" >
                    <img class="image-direct" src="{$base_dir}modules/sendyapimodule/views/img/direct.png" >
                </div>
                <div class="show-type" >Direct</div>
                <div class="show-currency" >KES</div>
                <div class="show-price" >240</div>
                <div>
                <input class="btnContinue" id="continue" onclick="setShipping()" type="submit" value="Continue">
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
        $('input[type="text"]').val('');
        $("#submitBtn").css("background-color","#1782c5");
        $("#submitBtn").val('Get a Shipping Price Estimate');
        $("#submitBtn").css("display", "block");

    }
    function setShipping(){
    }
</script>