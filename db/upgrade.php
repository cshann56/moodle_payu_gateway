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
 * Plugin upgrade steps are defined here.
 *
 * @package     paygw_payuindia
 * @category    upgrade
 * @copyright   2022 Christopher Shannon <cshannon108@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute paygw_payuindia upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_paygw_payuindia_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2022100402) {

        // Define field source to be added to paygw_payuindia_response.
        $table = new xmldb_table('paygw_payuindia_response');
        $field = new xmldb_field('source', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'redirect', 'datetime');

        // Conditionally launch add field source.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field remoteaddr to be added to paygw_payuindia_response.
        $field = new xmldb_field('remoteaddr', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'source');

        // Conditionally launch add field remoteaddr.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Payuindia savepoint reached.
        upgrade_plugin_savepoint(true, 2022100402, 'paygw', 'payuindia');
    }

    if ($oldversion < 2022100403) {

        // Define index mihpayid_idx (unique) to be added to paygw_payuindia_response.
        $table = new xmldb_table('paygw_payuindia_response');
        $index = new xmldb_index('mihpayid_idx', XMLDB_INDEX_UNIQUE, ['mihpayid']);

        // Conditionally launch add index mihpayid_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Payuindia savepoint reached.
        upgrade_plugin_savepoint(true, 2022100403, 'paygw', 'payuindia');
    }

    // For further information please read {@link https://docs.moodle.org/dev/Upgrade_API}.
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at {@link https://docs.moodle.org/dev/XMLDB_editor}.

    return true;
}
