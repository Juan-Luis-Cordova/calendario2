<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once ($CFG->dirroot . "/local/calendario2/form/form.php");
global $PAGE, $CFG, $OUTPUT, $DB, $USER;

require_login();
$userid = $USER->id;

$url = new moodle_url('/local/calendario2/index.php');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
//$PAGE->set_title($title);
//$PAGE->set_heading($title);

$formulario = new calendario2_crear_form();

echo $OUTPUT->header();

$action = optional_param("action", "viewevent", PARAM_TEXT);

if ($action=="add"){

	if ($formulario->is_cancelled()) {
		$action = "viewevent";
	}
	else if ($fromform = $formulario->get_data()) {
		echo "hay datos";
		
		$big_event = new stdClass();
		$big_event->evento = $fromform->evento;
		$big_event->descripcion = $fromform->descripcion;
		$big_event->fechacreacion = time();
		$big_event->fechaevento = $fromform->fecha;
		$big_event->iduser = $userid;
		//echo $big_event->userid;
		$insert_action = $DB->insert_record("calendario2_evento", $big_event, $returnid=true, $bulk=false);
		$action = "viewevent";
	}
}

if ($action == "viewevent"){
	$time = time();
	//fechaevento > $time AND 
	$sql = "SELECT * from mdl_calendario2_evento WHERE iduser =?";
	$eventos = $DB->get_records_sql($sql, array ("iduser"=>$userid));
	$contador = count($eventos); 
	//echo $eventos[1]->evento;
	$string = "";
	$botonurl = new moodle_url("/local/calendario2/index.php", array("action" => "add"));
	for ($i=1; $i<=$contador; $i++){
		$string.= $eventos[$i]->evento;
		$string.= "<br>";
		$string.= $eventos[$i]->descripcion;
		$string.= "<br>";
		$string.= date("d-m-Y", (float)$eventos[$i]->fechaevento);
		$string.= "<br><br><br>";
		
	}
	
	
}

if ($action == "add"){
	$formulario->display();
}

if ($action == "viewevent"){
	echo $string;
	echo $OUTPUT->single_button($botonurl,"Agregar Evento");
}

echo $OUTPUT->footer();
