<?php
require_once __DIR__ . '/tid-list.php';

function getNextTID() {
    if (!isset($_SESSION['tid_index'])) {
        $_SESSION['tid_index'] = 0;
    }
    global $tid_list;
    $tid = $tid_list[$_SESSION['tid_index']];
    $_SESSION['tid_index'] = ($_SESSION['tid_index'] + 1) % count($tid_list);
    return $tid;
}
?> 