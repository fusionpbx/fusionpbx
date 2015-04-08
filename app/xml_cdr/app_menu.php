<?php

$apps[$x]['menu'][0]['title']['en-us'] = "Call Detail Records";
$apps[$x]['menu'][0]['title']['es-cl'] = "Registro de detalle de llamada";
$apps[$x]['menu'][0]['title']['fr-fr'] = "Historiques Appels";
$apps[$x]['menu'][0]['title']['pt-pt'] = "Detalhes das Gravações de Voz";
$apps[$x]['menu'][0]['title']['pt-br'] = "Detalhes das gravações de voz";
$apps[$x]['menu'][0]['title']['pl'] = "Wykaz rozmów";
$apps[$x]['menu'][0]['title']['sv-se'] = "Detaljerad Samtalsinformation";
$apps[$x]['menu'][0]['uuid'] = "8f80e71a-31a5-6432-47a0-7f5a7b271f05";
$apps[$x]['menu'][0]['parent_uuid'] = "fd29e39c-c936-f5fc-8e2b-611681b266b5";
$apps[$x]['menu'][0]['category'] = "internal";
$apps[$x]['menu'][0]['path'] = "/app/xml_cdr/xml_cdr.php";
$apps[$x]['menu'][0]['groups'][] = "user";
$apps[$x]['menu'][0]['groups'][] = "admin";
$apps[$x]['menu'][0]['groups'][] = "superadmin";

$apps[$x]['menu'][1]['title']['en-us'] = "CDR Statistics";
$apps[$x]['menu'][1]['title']['es-cl'] = "Statistics CDR";
$apps[$x]['menu'][1]['title']['fr-fr'] = "Statistiques CDR";
$apps[$x]['menu'][1]['title']['pt-pt'] = "Statistics CDR";
$apps[$x]['menu'][1]['title']['pt-br'] = "Estatisticas do CDR";
$apps[$x]['menu'][1]['title']['pl'] = "Statystyki wykazu rozmów";
$apps[$x]['menu'][1]['title']['sv-se'] = "CDR Statistik";
$apps[$x]['menu'][1]['uuid'] = "032887d2-2315-4e10-b3a2-8989f719c80c";
$apps[$x]['menu'][1]['parent_uuid'] = "0438b504-8613-7887-c420-c837ffb20cb1";
$apps[$x]['menu'][1]['category'] = "internal";
$apps[$x]['menu'][1]['path'] = "/app/xml_cdr/xml_cdr_statistics.php";
$apps[$x]['menu'][1]['groups'][] = "user";
$apps[$x]['menu'][1]['groups'][] = "admin";
$apps[$x]['menu'][1]['groups'][] = "superadmin";

$apps[$x]['menu'][2]['title']['en-us'] = "Extension Summary";
$apps[$x]['menu'][2]['title']['es-cl'] = "Extension Summary";
$apps[$x]['menu'][2]['title']['fr-fr'] = "Résumé de l'Extension";
$apps[$x]['menu'][2]['title']['pt-pt'] = "Extension Summary";
$apps[$x]['menu'][2]['title']['pt-br'] = "Extensão do sumário";
$apps[$x]['menu'][2]['title']['pl'] = "Podsumowanie numerów wewnętrznych";
$apps[$x]['menu'][2]['title']['sv-se'] = "Anknytnings Summering";
$apps[$x]['menu'][2]['uuid'] = "4e45a3c1-6db5-417f-9abb-1d30a4fd0bf2";
$apps[$x]['menu'][2]['parent_uuid'] = "0438b504-8613-7887-c420-c837ffb20cb1";
$apps[$x]['menu'][2]['category'] = "internal";
$apps[$x]['menu'][2]['path'] = "/app/xml_cdr/xml_cdr_extension_summary.php";
$apps[$x]['menu'][2]['groups'][] = "admin";
$apps[$x]['menu'][2]['groups'][] = "superadmin";

?>