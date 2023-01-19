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

use \context_course;
use \core_payment\helper;
use \core\session\manager;
use \paygw_payuindia\gatewayconfig;
use \paygw_payuindia\event\user_payment_accepted;

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


        $additional_charges = isset($params['additional_charges']) ?
            $params['additional_charges'] : '';

        if (!empty($additional_charges)) {
            $payhash_str = $payhash_str . '|'. $additional_charges;
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

        $additional_charges = isset($params['additional_charges']) ?
            $params['additional_charges'] : '';

        if (!empty($additional_charges)) {
            $payhash_str = $additional_charges . '|'. $payhash_str;
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
            throw new \moodle_exception(123, null, null, "cURL did not return anything.");
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
                                        $txnid, $amount, $mihpayid, $additional_charges = null) {
        global $DB;

        // Get record for initial transaction.
        $record = $DB->get_record('paygw_payuindia', ['txnid' => $txnid]);
        $userid = $record->userid; // Do not use the one from $USER.

        $payable = helper::get_payable($component, $paymentarea, $itemid);

        $cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(),
            $additional_charges != null ? $additional_charges : 0.0);
        $paymentid = helper::save_payment($payable->get_account_id(), $component, $paymentarea,
            $itemid, $userid, $cost, $payable->get_currency(), 'payuindia');
        $result = helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $userid);

        if ($result == true) { // order successfully delivered. It should always work if we got this point.

            // Update the default record with the id of the submitinfo and the paymentid.
            $record->submitinfoid = $submitinfoid;
            $record->paymentid = $paymentid;
            $DB->update_record('paygw_payuindia', $record);

            // Initiate a PayU payment event
            $enrol_rec = $DB->get_record('enrol', ['enrol' => $paymentarea, 'id' => $itemid]);
            $context   = context_course::instance($enrol_rec->courseid, MUST_EXIST);
            
            // Get the course info.
            $course = $DB->get_record('course', ['id' => $enrol_rec->courseid]); 

            // Get the person's response info.
            $response = $DB->get_record('paygw_payuindia_response', ['mihpayid' => $mihpayid]);

            // Get the actual country name
            $country = $DB->get_record('paygw_payuindia_countries', ['iso3' => $response->country]);
            $countryname = $country->name;

            $event = user_payment_accepted::create(
                array(
                    'objectid' => $response->id, // The id of the table paygw_payuindia_response
                    'courseid' => $enrol_rec->courseid,
                    'contextid' => $context->id,
                    'relateduserid' => $record->userid,
                    'other' => array(
                        'accountid' => $payable->get_account_id(), // Linked to account and account linked to gateway
                        'paymentid' => $paymentid, // Just in case we need it.
                        'course_fullname'   => $course->fullname,
                        'course_shortname'  => $course->shortname,
                        'course_idnumber'   => $course->idnumber,
                        'course_startdate'  => $course->startdate,
                        'course_enddate'    => $course->enddate,
                        'txnid'     => $txnid, // Transaction ID shared with PayU
                        'firstname' => $response->firstname,
                        'lastname'  => $response->lastname,
                        'address1'  => $response->address1,
                        'address2'  => $response->address2,
                        'city'      => $response->city,
                        'state'     => $response->state,
                        'country'   => $countryname,
                        'zipcode'   => $response->zipcode,
                        'email'     => $response->email,
                        'amount'    => $response->amount,
                        'additional_charges'    => $response->additional_charges,
                        'payumode'  => $response->payumode,
                        'status'    => $response->status,
                        'productinfo'   => $response->productinfo,
                        'bankcode'  => $response->bankcode,
                        'paymentsource' => $response->paymentsource,
                        'pg_type'   => $response->pg_type
                        )
                    )
                );

            $event->trigger();

        } else {
            // Throw an error. Something happened.
            throw new \moodle_exception(__LINE__, __FILE__, null, "Something went wrong with order delivery.");
        }
    }

    /** Lifted from \paygw\helper */
    private static function get_service_provider_classname(string $component) {
        
        $providerclass = "$component\\payment\\service_provider";

        if (class_exists($providerclass)) {
            $rc = new \ReflectionClass($providerclass);
            if ($rc->implementsInterface(local\callback\service_provider::class)) {
                return $providerclass;
            }
        }

        throw new \coding_exception("$component does not have an eligible implementation of payment service_provider.");
    }

    /**
     * Returns true false only.
     * False means response was not recorded. True means it was recorded.
     * @param $mihpayid Optional mihpayid, the unique ID for each transaction. Default is null.
     */
    public static function is_response_recorded($mihpayid = null) {

        global $DB;

        $retval = false;

        if ($mihpayid == null) {
            $mihpayid = optional_param('mihpayid', null, PARAM_RAW);
        }

        // Make sure this is ordered ascending by the id field.
        if ($mihpayid != null) {

            $rec = $DB->get_record('paygw_payuindia_response', ['mihpayid' => $mihpayid]);

            if ($rec != null) {
                $retval = true;
            }
        }

        return $retval;
    }

    /**
     * Returns redirect or webhook type of response for a given mihpayid.
     * A response of "webhook" means the already recorded response was a webhook. A
     * A response of "redirect" means the response was already recorded as a result of a redirect.
     * A null response means that the mihpayid could not be found.
     * The web query submission form data is not queried, only the database is queried.
     * @param $mihpayid Optional mihpayid, the unique ID for each transaction. Default is null.
     */
    public static function is_recorded_response_from_webhook($mihpayid = null) {

        global $DB;

        $retval = null;

        if ($mihpayid == null) {
            $mihpayid = optional_param('mihpayid', null, PARAM_RAW);
        }

        // Make sure this is ordered ascending by the id field.
        if ($mihpayid != null) {

            $rec = $DB->get_record('paygw_payuindia_response', ['mihpayid' => $mihpayid]);

            if ($rec != null) {

                if ($rec->source == 'webhook') {
                    $retval = true;
                } else {
                    $retval = false;
                }
            }
        }

        return $retval;
    }

    /**
     * Returns success, failure, pending, or null if mihpayid not found.
     * If no $mihpayid is given as a parameter (null), then the input from the
     * HTTPD query or submit information is read fromt the status parameter and returned.
     * @param $mihpayid Optional mihpayid, the unique ID for each transaction. Default is null.
     */
    public static function payment_status($mihpayid = null) {

        global $DB;

        $retval = null;

        if ($mihpayid == null) {
            $status = optional_param('status', null, PARAM_RAW);
            $retval = $status;
        } else {

            $rec = $DB->get_record('paygw_payuindia_response', ['mihpayid' => $mihpayid]);
            
            if ($rec != null) {
                $retval = $rec->status;
            }
        }

        return $retval;
    }
    
    /**
     * Takes a transaction id (txnid) and determines whether the
     * user's enrollment was successful.
     */
    public static function is_user_enrolled($txnid = null) {

        global $DB;

        $retval = false;

        if ($txnid == null) {
            $txnid = optional_param('txnid', null, PARAM_RAW);
        }

        // Make sure this is ordered ascending by the id field.
        if ($txnid != null) {

            // There can only be one record for any given txnid
            $rec = $DB->get_record('paygw_payuindia', ['txnid' => $txnid]);

            if ($rec->paymentid != null) {
                $retval = true;
            }
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
        global $DB, $USER, $_REQUEST;

        // USER->id == 1 is the guest user. We don't want guest user access.
        if ($USER->id != 1 && isloggedin()) {
            return; // If we are logged in as someone, then no need to re-log in.
        }

        $txnid = required_param('txnid', PARAM_RAW);
        $hash  = required_param('hash', PARAM_RAW);

        // Just to make sure that this is a legitimate redirect,
        // we will check the hash at this point to make sure that it's a valid
        // request.




        $rec = $DB->get_record('paygw_payuindia', ['txnid' => $txnid]);
        $userid = $rec->userid;
        $gwcfg = self::get_gatewayconfig($txnid);

        // Before we relogin user, we will do the reverse hash here to see if
        // the request was legitimate. If not, then throw an exception.
        $rhash = self::generate_reverse_hash($gwcfg, $_REQUEST);

        // Check that revese hash and hash match.
        if ($rhash != $_REQUEST['hash']) {
            throw new \moodle_exception(__LINE__, null, null, "Unauthorized access.");
        }

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
        
        if ($submitinfo['component'] != null) {
            // Get the gateway config.
            $gwcfg = new gatewayconfig(
                    $submitinfo['component'],
                    $submitinfo['paymentarea'],
                    $submitinfo['itemid']);
        } else {
            $gwcfg = null;
        }

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
