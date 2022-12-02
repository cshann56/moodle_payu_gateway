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
 * Abstract class for handling responses from redirect response or
 * or server to server response from payment gateway. This class
 * implements a template design pattern.
 *
 * @package     paygw_payuindia
 * @copyright   2022 Christopher Shannon <cshannon108@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payuindia;

use core_payment\helper;
use paygw_payuindia\payuhelper;
use paygw_payuindia\gatewayconfig;

require_once('../../../config.php');
require_once('./lib.php'); // Loads settings that all accounts will use.
global $_REQUEST;
abstract class abstract_response_template {

    public $fetched_params;
    public $submitinfo;
    public $gwcfg;
    public $failurecode; // Set if a failure happens.
    public $remotesource; // tells whether it is redirecet (null) or webhook.
    public $excep; // Record an exception at the final step, if any. Null otherwise.
    public $webhook_recorded = false; // Whether a webhook query has already been recorded.

    // Main template function.
    public function process_response() {
        $continue_process = $this->is_response_received(); 

        if (! $continue_process) {
            return;
        }

        $continue_process = $this->record_response();

        if (! $continue_process) {
            return;
        }
        
        $continue_process = $this->is_already_enrolled();

        if (! $continue_process) {
            return;
        }

        $continue_process = $this->compare_hashes();
        if (! $continue_process) {
            return;
        }

        $continue_process = $this->compare_charges();
        if (! $continue_process) {
            return;
        }

        $continue_process = $this->verify_response();
        if (! $continue_process) {
            return;
        }

        $continue_process = $this->deliver_order();
        if (! $continue_process) {
            return;
        }

        // TODO: add a send mail step here.
        // https://articlebin.michaelmilette.com/sending-custom-emails-in-moodle-using-the-email_to_user-function/
    }

    // Check if response is already received. A non-webhook pre-existing record
    // Will also set the failure code to 004.
    protected function is_response_received() {
        $webhook_recorded = payuhelper::is_recorded_response_from_webhook();
        $isrecorded = payuhelper::is_response_recorded();

        if ($isrecorded && ! $webhook_recorded) {
            // We don't want to record this as an error that the user resubmitted a request
            // or pressed the refresh/F5 button if the recording is from a webhook.
            $this->failurecode = '004';
        }

        return $this->is_response_received_action($isrecorded);
    }

    // Action to perform if is_response_recorded is true or false.
    // Return value: true to continue further processing, false: stop processing.
    abstract protected function is_response_received_action($isrecorded): bool;

    // Record the resposne here. 
    protected function record_response() {

        $excep = null;

        
        if ($this->remotesource == null) {
            $this->remotesource == 'redirect';
        }

        try {//
            $this->fetched_params = payuhelper::record_response($this->remotesource);
        } catch (Exception $e) {
            $excep = $e;
        }
        return $this->record_response_action($excep);
    }

    // Says what to do on recording response.
    // Function will be passed an exception object, if any, or null.
    abstract protected function record_response_action($excep = null): bool;

    // Check to see if the user with the specified txnid has been successfully
    // enrolled in the course.
    protected function is_already_enrolled() {
        $is_enrolled = payuhelper::is_user_enrolled();

        return $this->is_already_enrolled_action($is_enrolled);
    }

    // Says what to do if the user is already enrolled.
    abstract protected function is_already_enrolled_action($is_enrolled): bool;

    protected function compare_hashes() {
        global $DB;

        // The original data submitted to the remote PayU system.
        $this->submitinfo = (array) $DB->get_record('paygw_payuindia_submitinfo', 
            ['txnid' => $this->fetched_params['txnid']]);

        // Get the gateway config.
        $this->gwcfg = new gatewayconfig(
                $this->submitinfo['component'],
                $this->submitinfo['paymentarea'],
                $this->submitinfo['itemid']);

        // 'key' is in the actual request parameter, but
        // fetched_params actually converts it to payukey.
        $this->fetched_params['key'] = $this->fetched_params['payukey'];

        // Check the generated hash against the one returned by the PayU system.
        // If it does not match, then record the message in udf10 that
        // the hashes did not match, and exit.
        $reversehash = payuhelper::generate_reverse_hash($this->gwcfg, $this->fetched_params);

        $hashes_match = ($reversehash == $this->fetched_params["hash"]);

        if (! $hashes_match) {
            $this->failurecode = '001';
        }
        
        return $this->compare_hashes_action($hashes_match);
    }

    abstract protected function compare_hashes_action(bool $hashes_match): bool;

    protected function compare_charges(): bool {
    
        $additional_charges =
            $this->submitinfo['additional_charges'] == null ? 
                '' : $this->submitinfo['additional_charges'];

        $unequal = (($this->submitinfo['amount'] != $this->fetched_params['amount']) ||
                ($additional_charges != $this->fetched_params['additional_charges']));

        if ($unequal) {
            $this->failurecode = '002';
        }

        return $this->compare_charges_action($unequal);
    }

    // If $unequal is true, then some charges did not match.
    abstract protected function compare_charges_action(bool $unequal): bool;


    protected function verify_response(): bool {

        $response_verified = payuhelper::verify_transaction($this->gwcfg,
            $this->fetched_params['txnid'], $this->fetched_params['datetime'], false);

        if (! $response_verified) {
            $this->failurecode = '003';
        }

        return $this->verify_response_action($response_verified);
    }

    abstract protected function verify_response_action(bool $response_verified): bool;

    protected function deliver_order(): bool {

        $excep = null;

        try {
            payuhelper::deliver_order(
                $this->submitinfo['component'],
                $this->submitinfo['paymentarea'],
                $this->submitinfo['itemid'],
                $this->submitinfo['id'],
                $this->submitinfo['txnid'],
                $this->submitinfo['amount'],
                $this->submitinfo['additional_charges']); // We want additional_charges null here for input if not used.
        } catch (Exception $e) {
            $excep = $e;
        }

        return $this->deliver_order_action($excep);
    }

    // Function will be passed an exception object, if any, or null.
    abstract protected function deliver_order_action($excep = null): bool;


    protected function update_response_record($failurecode) {
        global $DB;

        $record = $DB->get_record('paygw_payuindia_response', ['id' => $this->fetched_params['id']]);
        $record->udf = '||||||||'.$failurecode.'|'.$this->get_response_error_message($failurecode);
        $DB->update_record('paygw_payuindia_response', $record);
    }

    public function get_response_error_message($errorcode) {
        return get_string('response_error_code_'.$errorcode, 'paygw_payuindia');
    }
}
