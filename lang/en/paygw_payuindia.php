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
 * Plugin strings are defined here.
 *
 * @package     paygw_payuindia
 * @category    string
 * @copyright   2022 Christopher Shannon <cshannon108@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname']   = 'PayU India';
$string['pluginname_desc'] = 'The PayU India plugin allows you to receive payments via the good people at PAYU India.';
$string['gatewayname']       = 'PayU India';
$string['gatewaydescription'] = 'PayU India is an authorized payment gateway provider.';
$string['testsys']      = 'Test';
$string['prodsys']      = 'Live';
$string['testorprod_label']  = 'Remote system';
$string['remoteid_label']   = 'Merchant ID';
$string['remotekey_label']  = 'Test Merchant key';
$string['remotesalt_label'] = 'Test Merchant salt';
$string['remotekeylive_label']  = 'Live Merchant key';
$string['remotesaltlive_label'] = 'Live Merchant salt';
$string['transactionprefix_label'] = 'Transaction ID prefix';
$string['testwebhook_label'] = 'Webhook test IP address';

$string['response_error_code_001'] = 'Remote and local hashes do not match.';
$string['response_error_code_002'] = 'Submitted amount and reported amount do not match.';
$string['response_error_code_003'] = 'Cannot verify transaction at this time.';
$string['response_error_code_004'] = 'Response from payment site already received.';
