<?php
//
// Description
// -----------
// This method searchs for a Report Blockss for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Report Blocks for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_reporting_blockSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'checkAccess');
    $rc = ciniki_reporting_checkAccess($ciniki, $args['tnid'], 'ciniki.reporting.blockSearch');
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
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
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
