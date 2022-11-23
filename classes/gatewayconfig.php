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

namespace paygw_payuindia;

defined('MOODLE_INTERNAL') || die();

use \core_payment\helper;

require_once(dirname(__FILE__).'/../../../../config.php');
require_once(dirname(__FILE__).'/../lib.php');

class gatewayconfig {

    private $config;
    public  $testorprod;
    public  $remoteid;
    public  $remotekey;
    public  $remotesalt;
    public  $transactionprefix;
    public  $remotebaseurl;
    public  $successurl;
    public  $failureurl;
    public  $webhookaddress;


    function __construct($component, $paymentarea, $itemid) {

        $this->config       = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payuindia');
        $this->testorprod   = $this->config->testorprod;
        $this->remoteid     = $this->config->remoteid;
        $this->transactionprefix = $this->config->transactionprefix;
        if ($this->testorprod == 'test') {
            $this->remotekey    = $this->config->remotekey;
            $this->remotesalt   = $this->config->remotesalt;
            $whaddr = $this->config->testwebhook;
            $webaddr_arr = preg_split("/\s*,\s*/", $whaddr);
            $this->webhookaddress = $webaddr_arr; 
        } else {
            $this->remotekey    = $this->config->remotekeylive;
            $this->remotesalt   = $this->config->remotesaltlive;
            $this->webhookaddress = REMOTE_WEBHOOOK_IP_WHITELIST;
        }

        $this->remotebaseurl = $this->testorprod == 'test' ? REMOTE_SYS_TEST_URL : REMOTE_SYS_PROD_URL;

        // As a convenience, add (local) success URL and failure URL that PayU system sends on redirect.
        $this->successurl = LOCAL_SUCCESS_URL;
        $this->failureurl = LOCAL_FAILURE_URL;
    }
}

