<?php
//
// Description
// -----------
// This function returns the settings for the module and the main menu items and settings menu items
//
// Arguments
// ---------
// ciniki:
// tnid:
// args: The arguments for the hook
//
// Returns
// -------
//
function ciniki_reporting_hooks_uiSettings(&$ciniki, $tnid, $args) {
    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array(), 'settings_menu_items'=>array());

    //
    // Check permissions for what menu items should be available
    //
    // **NOTE**: This module is only available to owners. Reports could have
    //           sensitive data about employee tracking that other employees should
    //           not get access to!
    //
    if( isset($ciniki['tenant']['modules']['ciniki.reporting'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['ciniki.reporting'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>1200,
            'label'=>'Reporting',
            'edit'=>array('app'=>'ciniki.reporting.main'),
            );
        $rsp['menu_items'][] = $menu_item;
    }

    return $rsp;
}
?>
