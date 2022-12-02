<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     paygw_payuindia
 * @category    admin
 * @copyright   2022 Christopher Shannon <cshannon108@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use paygw_payuindia\payuhelper;
use paygw_payuindia\failure_response;

require_once('../../../config.php');
require_once('./lib.php'); // Loads settings that all accounts will use.

global $_REQUEST;

$context = context_system::instance();
payuhelper::relogin_user($context); // This must be called here.

$PAGE->set_context($context);
// Set page url.
$PAGE->set_url(paygw_payuindia\LOCAL_FAILURE_URL);
require_login();

$failurecode = optional_param('failurecode', null, PARAM_RAW);
$txnid = optional_param('txnid', null, PARAM_RAW);

if ($failurecode == null) {
    $fail_resp = new failure_response();
 
    // Remote site reported a failure.
    $fail_resp->process_response();
    
    if ($fail_resp->failurecode != null) {
        $failurecode = $fail_resp->failurecode;
    }

} else {

    // Check to see if response is already recorded (i.e. user pushed refresh button).
    $webhook_recorded = payuhelper::is_recorded_response_from_webhook();

    if ($webhook_recorded == null) {
        $webhook_recorded = false;
    }

    $isrecorded = payuhelper::is_response_recorded();

    if ($isrecorded && ! $webhook_recorded) {
        // User pressed refresh, send to error page.
        redirect(paygw_payuindia\LOCAL_FAILURE_URL . '?failurecode=004&response_txnid='.$txnid);     
    }

    if ($txnid == null) {

        // There isn't even a transaction ID, so we don't know what
        // to do with this.
        redirect(paygw_payuindia\LOCAL_FAILURE_URL . '?failurecode=003');     
    }
}

$PAGE->set_pagelayout('standard');
$PAGE->set_title('PayU India - payment transaction report');
$PAGE->set_heading('PayU India - payment transaction report');

$continue_button = payuhelper::get_continue_on_failure_form($txnid);

if ($failurecode != null) { // Some check in the success path failed.

    $errormsg = $fail_resp->get_response_error_message($failurecode);

    echo $OUTPUT->header();
    echo <<<HTML

<h2>Problem with verifying remote transaction.</h2>

<h3>Error Message for transaction ID <span color='red'>$txnid</span></h3>

<p><b>$errormsg</b></p>

$continue_button

HTML;

} else {

    // An error from the PayU side.
    // PayU has sent us a bunch of information not yet recorded.
    $fp_error = required_param("error", PARAM_RAW);
    $fp_error_msg = required_param("error_Message", PARAM_RAW);

    echo $OUTPUT->header();
    echo <<<HTML

<h2>Remote transaction failed.</h2>

<h3>Error Message from remote system for transaction ID <span color='red'>$txnid</span></h3>

<p>Error code: <b>$fp_error</b></p>
<p>Error message: <b>$fp_error_msg</b></p>

<p>Your payment was not accepted by the PayU India payment gateway.</p>

$continue_button

HTML;

}

echo $OUTPUT->footer();
