<?php
//
// Description
// ===========
// This function will add a table to the report.
//
// Arguments
// ---------
// ciniki:
// tnid:                The ID of the tenant the reports is attached to.
// report_id:           The ID of the reports to get the details for.
//
// Returns
// -------
//
function ciniki_reporting_reportChunkTable($ciniki, $tnid, &$report, $chunk) {

    if( isset($chunk['textlist']) && $chunk['textlist'] != '' ) {
        $report['text'] .= $chunk['textlist'];
    }

    $html = '<table cellpadding="5">';
    $pdfhtml = '<table border="0" cellpadding="5" cellspacing="0" style="border: 0.1px solid #aaa;">';
    if( !isset($chunk['header']) || $chunk['header'] == 'yes' ) {
        $html .= "<thead><tr>";
        $pdfhtml .= "<thead><tr>";
        foreach($chunk['columns'] as $col) {
            $html .= "<th>" . $col['label'] . "</th>";
            $pdfhtml .= '<th bgcolor="#dddddd" style="border: 0.1px solid #aaa;' 
                . (isset($col['pdfwidth']) ? 'width:' . $col['pdfwidth'] : '') . '">' . $col['label'] . "</th>";
        }
        $html .= "</tr></thead>";
        $pdfhtml .= "</tr></thead>";
    }
    $html .= "<tbody>";
    $pdfhtml .= "<tbody>";

    foreach($chunk['data'] as $row) {
        $html .= "<tr>";
        $pdfhtml .= '<tr nobr="true">';
        foreach($chunk['columns'] as $col) {
            $html .= '<td'
                . (isset($col['colspan']) && $col['colspan'] != '' ? ' colspan="' . $col['colspan'] . '"' : '')
                . ' style="border: 1px solid #aaa; padding: 5px;">';
            $pdfhtml .= '<td style="border: 0.1px solid #aaa;' . (isset($col['pdfwidth']) ? 'width:' . $col['pdfwidth'] : '') . '">' ;
            if( isset($row[$col['field']]) ) {
                $field_value = $row[$col['field']];
                if( isset($col['type']) && $col['type'] == 'dollar' ) {
                    $field_value = '$' . number_format($field_value, 2);
                }
                if( isset($col['line2']) && isset($row[$col['line2']]) && $row[$col['line2']] != '' ) {
                    $field_value .= ($field_value != '' ? "\n" : '') . $row[$col['line2']];
                }
                $html .= preg_replace("/\n/", "<br/>", $field_value);
                $pdfhtml .= preg_replace("/\n/", "<br/>", $field_value);
            }
            $html .= "</td>";
            $pdfhtml .= "</td>";
        }
        $html .= "</tr>";
        $pdfhtml .= "</tr>";
    }
    
    if( isset($chunk['footer']) && count($chunk['footer']) > 0 ) {
        $html .= "<thead><tr>";
        $pdfhtml .= "<tfoot><tr>";
        foreach($chunk['footer'] as $col) {
            $field_value = $col['value'];
            if( isset($col['type']) && $col['type'] == 'dollar' ) {
                $field_value = '$' . number_format($field_value, 2);
            }
            $html .= "<th"
                . (isset($col['colspan']) && $col['colspan'] != '' ? ' colspan="' . $col['colspan'] . '"' : '')
                . ">" . $field_value . "</th>";
            $pdfhtml .= '<th bgcolor="#dddddd" style="border: 0.1px solid #aaa;' 
                . (isset($col['pdfwidth']) ? 'width:' . $col['pdfwidth'] : '') . '">' . $field_value . "</th>";
        }
        $html .= "</tr></tfoot>";
        $pdfhtml .= "</tr></tfoot>";
    }

    $html .= "</tbody>";
    $html .= "</table>";
    $pdfhtml .= "</tbody>";
    $pdfhtml .= "</table>";

    $report['html'] .= $html;


    $report['pdf']->addHtml(1, $pdfhtml);

    return array('stat'=>'ok');
}
?>
