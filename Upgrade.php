<?php
ignore_user_abort(true);
set_time_limit(0);
header('Content-Type: text/plain; charset=utf-8');
if (file_exists(__DIR__ . '/upgrade.lock')) die("Script is locked.\n");
touch(__DIR__ . '/upgrade.lock');
ob_start();
$fp = fopen(__DIR__ . '/upgrade.log', 'w');
function output($text) {
    global $fp;
    fputs($fp, $text);
    echo $text;
    ob_flush();
}
function quit($text) {
    global $fp;
    output($text);
    fclose($fp);
    exit;
}
if (!@include_once dirname(dirname(dirname(__DIR__))) . '/config.inc.php')
    quit("Cannot access config.\n");
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    quit("Error({$errno}): $errstr in {$errfile} on line {$errline}\n");
});
set_exception_handler(function($e) {
    quit("Exception({$e->getCode()}): {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}\n");
});
output("Start upgrading...\nDetails will also be written to the log file (./upgrade.log). If the connection lost, you can check it later.\n");
output("==========\n\n");
$db = Typecho_Db::get();
$rows = $db->fetchAll($db->select('cid')->from('table.fields')->where('name = ?', 'pp_isEnabled')->where('str_value = ?', '1'));
output("Scanning posts...\n");
foreach($rows as $row) {
    $cid = $row['cid'];
    output("cid = {$cid}, pp_isEnable = 1, checking...\n");
    $sep = $db->fetchRow($db->select('str_value')->from('table.fields')->where('cid = ?', $cid)->where('name = ?', 'pp_sep'));
    if (!$sep) {
        output(" -> No \"pp_sep\" field, skip.\n");
        continue;
    }
    $sep = $sep['str_value'];
    output(" -> pp_sep = \"{$sep}\"\n");
    $pwds = $db->fetchRow($db->select('str_value')->from('table.fields')->where('cid = ?', $cid)->where('name = ?', 'pp_passwords'));
    if (!$pwds) {
        output(" -> No \"pp_passwords\" field, what the hell?\n");
        continue;
    }
    $pwds = $pwds['str_value'];
    $before = $pwds;
    if ($pwds) {
        if (!$sep) $pwds = array($pwds);
        else $pwds = explode($sep, $pwds);
        $pwds = json_encode($pwds);
    }
    output(" -> Automatically converting pp_passwords \"{$before}\" to \"{$pwds}\"...\n");
    $db->query(
        $db->update('table.fields')->rows(array('str_value' => $pwds))->where('cid = ?', $cid)->where('name = ?', 'pp_passwords'),
        Typecho_Db::WRITE
    );
    output(" -> Removing \"pp_passwords\" field...\n");
    $db->query(
        $db->delete('table.fields')->where('cid = ?', $cid)->where('name = ?', 'pp_sep'),
        Typecho_Db::WRITE
    );
    output(" -> Done.\n");
}
output("==========\n\n");
quit("All works completed successfully. For security reasons, the script will be locked.\n");
