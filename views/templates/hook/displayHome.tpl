<div id="sendyapimodule" class="block">
    <p class="title_block">{l s='Sendy API Module' mod='sendyapimodule'}</p>
    <div class="block_content">
        <blockquote>
            <div class="input-block">
                <input class="input" id="api_to" type="text" placeholder="Set destination">
            </div>
            <div class="loader"></div>
            <div class="divhidden">
            </div>
            <div>
                <input class="btn" id="submitBtn" type="submit" onclick="setDestination()" value="Get a Shipping Price Estimate">
            </div>
        </blockquote>
    </div>
</div>
<script>
    function setDestination() {
        document.getElementById('api_to').style.display = "block";
        document.getElementById('submitBtn').style.display = "none";
    }
</script>