<?php
//
// Description
// ===========
// This function will add a chunk of text to the report.
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
function ciniki_reporting_reportChunkMessage($ciniki, $tnid, &$report, $chunk) {

    $report['text'] .= $chunk['content'] . "\n\n";

    $rc = ciniki_web_processContent($ciniki, array(), $chunk['content'], 'message');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $report['html'] .= $rc['content'] . "\n";

    $report['pdf']->addHtml(1, $rc['content']);

    return array('stat'=>'ok');
}
?>
