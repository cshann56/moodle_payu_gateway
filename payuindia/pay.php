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

use core_payment\helper;
use paygw_payuindia\gatewayconfig;
use paygw_payuindia\paymentform;
use paygw_payuindia\payuhelper;

require_once('../../../config.php');
require_once('./lib.php'); // Loads settings that all accounts will use.

require_login();

$context = context_system::instance();
$PAGE->set_context($context);

// Set page url.
$PAGE->set_url(new moodle_url('/payment/gateway/payuindia/pay.php'));

// Standard page layout.
$PAGE->set_pagelayout('standard');
$PAGE->set_title('PayU India - submit payment');
$PAGE->set_heading('PayU India - submit payment');

// Set up our javascript module.
$PAGE->requires->js_call_amd('paygw_payuindia/pay', 'init');

$component      = required_param('component', PARAM_RAW);
$paymentarea    = required_param('paymentarea', PARAM_RAW);
$itemid         = required_param('itemid', PARAM_INT);
$description    = required_param('description', PARAM_TEXT);

$record = $DB->get_record('enrol', ['id' => $itemid, 'enrol' => $paymentarea]);
$courseid = $record->courseid; 

$config    = new gatewayconfig($component, $paymentarea, $itemid);

$payable   = helper::get_payable($component, $paymentarea, $itemid);

$surcharge = helper::get_gateway_surcharge('payuindia');

$amount    = $payable->get_amount();

$coursenode = $PAGE->navigation->find($PAGE->course->id, navigation_node::TYPE_COURSE);

// Create entry in database. We generate transaction id (txnid) from this database transaction.

// Create mform.
// payuindia_helper::build_mform();

// Only include fields that will go to the remote system.
// The "hash" field will be calculated based on these fields only.
// We are including udf1, which will store other info in the future.
// The udf1 value is later retrieved on redirection. There is also room
// for fields udf2 through udf5 that can be utilized later.
$fields = [
    'component'     => $component,
    'paymentarea'   => $paymentarea,
    'itemid'        => $itemid,
    'courseid'      => $courseid,
    'wwwroot'       => $CFG->wwwroot,
    'udf1'          => '', // Leaving it here for future development.
    'key'           => '',
    'txnid'         => '',
    'productinfo'   => $description,
    'amount'        => $amount,
    'surl'  => paygw_payuindia\LOCAL_SUCCESS_URL,
    'furl'  => paygw_payuindia\LOCAL_FAILURE_URL];

/* Surcharge is a percentage in the moodle system, so divide surcharge by 100 and
   multiply by charge and add it to the field.*/ 
if ($surcharge > 0) {
    $fields['additional_charges'] = round($amount * $surcharge / 100, 2);
}


$adminfields = [ // This is a hash / associaitive array.
    'testorprod' => $config->testorprod, // Used in setting action field.
    'remote' => 0, // Tells whether the form created is for collecting local data or for remote submission.
];

// Creates the local form for collecting all data. Some fields are hidden, others are
// text input devices, and others (country and state) are dropdown lists.
$payform = new paymentform($fields, $adminfields);

$adminfields["remote"] = 1;

// Creates the form for remote submission. All fields are hidden input fields.
$payform2 = new paymentform($fields, $adminfields);

echo $OUTPUT->header();

// TODO: Display list of charges and items being charged..
// TODO: Put destination for cancel button.
// TODO: Create dropdown fields for Country and State/Region.
//       This will involve a new AJAX webservice for getting state
//       list based on country.

$payform->display();
$payform2->display();

echo $OUTPUT->footer();
