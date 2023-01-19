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

class webhooks_response extends abstract_response_template {

    
    // Check if response is already received.
    protected function is_response_received() {

        $this->remotesource = 'webhook'; // Sets the type of response received.

        $mihpayid = required_param('mihpayid', PARAM_RAW);
        $isrecorded = payuhelper::is_response_recorded($mihpayid);

        if ($isrecorded) {
            $this->failurecode = '004';
        }

        return $this->is_response_received_action($isrecorded);
    }


    protected function is_response_received_action($isrecorded): bool {
        if ($isrecorded) {
            return false; // Discontinue if product successfully delivered already.
        }

        return true; // Continues template pattern.
    } 
    
    protected function is_already_enrolled_action($is_enrolled): bool {

        if ($is_enrolled) {
            return false;
        }

        return true;
    }

    protected function record_response_action($excep = null): bool {

        $retval = false;

        if ($excep) {
            $this->excep = $excep;
            log($excep);
        } else {

            // We have to check whether the transaction was a success or failure.
            // If a success, then we allow the transaction to continue. Otherwise,
            // it's a failure, and recording the transaction and sending any notification
            // is sufficient.
            $paystatus = payuhelper::payment_status();

            if ($paystatus == 'success') {
                $retval = true; // Other values could be 'failure' and 'pending'
            }
        }

        return $retval;
    }

    protected function compare_hashes_action(bool $hashes_match): bool {

        if (! $hashes_match) {
            $this->update_response_record('001');
            return false;
        }

        return true;
    }


    protected function compare_charges_action(bool $unequal): bool {

        $retval = true;

        if ($unequal) {
            $this->update_response_record('002');
            $retval = false;
        }

        return $retval;
    }

    protected function verify_response_action(bool $response_verified): bool {

        $retval = true;

        if (! $response_verified) {
            $this->update_response_record('003');
            $retval = false;
        }

        return $retval;
    }


    protected function deliver_order_action($excep = null): bool {

        $retval = true;

        if ($excep != null) {
            $this->excep = $excep;
            $retval = false;
        }

        return true;
    }
}
