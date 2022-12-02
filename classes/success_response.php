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

class success_response extends abstract_response_template {

    protected function is_response_received_action($isrecorded): bool {

        $retval = true; // $retval says whether to continue with transaction.

        $this->webhook_recorded = payuhelper::is_recorded_response_from_webhook();

        if ($isrecorded) {

            // Now, we have to check whether the response was recorded from
            // a webhook (probably) or if it was a redirect. If it was previously
            // recorded from a redirect, then the user probably refreshed his
            // screen or resubmitted the query. In that case, we want to
            // let the user know the response was already received.

            if (! $this->webhook_recorded) {

                $txnid = required_param('txnid', PARAM_RAW);
                redirect($this->gwcfg->failureurl . 
                    '?failurecode=004&response_txnid='.$fetched_params['txnid']);     
                $retval = false;
            } else {
                // This will take the browser directly to the course.
                $this->deliver_order_action(); // Takes browser to the course itself.
            }

            $retval = false;
        }

        return $retval;
    } 
    
    protected function record_response_action($excep = null): bool {

        $retval = true;

        if ($excep) {
            $this->excep = $excep;
            $retval = false;
        }

        return true;
    }

    protected function is_already_enrolled_action($is_enrolled): bool {

        $retval = true;

        if ($is_enrolled) {

            $retval = false;
        }

        return $retval;
    }

    protected function compare_hashes_action(bool $hashes_match): bool {

        if (! $hashes_match) {

            $this->update_response_record('001');

            redirect($this->gwcfg->failureurl . '?failurecode=001&response_txnid=' .
                $this->fetched_params['txnid']);     
            return false;
        }

        return true;
    }


    protected function compare_charges_action(bool $unequal): bool {

        if ($unequal) {

            $this->update_response_record('002');

            redirect($this->gwcfg->failureurl . '?failurecode=002&response_txnid='.
                $this->fetched_params['txnid']);

            return false;
        }

        return true;
    }

    protected function verify_response_action(bool $response_verified): bool {

        if (! $response_verified) {

            $this->update_response_record('003');

            redirect($this->gwcfg->failureurl . '?failurecode=003&response_txnid='.
                $this->fetched_params['txnid']);     
            return false;
        }

        return true;
    }


    protected function deliver_order_action($excep = null): bool {

        global $DB;

        //TODO: write handler in case of an exception, where $excep != null.

        if ($this->submitinfo == null) {
            // This is necessary because if a webhook response has already been
            // registered, then we just need the redirect and not need to record
            // anything else.

            $txnid = required_param('txnid', PARAM_RAW);

            // The original data submitted to the remote PayU system.
            $this->submitinfo = (array) $DB->get_record('paygw_payuindia_submitinfo', 
                ['txnid' => $txnid]);
        }


        $url = helper::get_success_url(
            $this->submitinfo['component'],
            $this->submitinfo['paymentarea'],
            $this->submitinfo['itemid']);
        redirect($url, 'Payment was approved.', 0, 'success');
    }



}
