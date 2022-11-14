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

        $txnid = required_param('txnid', PARAM_RAW);
        $isrecorded = payuhelper::is_response_recorded($txnid, true);

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
    
    protected function record_response_action($excep = null): bool {

        if ($excep) {
            // An exception was thrown.
            throw $excep;
            return false;
        }

        return true;

    }

    protected function compare_hashes_action(bool $hashes_match): bool {

        if (! $hashes_match) {
            $this->update_response_record('001');
            return false;
        }

        return true;
    }


    protected function compare_charges_action(bool $unequal): bool {

        if ($unequal) {
            $this->update_response_record('002');
            return false;
        }

        return true;
    }

    protected function verify_response_action(bool $response_verified): bool {

        if (! $response_verified) {
            $this->update_response_record('003');
            return false;
        }

        return true;
    }


    protected function deliver_order_action($excep = null): bool {

        if ($excep != null) {
            $this->excep = $excep;
        }

        return true;
    }
}
