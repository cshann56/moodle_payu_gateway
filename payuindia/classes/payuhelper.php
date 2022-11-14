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
 * @category    helper
 * @copyright   2022 Christopher Shannon <cshannon108@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payuindia;

defined('MOODLE_INTERNAL') || die();

use core_payment\helper;
use core\session\manager;
use paygw_payuindia\gatewayconfig;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');


class payuhelper {

    // Returns an associative array with txnid and time set.
    public static function get_transaction_id(gatewayconfig $gwcfg) {

        global $DB, $USER; // Get's the global database object.

        $record = new \stdClass;
        $mytime = time();
        $record->datetime   = $mytime;
        $record->userid     = $USER->id;
        $idfield = $DB->insert_record('paygw_payuindia', $record);
        $mytxnid = $gwcfg->transactionprefix . $idfield; // This creates the actual txnid.
        $record_with_id = ['id' => $idfield, 'txnid' => $mytxnid];
        $DB->update_record('paygw_payuindia', $record_with_id);
        return ['txnid' => $mytxnid, 'datetime' => $mytime];
    }

    // Takes the transaction id generated from submitinfo table.
    public static function get_failure_url($txnid) {

        global $DB, $CFG;

        if ($txnid != null) {
            $record = $DB->get_record('paygw_payuindia_submitinfo', ['txnid' => $txnid]);
            $enrol_rec = $DB->get_record('enrol', ['enrol' => $record->paymentarea, 'id' => $record->itemid]);
            $failure_url = $CFG->wwwroot . '/enrol/index.php?id='.$enrol_rec->courseid;
        } else {
            $failure_url = $CFG->wwwroot . '?redirect=0'; // Just go to the home page.
        }

        return $failure_url;
    }

    public static function get_continue_on_failure_form($txnid) {

       $furl = payuhelper::get_failure_url($txnid);

       $form = <<<HTML
<div style='text-align: center;'>
    <form>
        <input type='button' value='Continue'
            onclick='window.location.replace("$furl");'/>
    </form>
</div>
HTML;
        return $form;
    }

    public static function generate_hash(gatewayconfig $gwcfg, array $params) {
        // Adapted from Hasher.php from PayU source code.
        $payukey    = $gwcfg->remotekey;
        $payusalt   = $gwcfg->remotesalt;

        $txnid       = isset($params["txnid"]) ? $params["txnid"] : '';
        $amount      = isset($params["amount"]) ? $params["amount"] : '';
        $productinfo = isset($params["productinfo"]) ? $params["productinfo"] : '';
        $firstname   = isset($params["firstname"]) ? $params["firstname"] : '';
        $email       = isset($params["email"]) ? $params["email"] : '';
        $udf1        = isset($params["udf1"]) ? $params["udf1"] : '';
        $udf2        = isset($params["udf2"]) ? $params["udf2"] : '';
        $udf3        = isset($params["udf3"]) ? $params["udf3"] : '';
        $udf4        = isset($params["udf4"]) ? $params["udf4"] : '';
        $udf5        = isset($params["udf5"]) ? $params["udf5"] : '';

        $payhash_str = 
            $payukey . '|' . 
            $txnid . '|' . 
            $amount  . '|' . 
            $productinfo  . '|' . 
            $firstname . '|' . 
            $email . '|' . 
            $udf1 . '|' . 
            $udf2 . '|' . 
            $udf3 . '|' . 
            $udf4 . '|' . 
            $udf5 . '||||||' . 
            $payusalt;


        if ($params['additional_charges'] != null) {
            $payhash_str = $payhash_str . '|'. $params['additional_charges'];
        }

        $payment_hash = strtolower(hash('sha512', $payhash_str));

        return $payment_hash;
    }

