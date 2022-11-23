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

defined('MOODLE_INTERNAL') || die();

define('paygw_payuindia\REMOTE_SYS_TEST_URL', 'https://test.payu.in');
define('paygw_payuindia\REMOTE_SYS_PROD_URL', 'https://secure.payu.in');

// These are supposed to be appended to the above two paths on the remote system.
define('paygw_payuindia\REMOTE_SYS_PAYMENT_URL_SUBPATH', '_payment');
define('paygw_payuindia\REMOTE_SYS_VERIFY_URL_SUBPATH', 'merchant/postservice?form=2');

$success_url = new moodle_url("/payment/gateway/payuindia/transcomplete.php");
define('paygw_payuindia\LOCAL_SUCCESS_URL', $success_url);
$failure_url = new moodle_url("/payment/gateway/payuindia/transreport.php");
define('paygw_payuindia\LOCAL_FAILURE_URL', $failure_url);

unset($success_url);
unset($failure_url);

define('paygw_payuindia\REMOTE_WEBHOOOK_IP_WHITELIST',
    array(
        '180.179.165.250',
        '180.179.174.1',
        '180.179.174.1',
        '180.179.174.2',
        '180.179.174.23.7.89.1',
        '3.7.89.1052.140.8.88',
        '3.7.89.2',
        '3.7.89.8',
        '3.7.89.9',
        '52.140.8.64',
        '52.140.8.65',
        '52.140.8.66',
        '52.140.8.89'
    )
);
