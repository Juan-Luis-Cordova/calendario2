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
/*
 * @package    local
 * @subpackage calendario
 * @copyright  2016 Javier Gonzalez <javiergonzalez@alumnos.uai.cl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . "/formslib.php");


class calendario2_add_form extends moodleform {
	public function definition() {
		$mform = $this->_form;
	
		$mform->addElement("text", "event", "Evento");
		$mform->setType( "event", PARAM_TEXT);
		$mform->addElement("text", "description", "Descripcion");
		$mform->setType( "description", PARAM_TEXT);
		$mform->addElement("date_selector", "date", "Fecha");
		$mform->setType( "date", PARAM_INT);
		
		$mform->addElement("hidden", "action", "add");
		$mform->setType("action", PARAM_TEXT);
		
		$this->add_action_buttons(true, 'Agregar Evento');
	}
	
	public function validation($data, $files){
		$errors = array();
		
		$event = $data["event"];
		$description = $data["description"];
		$date = $data["date"];
		
		if(empty($event)){
			$errors["event"] = "Debe ponerle nombre a su evento";
		}
		if(empty($description)){
			$errors["description"] = "De que se trata su evento?";
		}
		$today = time();
		if ($today > $date + 86400){
			$errors["date"] = "Debe seleccionar una fecha";	
		}
		return $errors;		
	}
}