    public static function generate_reverse_hash(gatewayconfig $gwcfg, array $params) {
        // Adapted from Hasher.php from PayU source code.
        $payukey    = $gwcfg->remotekey;
        $payusalt   = $gwcfg->remotesalt;

        // None of these values are expected to be null, but we leave the check in place, just in case.
        $txnid       = isset($params["txnid"]) ? $params["txnid"] : '';
        $amount      = isset($params["amount"]) ? $params["amount"] : '';
        $productinfo = isset($params["productinfo"]) ? $params["productinfo"] : '';
        $firstname   = isset($params["firstname"]) ? $params["firstname"] : '';
        $email       = isset($params["email"]) ? $params["email"] : '';
        $status      = isset($params["status"]) ? $params["status"] : '';
        $udf1        = isset($params["udf1"]) ? $params["udf1"] : '';
        $udf2        = isset($params["udf2"]) ? $params["udf2"] : '';
        $udf3        = isset($params["udf3"]) ? $params["udf3"] : '';
        $udf4        = isset($params["udf4"]) ? $params["udf4"] : '';
        $udf5        = isset($params["udf5"]) ? $params["udf5"] : '';

        $payhash_str = 
            $payusalt . '|' .
            $status . '||||||' .
            $udf5 . '|' . 
            $udf4 . '|' . 
            $udf3 . '|' . 
            $udf2 . '|' . 
            $udf1 . '|' . 
            $email . '|' . 
            $firstname . '|' . 
            $productinfo  . '|' . 
            $amount  . '|' . 
            $txnid . '|' . 
            $payukey; 

        if ($params['additional_charges'] != null) {
            $payhash_str = $params['additional_charges'] . '|'. $payhash_str;
        }

        $payment_hash = strtolower(hash('sha512', $payhash_str));

        return $payment_hash;
    }

    /**
     * Returns true or false, or an associative array with the true/false
     * result and the data structure returned from the PayU website.
     * verify_params is a mapping of database fields from paygw_payuindia_submitinfo to
     * fields in the PayU response transaction details. The field response_datetime
     * is necessary to find the record from the response table.
     * 
     * See: https://devguide.payu.in/api/payments/transaction-verification-apis/verify_payment-api/
     */  
    public static function verify_transaction(
        gatewayconfig $gwcfg, $txnid, $response_datetime,
        $return_trans_details = false,
        $verify_params = ['amount' => 'transaction_amount',
            'additional_charges' => 'additional_charges']) {

        global $DB;

        // Get the record from the submitinfo table.
        $record = $DB->get_record('paygw_payuindia_response',
            ['txnid' => $txnid, 'datetime' => $response_datetime]);

        // We may need to make an anal check for retrieving the record here.
        // It would be very unusual for this to fail, however.

        // Generate hash for remote call.
        $hash_str = hash('sha512',
            $gwcfg->remotekey   .'|'.
            'verify_payment'    .'|'.
            $txnid              .'|'.
            $gwcfg->remotesalt);
        $hash_str = strtolower($hash_str);

        $remote_url = $gwcfg->remotebaseurl . '/' . REMOTE_SYS_VERIFY_URL_SUBPATH;            

        // Initiate the cURL session.
        $resp = 'false';
        try {
            $options[CURLOPT_NOSIGNAL] = true;
            $options[CURLOPT_HTTPHEADER] = ["accept: application/json"];
            $options[CURLOPT_URL] = $remote_url;
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] =
                ['key' => $gwcfg->remotekey, 'command' => 'verify_payment',
                    'var1' => $txnid, 'hash' => $hash_str];
            $options[CURLOPT_TIMEOUT] = 10;
            $options[CURLOPT_RETURNTRANSFER] = true;

            $curl = curl_init();
            curl_setopt_array($curl, $options);

            $resp = curl_exec($curl);
            curl_close($curl);

        } catch (Exception $e) {
            // A cURL exception, we want to record this without
            // erroring out. The remote site could be down,
            // remote URLs may have changed without notice,
            // Network may be down, etc.
            $DB->insert_record('paygw_payuindia_verify',
                [
                    'datetime' => time(), 'submitinfoid' => $record->id,
                    'internal_result_code' => 'cURL excp, no site access',
                    'verifydata' => var_export($e, true)
                ]);
             if ($return_trans_details) {
                 return ['result' => false, 'details' => $e, 'message' => 'cURL exception'];
             } else {
                 return false;
             }
        }

        if ($resp == 'false') {
            throw new moodle_exception(123, null, null, "cURL did not return anything.");
        }

        // Save the json response to the database.
        $DB->insert_record('paygw_payuindia_verify',
            ['datetime' => time(), 'responseid' => $record->id,
                'txnid' => $txnid, 'internal_result_code' => 'get remote data OK',
                'verifydata' => $resp]);

