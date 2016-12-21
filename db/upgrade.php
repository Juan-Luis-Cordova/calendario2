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
 * This file keeps track of upgrades to the evaluaciones block
*
* Sometimes, changes between versions involve alterations to database structures
* and other major things that may break installations.
*
* The upgrade function in this file will attempt to perform all the necessary
* actions to upgrade your older installation to the current version.
*
* If there's something it cannot do itself, it will tell you what you need to do.
*
* The commands in here will all be database-neutral, using the methods of
* database_manager class
*
* Please do not forget to use upgrade_set_timeout()
* before any action that may take longer time to finish.
*
* @since 2.0
* @package blocks
* @copyright 2016 Matías Queirolo
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
/**
 *
* @param int $oldversion
* @param object $block
*/
function xmldb_local_calendario2_upgrade($oldversion) {
	global $CFG, $DB;
	$dbman = $DB->get_manager();
	
	if ($oldversion < 2016121902) {
	
		// Define table calendario2_evento to be created.
		$table = new xmldb_table('calendario2_evento');
	
		// Adding fields to table calendario2_evento.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('evento', XMLDB_TYPE_CHAR, '120', null, XMLDB_NOTNULL, null, null);
		$table->add_field('descripcion', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
		$table->add_field('fechacreacion', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
		$table->add_field('fechaevento', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
		$table->add_field('iduser', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
	
		// Adding keys to table calendario2_evento.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
		$table->add_key('iduser', XMLDB_KEY_FOREIGN, array('iduser'), 'mdl_user', array('id'));
	
		// Conditionally launch create table for calendario2_evento.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}
	
		// Calendario2 savepoint reached.
		upgrade_plugin_savepoint(true, 2016121902, 'local', 'calendario2');
	}

	return true;
}