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

use paygw_payuindia\webhooks_response;
use paygw_payuindia\payuhelper;

require_once('../../../config.php');

$authorized = false;

try {
    $gwcfg = payuhelper::get_gatewayconfig();

    if ($gwcfg != null) {
        $allowedips = $gwcfg->webhookaddress;

        # Check remote_addr against allowable webhook address
        $raddr = payuhelper::get_remote_ipaddr(); 

        for ($i = 0; $i < count($allowedips); $i++) {
            if ($raddr == $allowedips[$i]) {
                $authorized = true;
                break;
            }
        }
    }
} catch (Exception $e) {
    // We don't care what the exception is right now, except
    // we know that the remote server did not send the correct information.
    $authorized = false;
}

if (! $authorized) {
    header("HTTP/1.0 403 Forbidden");
    echo "403 Forbidden";
} else {
    // HTTP Response code sent of in process_response()
    $response_obj = new webhooks_response();
    $response_obj->process_response();

    if ($response_obj->excep == null) {
        // 200 OK print
        header('HTTP/1.0 200 OK');
        echo "200 OK";
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        echo "500 Internal Server Error";
        var_dump($response_obj->excep);
    }
}