        // Convert the json into a PHP object and compare database fields with
        // remote verify object fields.
        $verify_obj = json_decode($resp, true);

        $trans_details = $verify_obj["transaction_details"];

        $verify_result = true;
        $record_as_array = (array) $record; // Convert db record obj to array for easy iteration.

        foreach ($verify_params as $dbrec => $vrec) {
            if (! isset($record_as_array[$dbrec])) {
                continue; // If some variables are null, we do not want to check.
            }

            if ($record_as_array[$dbrec] != $trans_details[$txnid][$vrec]) {
                $verify_result = false;
                break;
            }
        }

        if ($return_trans_details) {
            $return_val = ['result' => $verify_result,
                'details' => ['verify_obj' => $verify_obj,
                'response_rec' => $record,
                'verify_params' => $verify_params],
                'message' => 'Some values did not match across the record'];
            
            return $return_val;
        } else {
            return $verify_result;
        }
    }

    public static function deliver_order($component, $paymentarea, $itemid, $submitinfoid,
                                        $txnid, $amount, $additional_charges = null) {
        global $DB;

        // Get record for initial transaction.
        $record = $DB->get_record('paygw_payuindia', ['txnid' => $txnid]);
        $userid = $record->userid; // Do not use the one from $USER.

        $payable = helper::get_payable($component, $paymentarea, $itemid);

        // TODO: Get more info on function core_payment\helper::get_rounded_cost().
        $cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(),
            $additional_charges != null ? $additional_charges : 0.0);
        $paymentid = helper::save_payment($payable->get_account_id(), $component, $paymentarea,
            $itemid, $userid, $cost, $payable->get_currency(), 'payuindia');
        helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $userid);

        // Update the default record with the id of the submitinfo and the paymentid.
        $record->submitinfoid = $submitinfoid;
        $record->paymentid = $paymentid;
        $DB->update_record('paygw_payuindia', $record);
    }

    /**
     * Returns true false only.
     * False means response was recorded. True means it was recorded. Null means txnid not found.
     * @param $txnid    Optional transaction ID. Default is null.
     * @param $success  Optional flag indicating whether registration with class was successful.
     *                  Default is false.
     */
    public static function is_response_recorded($txnid = null, $success = false) {

        global $DB;

        $retval = false;

        if ($txnid == null) {
            $txnid = optional_param('txnid', null, PARAM_RAW);
        }

        // Make sure this is ordered ascending by the id field.
        if ($txnid != null) {

            if ($success == false) {

                $rec = $DB->get_records('paygw_payuindia_response', ['txnid' => $txnid]);

                if (count($rec) > 0) {
                    $retval = true;
                }
            } else {

                // Get the id for the max timestamp for txnid.
                $sql = "select max(datetime) as maxtime from {paygw_payuindia_response} where txnid = ?";
                $rec = $DB->get_record_sql($sql, [$txnid]);

                $rec2 = $DB->get_record_select(
                    'paygw_payuindia_response',
                    'datetime = ? AND txnid = ?',
                    [$rec->maxtime, $txnid]
                );

                // If the response has a success timestamp, then return true.
                // Otherwise, return false.
                if ($rec2->error == 'E000') {

                    // One more additional check, to see if an error
                    // was recorded in any of our compare of amount, hash, or other errors.
                    $udf = $rec2->udf;
                    if ($udf != null) {
                        $udf_fields <- explode("|", $udf);
                        $internal_err_code = $udf_fields[count($udf_fields) - 2]; // Get second to last code.

                        if ($internal_err_code == "") {
                            $retval = true;
                        } else {
                            $retval = false;
                        }
                    } else {
                        $retval = true;
                    }

                } else {
                    $retval = false;
                }
            }

        } else {

            $retval = null;
        }

        return $retval;
    }

    /**
     * Inserts a response from the PayU system into the database. Returns an
     * array of the fetched parameters.
     * @param webhook redirect (false) or webhook (true). Default is false.
     */
    public static function record_response($webhook = false) { // The other value is webhook.

        global $DB, $_SERVER;

        $param2dbname = [ 
            'mihpayid' => 'mihpayid', 'mode' => 'payumode', 'status' => 'status',
            'unmappedstatus' => 'unmappedstatus', 'key' => 'payukey', 'txnid' => 'txnid',
            'amount' => 'amount', 'discount' => 'discount', 'net_amount_debit' => 'net_amount_debit',
            'addedon' => 'addedon', 'productinfo' => 'productinfo', 'firstname' => 'firstname',
            'lastname' => 'lastname', 'address1' => 'address1', 'address2' => 'address2',
            'city' => 'city', 'state' => 'state', 'country' => 'country',
            'zipcode' => 'zipcode', 'email' => 'email', 'phone' => 'phone',
            'hash' => 'hash', 'payment_source' => 'payment_source',
            'PG_TYPE' => 'pg_type', 'bank_ref_num' => 'bank_ref_num', 'bankcode' => 'bankcode',
            'error' => 'error', 'error_Message' => 'error_message', 'additional_charges' => 'additional_charges'
        ];

        $fetched_params = [];
        foreach ($param2dbname as $param_name => $dbname) {
            $myparam = optional_param($param_name, null, PARAM_RAW);
            if ($myparam == '') {
                $myparam = null;
            }

            $fetched_params[$dbname] = $myparam;
        }


        $udfs = [];
        $hasudf = false;
        // Create UDFs if any.
        for ($i = 1; $i < 11; $i++) {
            $myparam = optional_param('udf'.$i, '', PARAM_RAW);
            if ($myparam != '') {
                $hasudf = true;
             }
            array_push($udfs, $myparam);
        }

        $udf = null;
        if ($hasudf) {
            $udf = implode("|", $udfs);
        }

        $fetched_params['udf'] = $udf;


        $fields = [];
        $hasfield = false;
        // Create UDFs if any.
        for ($i = 1; $i < 10; $i++) {
            $myparam = optional_param('field'.$i, '', PARAM_RAW);
            if ($myparam != '') {
                $hasfield = true;
            }
            array_push($fields, $myparam);
        }

        $field = null;
        if ($hasfield) {
            $field = implode("|", $fields);
        }

        $fetched_params['field'] = $field;

        // Because we're not using UDFs, we don't care about
        // checking them.

        // Add datetime to record.
        $fetched_params["datetime"] = time();

        // Add IP address and URL to the database
        $fetched_params["source"]     = $webhook ? 'webhook' : 'redirect';
        $fetched_params["remoteaddr"] = $_SERVER["REMOTE_ADDR"];

        // Save record.
        $id = $DB->insert_record('paygw_payuindia_response', $fetched_params);

        $fetched_params["id"] = $id; // So we can keep track of the id.

        return $fetched_params;
    }

    /**
     * Used only on redirect from remote PayU web server. 
     * This uses the PayU txnid, retrieves the user id, and then
     * relogs in the user without need for additional authentication.
     */
    public static function relogin_user(\context $context) {
        global $DB;

        // TODO: Make sure that we aren't logged in as guest user.
        if (isloggedin()) {
            return; // If we are logged in as someone, then no need to re-log in.
        }

        $txnid = required_param('txnid', PARAM_RAW);
        $rec = $DB->get_record('paygw_payuindia', ['txnid' => $txnid]);
        $userid = $rec->userid;

        // This is the session manager logging in. But we do not
        // want to generate any login event, in case that triggers something
        // we really only want to trigger when the user logs in.

        manager::loginas($userid, $context, false);

    }

    public static function get_gatewayconfig($txnid = null) {

        global $DB;

        if ($txnid == null) {
            $txnid = required_param("txnid", PARAM_RAW);
        }
        
        // The original data submitted to the remote PayU system.
        $submitinfo = (array) $DB->get_record('paygw_payuindia_submitinfo', 
            ['txnid' => $txnid]);

        // Get the gateway config.
        $gwcfg = new gatewayconfig(
                $submitinfo['component'],
                $submitinfo['paymentarea'],
                $submitinfo['itemid']);

        return $gwcfg;
    }

    public static function get_remote_ipaddr() {

        global $_SERVER;

        if (!empty($_SERVER['HTTP_CLIENT_IP']))   {
            //ip from share internet
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //ip is from proxy
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            //ip is from remote address
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        return $ip_address;
    }

    /**
     * Utility function for debugging.
     */
    public static function var2log(...$vars) {
        $output = var_export($vars, true);
        error_log($output);
    }

}
