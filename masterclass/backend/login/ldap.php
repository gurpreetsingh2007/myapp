<?php
function ldap_authenticate($username, $password)
{
    $ldap_host = "ldap://172.18.91.1";
    $ldap_dn = "WPDC";//server

    // Construct user DN
    $ldap_user = "cn=$username,cn=Users,dc=ads,dc=coopolis,dc=it";

    // Connect to LDAP server
    $ldap_conn = ldap_connect($ldap_host);
    if (!$ldap_conn) {
        return ["success" => false, "message" => "Could not connect to LDAP server."];
    }

    ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

    // Attempt to bind (authenticate)
    $bind = @ldap_bind($ldap_conn, $ldap_user, $password);
    if ($bind) {
        ldap_unbind($ldap_conn);
        return ["success" => true, "message" => "Authentication successful."];
    } else {
        ldap_unbind($ldap_conn);
        return ["success" => false, "message" => "Invalid credentials."];
    }
}
?>