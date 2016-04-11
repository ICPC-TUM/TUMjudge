<?php

//classes PayloadException, SSOHelper and the template taken from
//https://github.com/ArmedGuy/discourse_sso_php

class PayloadException extends \Exception
{
}

class SSOHelper
{
    /**
     * @var
     */
    private $secret;
    /**
     * @param $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }
    /**
     * @param $payload
     * @param $signature
     * @return mixed
     */
    public function validatePayload($payload, $signature)
    {
        $payload = urldecode($payload);
        return $this->signPayload($payload) === $signature;
    }
    /**
     * @param $payload
     * @return mixed
     * @throws PayloadException
     */
    public function getNonce($payload)
    {
        $payload = urldecode($payload);
        $query   = array();
        parse_str(base64_decode($payload), $query);
        if (!array_key_exists('nonce', $query)) {
            throw new PayloadException('Nonce not found in payload');
        }
        return $query['nonce'];
    }
    /**
     * @param $nonce
     * @param $id
     * @param $email
     * @param array $extraParameters
     * @return string
     */
    public function getSignInString($nonce, $id, $email, $extraParameters = array())
    {
        $parameters = array(
            'nonce' => $nonce,
            'external_id' => $id,
            'email' => $email
        );
        $parameters = array_merge($parameters, $extraParameters);
        $payload    = base64_encode(http_build_query($parameters));
        $data       = array(
            'sso' => $payload,
            'sig' => $this->signPayload($payload)
        );
        return http_build_query($data);
    }
    /**
     * @param $payload
     * @return string
     */
    protected function signPayload($payload)
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }
}

//end of copy

require('init.php');

$sso = new SSOHelper();
$sso->setSecret(dbconfig_get('sso_secret', ''));

// load the payload passed in by Discourse
$payload   = $_GET['sso'];
$signature = $_GET['sig'];

// validate the payload
if (!($sso->validatePayload($payload, $signature))) {
    // invaild, deny
    header("HTTP/1.1 403 Forbidden");
    echo ("Bad SSO request");
    die();
}

$nonce = $sso->getNonce($payload);

if (@$_POST['cmd'] == 'login') {
    $user = trim($_POST['login']);
    $pass = trim($_POST['passwd']);
    
    if (empty($user) || empty($pass)) {
        show_failed_login('Please supply a username and password.');
    }
    
    $userdata = $DB->q('MAYBETUPLE SELECT * FROM user
                      WHERE username = %s AND enabled = 1', $user);
    if (empty($userdata['username']))
        show_failed_login('This account is not registered here.');
    $pw_null = empty($userdata['password']);
    
    if ($pw_null) {
        //LDAP IN TUM
        $conn = false;
        foreach (explode(' ', LDAP_SERVERS) as $server) {
            if ($conn == false) {
                $conn = ldap_connect($server);
            }
        }
        if (!$conn)
            show_failed_login('The authentification server is not online.');
        if (!ldap_bind($conn))
            show_failed_login('The authentification server does not accept connections.');
        
        $entry = ldap_get_entries($conn, ldap_search($conn, 'ou=Personen,ou=IN,o=TUM,c=DE', '(mail=' . $user . '@in.tum.de)'));
        if ($entry['count'] == 0)
            show_failed_login('Invalid username supplied. Please try again or contact a staff member.');
        
        $authtoken = mysql_escape_string($entry[0]['dn']);
        
        if (!ldap_check_credentials_dn($userdata['username'], $pass, $authtoken)) {
            sleep(1);
            show_failed_login('Invalid username or password supplied. ' . 'Please try again or contact a staff member.');
        }
    } else {
        //native login
        $userdata = $DB->q('MAYBETUPLE SELECT * FROM user WHERE username = %s AND password = %s AND enabled = 1', $user, md5($user . "#" . $pass));
        if (!$userdata) {
            sleep(1);
            show_failed_login("Invalid username or password supplied. Please try again or contact a staff member.");
        }
    }
    auditlog('user', $userdata['userid'], 'logged in (sso)', $ip);
    
    $extraParameters = array(
        'username' => $userdata['username'],
        'name' => $userdata['name']
    );
    
    // build query string and redirect back to the Discourse site
    $query = $sso->getSignInString($nonce, $userdata['userid'], $userdata['email'], $extraParameters);
    header('Location: https://judge.in.tum.de/discuss/session/sso_login?' . $query);
    exit(0);
} else {
    $title = 'Not Authenticated';
    $menu  = false;
    
    include(LIBWWWDIR . '/header.php');
?>
<h1>SSO Login</h1>

<p>
Please supply your credentials below, or contact a staff member for assistance.
</p>

<form action="login_sso.php?sso=<?php echo urlencode($_GET['sso']); ?>&sig=<?php echo urlencode($_GET['sig']); ?>" method="post" class="form-vertical">
<input type="hidden" name="cmd" value="login" />
<div class="form-group">
    <label for="login">User</label>
    <input type="text" class="form-control" id="login" name="login" value="" placeholder="User" autofocus>
</div>

<div class="form-group">
    <label for="passwd">Password</label>
    <input type="password" class="form-control" id="passwd" name="passwd" value="" placeholder="Password">
</div>

<div class="form-group" style="float:right; padding-top:0.5em;">
    <label style="font-size:11px;"><a href='/main/' style='color:#ffffff;'>Register new Account</a></label>
</div>

<button type="submit" class="btn btn-default">Login</button>

</form>

<?php
    include(LIBWWWDIR . '/footer.php');   
}
