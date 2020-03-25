<?php
//
// Description
// -----------
// This method will return the list of Report Blockss for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Report Blocks for.
//
// Returns
// -------
//
function ciniki_reporting_blockList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'checkAccess');
    $rc = ciniki_reporting_checkAccess($ciniki, $args['tnid'], 'ciniki.reporting.blockList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of block
    //
    $strsql = "SELECT ciniki_reporting_report_blocks.id, "
        . "ciniki_reporting_report_blocks.report_id, "
        . "ciniki_reporting_report_blocks.btype, "
        . "ciniki_reporting_report_blocks.title, "
        . "ciniki_reporting_report_blocks.sequence, "
        . "ciniki_reporting_report_blocks.block_ref "
        . "FROM ciniki_reporting_report_blocks "
        . "WHERE ciniki_reporting_report_blocks.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reporting', array(
        array('container'=>'block', 'fname'=>'id', 
            'fields'=>array('id', 'report_id', 'btype', 'title', 'sequence', 'block_ref')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['block']) ) {
        $block = $rc['block'];
        $block_ids = array();
        foreach($block as $iid => $block) {
            $block_ids[] = $block['id'];
        }
    } else {
        $block = array();
        $block_ids = array();
    }

    return array('stat'=>'ok', 'block'=>$block, 'nplist'=>$block_ids);
}
?>
