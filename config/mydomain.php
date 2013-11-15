<?php
$GLOBALS['config']['ldapAddress']        = 'ldap://mydomain.com';
$GLOBALS['config']['ldapUser']           = 'Administrator@mydomain.com';
$GLOBALS['config']['ldapPassword']       = 'password';
$GLOBALS['config']['dc'][0]              = 'DC=com';
$GLOBALS['config']['dc'][1]              = 'DC=mydomain';
$GLOBALS['config']['ou']                 = 'OU=test';
$GLOBALS['config']['csvName']            = 'example.csv';

//$GLOBALS['users']['memberOf']            = 'CN=GG_Apprenants,OU=GG,DC=cpfiqr,DC=com';
$GLOBALS['users']['scriptPath']          = '\\\\test\\test.bat';
$GLOBALS['users']['userAccountControl']  = '512';
?>