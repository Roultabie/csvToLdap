<?php
/**
* TODO: Ajouter la possibilité de réinit le DN pour les ajouts de masse (sinon, comme ici je suis obligé d'ajouter le CN sans passer par constructDn())
*/
class activeDirectory
{ 
    function __construct()
    {
        putenv("TLS_REQCERT=never");
        $this->ldapAddress      = $GLOBALS['config']['ldapAddress'];
        $this->globalAttributes = $GLOBALS['users'];
    }

    public function connect($server, $username, $password, $port = '')
    {
        $this->ds = ldap_connect($server);
        if ($this->ds) {
            ldap_bind($this->ds, $username, $password);
        }
    }

    public function prepare($resource)
    {
        if (is_array($resource)) {
            foreach ($resource as $key => $infos) {
                foreach ($infos as $type => $value) {
                    $result[$key][$type] = $value;
                    $cn                                 = mb_convert_case($resource[$key]['sn'] . ' ' . $resource[$key]['givenName'], constant($GLOBALS['config']['cnFormat']));
                    $result[$key]['cn']                 = $cn;
                    //$sAMAccountName                     = mb_strtolower($resource[$key]['sn'] . $GLOBALS['config']['accountSeparator'] . $resource[$key]['givenName']);
                    $sAMAccountName                     = mb_strtolower($resource[$key]['givenName'] . $GLOBALS['config']['accountSeparator'] . $resource[$key]['sn']);
                    $sAMAccountName                     = str_replace(' ', '', $sAMAccountName);
                    $sAMAccountName                     = $this->removeAccents($sAMAccountName);
                    $result[$key]['sAMAccountName']     = $sAMAccountName;
                    $userPrincipalName                  = $sAMAccountName . '@' . implode('.', $this->readDn('DC', 'DESC'));
                    $result[$key]['userPrincipalName']  = $userPrincipalName;
                    $result[$key]['displayName']        = $cn;
                    if (strrpos($this->ldapAddress, 'ldaps') !== FALSE) {
                        $password                           = $this->generatePassword();
                        $result[$key]["unicodePwd"]         = $this->formatPasswordToAD($password);
                    }
                    if ($type === 'sn') {
                        $result[$key][$type] = mb_convert_case($value, constant($GLOBALS['config']['cnFormat']));
                    }
                    if ($type === 'givenName') {
                        $result[$key][$type] = mb_convert_case($value, constant($GLOBALS['config']['cnFormat']));
                    }
                }
                if (is_array($this->globalAttributes)) {
                    $result[$key] = array_merge($result[$key], $this->globalAttributes);
                }
                $result[$key]['objectClass'][]          = 'user';
                //$this->constructDn('CN=' . $cn);
                $result[$key]['dn']                     = 'CN=' . $cn . ',' . $this->listDn('DESC');
            }
            $this->resource = $result;
        }
    }

    public function record()
    {
        if (is_array($this->resource)) {
            foreach ($this->resource as $key => $infos) {
                if (is_array($infos)) {
                    $dn     = array_pop($infos);
                    $record = ldap_add($this->ds, $dn, $infos);
                }
            }
        }
    }

    public function constructDn($element)
    {
        if (strpos($element, '=') !== FALSE) {
            $parts = explode('=', $element);
            $this->dn[$parts[0]][] = $parts[1];
        }
    }

    private function listDn($order='ASC', $element = '', $glue = ',')
    {
        if (is_array($this->dn)) {
            foreach ($this->dn as $dn => $parts) {
                if (is_array($parts)) {
                    foreach ($parts as $key => $value) {
                        if (!empty($element)) {
                            if ($dn === $element) {
                                $result[] = $dn . '=' . $value;
                            }
                        }
                        else {
                            $result[] = $dn . '=' . $value;
                        }
                    }
                }
            }
            if ($order === 'DESC') {
                $result = array_reverse($result);
            }
            $result = implode($glue, $result);
        }

        return $result;
    }

    private function readDn($element, $order='ASC')
    {
        
        if (!empty($element)) {
            if (is_array($this->dn)) {
                foreach ($this->dn as $dn => $parts) {
                    if (is_array($parts)) {
                        foreach ($parts as $key => $value) {
                            if ($dn === $element) {
                                $result[] = $value;
                            }
                        }
                    }
                }
            }
        }
        if ($order === 'DESC') {
            $result = array_reverse($result);
        }
        return $result;
    }

