<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_reporting_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();
    $objects['report'] = array(
        'name'=>'Reports',
        'sync'=>'yes',
        'table'=>'ciniki_reporting_reports',
        'o_name'=>'report',
        'o_container'=>'reports',
        'fields'=>array(
            'title'=>array('name'=>'Title'),
            'frequency'=>array('name'=>'Frequency'),
            'flags'=>array('name'=>'Options', 'default'=>0x03),
            'next_date'=>array('name'=>'Next Date'),
            ),
        'history_table'=>'ciniki_reporting_history',
        );
    $objects['user'] = array(
        'name'=>'Report Users',
        'sync'=>'yes',
        'table'=>'ciniki_reporting_report_users',
        'o_name'=>'user',
        'o_container'=>'users',
        'fields'=>array(
            'report_id'=>array('name'=>'Report', 'ref'=>'ciniki.reporting.report'),
            'user_id'=>array('name'=>'User', 'ref'=>'ciniki.users.user'),
            'flags'=>array('name'=>'Options', 'default'=>'1'),
            ),
        'history_table'=>'ciniki_reporting_history',
        );
    $objects['block'] = array(
        'name'=>'Report Blocks',
        'sync'=>'yes',
        'table'=>'ciniki_reporting_report_blocks',
        'o_name'=>'block',
        'o_container'=>'block',
        'fields'=>array(
            'report_id'=>array('name'=>'Report', 'ref'=>'ciniki.reporting.report'),
            'btype'=>array('name'=>'Block Type'),
            'title'=>array('name'=>'Title', 'default'=>''),
            'sequence'=>array('name'=>'Order'),
            'block_ref'=>array('name'=>'Block'),
            'options'=>array('name'=>'Options', 'default'=>''),
            ),
        'history_table'=>'ciniki_reporting_history',
        );
    //
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
