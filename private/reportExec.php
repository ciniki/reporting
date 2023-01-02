<?php
//
// Description
// ===========
// This function runs the report and builds the text and pdf versions.
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
function ciniki_reporting_reportExec($ciniki, $tnid, $args) {

    //
    // Load the report
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportLoad');
    $rc = ciniki_reporting_reportLoad($ciniki, $tnid, $args['report_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['report']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.14', 'msg'=>'Unable to find report.'));
    }
    $report = $rc['report'];

    //
    // Return if there are no blocks
    //
    if( !isset($report['blocks']) ) {
        $report['text'] = "The report is empty\n\n";
        $report['html'] = "The report is empty\n\n";
        return array('stat'=>'ok', 'report'=>$report);
    }

    //
    // Add the block data (chunks)
    //
    foreach($report['blocks'] as $bid => $block) {
        if( isset($args['start_date']) ) {
            $block['options']['start_date'] = $args['start_date'];
        }
        if( isset($args['end_date']) ) {
            $block['options']['end_date'] = $args['end_date'];
        }
        list($pkg, $mod, $blockname) = explode('.', $block['block_ref']);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'reporting', 'block');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $tnid, $block);
            if( $rc['stat'] != 'ok' ) {
                error_log('RPTERR[01]: ' . print_r($rc, true));
            } elseif( isset($rc['chunks']) ) {
                $report['blocks'][$bid]['chunks'] = $rc['chunks'];
                if( isset($rc['dates']) && $rc['dates'] == 'yes' ) {    
                    $report['dates'] = 'yes';
                }
            }
        }
    }

    //
    // Load functions required to assemble report
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportBlock');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportChunkMessage');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportChunkText');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportChunkTable');

    //
    // Start the report
    //
    $rc = ciniki_reporting_reportStart($ciniki, $tnid, $report);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'report'=>$report, 'err'=>array('code'=>'ciniki.reporting.15', 'msg'=>'Unable to start report', 'err'=>$rc['err']));
    }

    //
    // Go through all the blocks/chunks
    //
    $num_chunks = 0;
    foreach($report['blocks'] as $bid => $block) {
        if( isset($block['chunks']) ) { 
            $rc = ciniki_reporting_reportBlock($ciniki, $tnid, $report, $block);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'report'=>$report, 'err'=>array('code'=>'ciniki.reporting.16', 'msg'=>'Unable to add block', 'err'=>$rc['err']));
            }
            $num_chunks+=count($block['chunks']);
        }
    }

    if( $num_chunks == 0 ) {
        return array('stat'=>'empty', 'report'=>$report);
    }

    return array('stat'=>'ok', 'report'=>$report);
}
?>
