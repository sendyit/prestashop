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

function dynamicallyLoadScript(url) {
    let script = document.createElement("script"); // Make a script DOM node
    // script.setAttribute('defer','');
    // script.setAttribute('async','');
    script.src = url; // Set it's src to the provided URL
    document.head.appendChild(script); // Add it to the end of the head section of the page (could change 'head' to 'body' to add it to the end of the body section instead)
}

dynamicallyLoadScript("https://maps.googleapis.com/maps/api/js?&libraries=places&key=AIzaSyD5y2Y1zfyWCWDEPRLDBDYuRoJ8ReHYXwY&callback=initMap");