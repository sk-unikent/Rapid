<?php
/**
 * CLA system mock-up
 * 
 * @author Skylar Kelty <skylarkelty@gmail.com>
 */

require_once('config.php');

$PAGE->set_url('/');
$PAGE->set_title("CLA Home");

echo $OUTPUT->header();
echo $OUTPUT->heading("CLA Administration");

$data = $DB->get_records('extract');

$table = new \Presentation\Table();
$table->set_data($data);
echo $table;

echo $OUTPUT->footer();
