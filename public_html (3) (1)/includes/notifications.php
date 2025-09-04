<?php
// Notification settings keys + defaults
function notify_defaults() {
    return [
        'notify_assign_agent'     => '1',   // email agent on assignment
        'notify_status_owner'     => '1',   // email ticket owner on status change
        'notify_status_agent'     => '1',   // email assigned agent on status change
        'notify_signup_user'      => '1',   // welcome email to new user
        'notify_signup_admin'     => '1',   // alert admin on new signup
        'notify_new_ticket_admin' => '1',   // NEW: alert admin on new ticket creation
    ];
}

// Get all current notify settings merged with defaults
function notify_get_all() {
    $defs = notify_defaults();
    $out = [];
    foreach ($defs as $k=>$v) {
        $out[$k] = function_exists('setting_get') ? setting_get($k, $v) : $v;
    }
    return $out;
}

// Quick checker
function notify_is_enabled($key) {
    $defs = notify_defaults();
    $def = isset($defs[$key]) ? $defs[$key] : '0';
    return (function_exists('setting_get') ? setting_get($key, $def) : $def) === '1';
}
