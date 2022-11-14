<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * auth_db installer script.
 *
 * @package    paygw_payuindia\db 
 * @copyright  2022 Christopher Shannon
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_paygw_payuindia_install() {
    global $CFG, $DB;


    // We assume that countries and state tables exists by now.
    $rec = $DB->get_record_sql('SELECT COUNT(*) as numcountries FROM {paygw_payuindia_countries}');

    if ($rec->numcountries == 0) {
        // Records were not loaded. This is probably a new install.
        // So, load in the country and state tables.

        try {

            $transaction = $DB->start_delegated_transaction();

            // Load the countries into the new countries table.
            if (($handle = fopen(__DIR__ . '/../resources/.countries.csv', "r")) !== FALSE) {

                fgetcsv($handle); // Get the header record and discard.
                
                $counter = 1;
                $dataobjs = [];
                
                while (($data = fgetcsv($handle)) !== FALSE) {

                    array_push($dataobjs, [
                        'countryid' => $data[0],
                        'name'      => $data[1],
                        'iso3'      => $data[2],
                        'currency'  => $data[7],
                        'currency_name'     => $data[8],
                        'currency_symbol'   => $data[9]]);
                    $counter++;

                    if ($counter == 100) { // We have 100 records.
                        // Save records
                        $DB->insert_records('paygw_payuindia_countries', $dataobjs);
                        $dataobjs = [];
                        $counter = 1;
                    }
                }

                // While loop closed.
                fclose($handle);

                // Save last few records if any.
                if (count($dataobjs) > 0) {
                    $DB->insert_records('paygw_payuindia_countries', $dataobjs);
                }
            }

            // Load the states into the new states table.
            if (($handle = fopen(__DIR__ . '/../resources/.states.csv', "r")) !== FALSE) {

                fgetcsv($handle); // Get the header record and discard.

                $counter = 1;
                $dataobjs = [];
                
                while (($data = fgetcsv($handle)) !== FALSE) {

                    array_push($dataobjs, [
                        'stateid'       => $data[0],
                        'countryid'     => $data[2],
                        'name'          => $data[1],
                        'state_code'    => $data[5]]);
                    $counter++;

                    if ($counter == 100) { // We have 100 records.
                        // Save records
                        $DB->insert_records('paygw_payuindia_states', $dataobjs);
                        $dataobjs = [];
                        $counter = 1;
                    }
                }

                // While loop closed.
                fclose($handle);

                // Save last few records if any.
                if (count($dataobjs) > 0) {
                    $DB->insert_records('paygw_payuindia_states', $dataobjs);
                }
            }

            $transaction->allow_commit();

        } catch (Exception $e) {

            $transaction->rollback($e);
        }
    } 
}
