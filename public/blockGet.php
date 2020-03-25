<?php
//
// Description
// ===========
// This method will return all the information about an report blocks.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the report blocks is attached to.
// block_id:          The ID of the report blocks to get the details for.
//
// Returns
// -------
//
function ciniki_reporting_blockGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'block_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Report Blocks'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'checkAccess');
    $rc = ciniki_reporting_checkAccess($ciniki, $args['tnid'], 'ciniki.reporting.blockGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Report Blocks
    //
    if( $args['block_id'] == 0 ) {
        $block = array('id'=>0,
            'report_id'=>'',
            'btype'=>'',
            'title'=>'',
            'sequence'=>'',
            'block_ref'=>'',
            'options'=>'',
        );
    }

    //
    // Get the details for an existing Report Blocks
    //
    else {
        $strsql = "SELECT ciniki_reporting_report_blocks.id, "
            . "ciniki_reporting_report_blocks.report_id, "
            . "ciniki_reporting_report_blocks.btype, "
            . "ciniki_reporting_report_blocks.title, "
            . "ciniki_reporting_report_blocks.sequence, "
            . "ciniki_reporting_report_blocks.block_ref, "
            . "ciniki_reporting_report_blocks.options "
            . "FROM ciniki_reporting_report_blocks "
            . "WHERE ciniki_reporting_report_blocks.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_reporting_report_blocks.id = '" . ciniki_core_dbQuote($ciniki, $args['block_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reporting', array(
            array('container'=>'block', 'fname'=>'id', 
                'fields'=>array('report_id', 'btype', 
                    'block_title'=>'title', 'block_sequence'=>'sequence', 'block_ref', 'options'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.22', 'msg'=>'Report Blocks not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['block'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.23', 'msg'=>'Unable to find Report Blocks'));
        }
        $block = $rc['block'][0];
        $block['options'] = unserialize($block['options']);
    }

    $rsp = array('stat'=>'ok', 'block'=>$block);

    //
    // Get the list of available blocks
    //
    $rsp['availableblocks'] = array();
    foreach($ciniki['tenant']['modules'] as $module) {
        //
        // Check if the module has a file reporting/blocks.php 
        //
        $rc = ciniki_core_loadMethod($ciniki, $module['package'], $module['module'], 'reporting', 'blocks');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['tnid'], array());
            if( $rc['stat'] == 'ok' ) {
                $rsp['availableblocks'] = array_merge($rsp['availableblocks'], $rc['blocks']);
            }
        }
    }

    return $rsp;
}
?>
