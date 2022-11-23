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

use paygw_payuindia\success_response;
use paygw_payuindia\payuhelper;

require_once('../../../config.php');

$context = context_system::instance();
payuhelper::relogin_user($context); // This must be called here.

$PAGE->set_context($context);

// Set page url.
$PAGE->set_url(new moodle_url('/payment/gateway/payuindia/transcomplete.php'));

require_login();    // Almost redundant. Consider moving reverse hash check
                    // to relogin_user() above. 

$response_obj = new success_response();
$response_obj->process_response();

