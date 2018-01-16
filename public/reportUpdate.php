<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_reporting_reportUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'report_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reports'),
        'user_ids'=>array('required'=>'no', 'blank'=>'no', 'type'=>'idlist', 'name'=>'Users'),
        'title'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'),
        'frequency'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Frequency'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'next_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Next Date'),
        'next_time'=>array('required'=>'no', 'blank'=>'no', 'type'=>'time', 'name'=>'Next Time'),
        'addblock'=>array('required'=>'no', 'blank'=>'no', 'name'=>'New Block'),
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
    $rc = ciniki_reporting_checkAccess($ciniki, $args['tnid'], 'ciniki.reporting.reportUpdate');
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

    //
    // Get the existing values
    //
    $strsql = "SELECT ciniki_reporting_reports.id, "
        . "ciniki_reporting_reports.title, "
        . "ciniki_reporting_reports.frequency, "
        . "ciniki_reporting_reports.flags, "
        . "ciniki_reporting_reports.next_date, "
        . "ciniki_reporting_reports.next_date AS next_time "
        . "FROM ciniki_reporting_reports "
        . "WHERE ciniki_reporting_reports.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_reporting_reports.id = '" . ciniki_core_dbQuote($ciniki, $args['report_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reporting', array(
        array('container'=>'reports', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'frequency', 'flags', 'next_date', 'next_time'),
            'utctotz'=>array(
                'next_date'=>array('format'=>'Y-m-d', 'timezone'=>$intl_timezone),
                'next_time'=>array('format'=>'H:i:s', 'timezone'=>$intl_timezone),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.9', 'msg'=>'Reports not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['reports'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.10', 'msg'=>'Report not found'));
    }
    $report = $rc['reports'][0];

    //
    // Check if date or time was passed
    //
    if( isset($args['next_date']) || isset($args['next_time']) ) {
        $date_str = '';
        if( isset($args['next_date']) ) {
            $date_str = $args['next_date'];
        } else {
            $date_str = $report['next_date'];
        }
        if( isset($args['next_time']) ) {
            $date_str .= ' ' . $args['next_time'];
        } else {
            $date_str .= ' ' . $report['next_time'];
        }
        $ts = strtotime($date_str);
        if( $ts === FALSE || $ts < 1 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.reporting.11', 'msg'=>'Invalid date or time format'));
        }
        $dt = new DateTime("@" . $ts, new DateTimezone($intl_timezone));
        $args['next_date'] = $dt->format('Y-m-d H:i:s');
    }

    //
    // Get the list of available blocks
    //
    $available_blocks = array();
    foreach($ciniki['tenant']['modules'] as $module) {
        //
        // Check if the module has a file reporting/blocks.php
        //
        $rc = ciniki_core_loadMethod($ciniki, $module['package'], $module['module'], 'reporting', 'blocks');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['tnid'], array());
            if( $rc['stat'] == 'ok' ) {
                $available_blocks = array_merge($available_blocks, $rc['blocks']);
            }
        }
    }

    //
    // Get the list of existing blocks to compare with new later
    //
    $strsql = "SELECT id, btype, title, sequence, block_ref, options "
        . "FROM ciniki_reporting_report_blocks "
        . "WHERE report_id = '" . ciniki_core_dbQuote($ciniki, $args['report_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sequence "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.reporting', array(
        array('container'=>'blocks', 'fname'=>'id', 'fields'=>array('id', 'btype', 'title', 'sequence', 'block_ref', 'options')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $existing_blocks = array();
    if( isset($rc['blocks']) ) {
        $existing_blocks = $rc['blocks'];
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.reporting');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Reports in the database
    //
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.reporting.report', $args['report_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reporting');
        return $rc;
    }

    //
    // Check if the user_ids are updated
    //
    if( isset($args['user_ids']) ) {
        //
        // Get the list of tenant users
        //
        $strsql = "SELECT id "
            . "FROM ciniki_tenant_users "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND status = 10 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.reporting', 'users', 'id');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reporting');
            return $rc;
        }
        $users = array();
        if( isset($rc['users']) ) {
            $users = $rc['users'];
        }

        //
        // Get the current list of users
        //
        $strsql = "SELECT id, uuid, user_id "
            . "FROM ciniki_reporting_report_users "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND report_id = '" . ciniki_core_dbQuote($ciniki, $args['report_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.reporting', array(
            array('container'=>'users', 'fname'=>'user_id', 'fields'=>array('id', 'uuid', 'user_id')),
            ));
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reporting');
            return $rc;
        }
        $report_users = array();
        if( isset($rc['users']) ) {
            $report_user_ids = array_keys($rc['users']);
            $report_users = $rc['users'];
        }

        //
        // Check for users that need to be added
        //
        foreach($args['user_ids'] as $user_id) {
            if( !in_array($user_id, $report_user_ids) ) {
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.reporting.reportuser', array(
                    'report_id'=>$args['report_id'],
                    'user_id'=>$user_id, 
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reporting');
                    return $rc;
                }
            }
        }

        //
        // Check for users need to be removed
        //
        foreach($report_users as $user_id => $user) {
            if( !in_array($user_id, $args['user_ids']) ) {
                $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.reporting.reportuser', $user['id'], $user['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reporting');
                    return $rc;
                }
            }
        }
    }

    //
    // Check if block options need updating
    //
    foreach($existing_blocks as $bid => $block) {
        if( !isset($available_blocks[$block['block_ref']]) ) {
            continue;
        }
        $b = $available_blocks[$block['block_ref']];
        $values = unserialize($block['options']);
        foreach($b['options'] as $oid => $option) {
            //
            // Make sure at least the default value exists in the values array
            //
            if( !isset($values[$oid]) ) {
                $values[$oid] = $option['default'];
            }
            if( isset($ciniki['request']['args']['block_' . $block['id'] . '_' . $oid]) ) {
                $values[$oid] = $ciniki['request']['args']['block_' . $block['id'] . '_' . $oid];
            }
        }
        $new_options = serialize($values);
        $update_args = array();
        if( $new_options != $block['options'] ) {
            $update_args['options'] = $new_options;
        }
        //
        // Check for new title or sequence
        //
        if( isset($ciniki['request']['args']['block_' . $block['id'] . '_title']) 
            && $ciniki['request']['args']['block_' . $block['id'] . '_title'] != $block['title'] 
            ) {
            $update_args['title'] = $ciniki['request']['args']['block_' . $block['id'] . '_title'];
        }
        if( isset($ciniki['request']['args']['block_' . $block['id'] . '_sequence']) 
            && $ciniki['request']['args']['block_' . $block['id'] . '_sequence'] != $block['sequence'] 
            ) {
            $update_args['sequence'] = $ciniki['request']['args']['block_' . $block['id'] . '_sequence'];
        }
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.reporting.reportblock', $block['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.reporting');
                return $rc;
            }
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.reporting');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'reporting');

    return array('stat'=>'ok');
}
?>