    private function removeAccents($txt) {
        $txt = utf8_encode($txt);
        $txt = str_replace('œ', 'oe', $txt);
        $txt = str_replace('Œ', 'Oe', $txt);
        $txt = str_replace('æ', 'ae', $txt);
        $txt = str_replace('Æ', 'Ae', $txt);
        mb_regex_encoding('UTF-8');
        $txt = mb_ereg_replace('[ÀÁÂÃÄÅĀĂǍẠẢẤẦẨẪẬẮẰẲẴẶǺĄ]', 'A', $txt);
        $txt = mb_ereg_replace('[àáâãäåāăǎạảấầẩẫậắằẳẵặǻą]', 'a', $txt);
        $txt = mb_ereg_replace('[ÇĆĈĊČ]', 'C', $txt);
        $txt = mb_ereg_replace('[çćĉċč]', 'c', $txt);
        $txt = mb_ereg_replace('[ÐĎĐ]', 'D', $txt);
        $txt = mb_ereg_replace('[ďđ]', 'd', $txt);
        $txt = mb_ereg_replace('[ÈÉÊËĒĔĖĘĚẸẺẼẾỀỂỄỆ]', 'E', $txt);
        $txt = mb_ereg_replace('[èéêëēĕėęěẹẻẽếềểễệ]', 'e', $txt);
        $txt = mb_ereg_replace('[ĜĞĠĢ]', 'G', $txt);
        $txt = mb_ereg_replace('[ĝğġģ]', 'g', $txt);
        $txt = mb_ereg_replace('[ĤĦ]', 'H', $txt);
        $txt = mb_ereg_replace('[ĥħ]', 'h', $txt);
        $txt = mb_ereg_replace('[ÌÍÎÏĨĪĬĮİǏỈỊ]', 'I', $txt);
        $txt = mb_ereg_replace('[ìíîïĩīĭįıǐỉị]', 'i', $txt);
        $txt = str_replace('Ĵ', 'J', $txt);
        $txt = str_replace('ĵ', 'j', $txt);
        $txt = str_replace('Ķ', 'K', $txt);
        $txt = str_replace('ķ', 'k', $txt);
        $txt = mb_ereg_replace('[ĹĻĽĿŁ]', 'L', $txt);
        $txt = mb_ereg_replace('[ĺļľŀł]', 'l', $txt);
        $txt = mb_ereg_replace('[ÑŃŅŇ]', 'N', $txt);
        $txt = mb_ereg_replace('[ñńņňŉ]', 'n', $txt);
        $txt = mb_ereg_replace('[ÒÓÔÕÖØŌŎŐƠǑǾỌỎỐỒỔỖỘỚỜỞỠỢ]', 'O', $txt);
        $txt = mb_ereg_replace('[òóôõöøōŏőơǒǿọỏốồổỗộớờởỡợð]', 'o', $txt);
        $txt = mb_ereg_replace('[ŔŖŘ]', 'R', $txt);
        $txt = mb_ereg_replace('[ŕŗř]', 'r', $txt);
        $txt = mb_ereg_replace('[ŚŜŞŠ]', 'S', $txt);
        $txt = mb_ereg_replace('[śŝşš]', 's', $txt);
        $txt = mb_ereg_replace('[ŢŤŦ]', 'T', $txt);
        $txt = mb_ereg_replace('[ţťŧ]', 't', $txt);
        $txt = mb_ereg_replace('[ÙÚÛÜŨŪŬŮŰŲƯǓǕǗǙǛỤỦỨỪỬỮỰ]', 'U', $txt);
        $txt = mb_ereg_replace('[ùúûüũūŭůűųưǔǖǘǚǜụủứừửữự]', 'u', $txt);
        $txt = mb_ereg_replace('[ŴẀẂẄ]', 'W', $txt);
        $txt = mb_ereg_replace('[ŵẁẃẅ]', 'w', $txt);
        $txt = mb_ereg_replace('[ÝŶŸỲỸỶỴ]', 'Y', $txt);
        $txt = mb_ereg_replace('[ýÿŷỹỵỷỳ]', 'y', $txt);
        $txt = mb_ereg_replace('[ŹŻŽ]', 'Z', $txt);
        $txt = mb_ereg_replace('[źżž]', 'z', $txt);
        return $txt;
    }

    /**
    * smallHash via shaarli (sebsauvage)
    * @param string $string [description]
    * @return string $hash [description]
    */
    private function generatePassword()
    {
        $string = 'abcdefghijklmnopqrstuvwxyz';
        $hash  .= $string . strtoupper($string);
        $hash  .= $hash . '-*/_:;.!?';
        $hash   = str_shuffle($hash);
        $hash   = rtrim(base64_encode(hash('crc32', $hash, TRUE)), '=');
        $hash   = mb_ereg_replace('[\+\/\=]', $string[rand(1, strlen($string))], $hash);
        $hash   = str_shuffle($hash);
        $hash   = utf8_encode($hash);
        return $hash;
    }

    private function formatPasswordToAD($password)
    {
        $password = "\"" . $password . "\"";
        for ($i = 0; $i < (strlen($password)); $i++) {
            $uni_passwd .= "{$password{$i}}\000";
        }
        return $uni_passwd;
    }

    function __destruct()
    {
        ldap_close($this->ds);
    }

}

// // on suppose que le serveur LDAP est sur le serveur local
// if ($ds)
// {
// // Connexion avec une identité qui permet les modifications
//  ldap_bind($ds, "Administrateur@pdb.com", "hyndips29");
// // prepare les données
 
// // Ajoute les données au dossier
//  $r=ldap_add($ds, "CN=dupont Jean,OU=testLdifImport,DC=pdb,DC=com", $info);
//  ldap_close($ds);
// }
// else
// {
//   echo 'Impossible de se connecter au serveur LDAP';
// }
?>