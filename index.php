<?php

/*
*
* This is a simple script to handle web payments in grlc
* Does not require sql database
* only php5 + optional pear mail
*
* Everything is encrypted aes-256-cbc algorithm like in a bank
* Generating payments is only possible when using a cold wallet address.
* Demo https://grlc.eu/pay
*
* Written by: tomiiiii
* Mialto: t0mi[:-)]protonmail.com 
* Website: https://grlc.eu/pay
* Date: 2019-09-02
* Version: 1 
* Licencia: Lesser General Public License (LGPL)   
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 2.1 of the License, or (at your option) any later version.
*  
*/


/************************************
 Config start
************************************/

/* !!! set random secret password for encrypted links !!! */
$encryption_key = "{your_random_password_example_jgsdf78673476dr%Resfcd}"; 

$data_dir = "./data"; /* data dir must have permission 777 */
$debug_mode = false;
$domain_name = 'index.php'; /* https://domain/path.file where the script will run */
$link_validity_in_seconds =  3600*24*7; /* 7 day */

/*********************************
* Mail options 
*
*   This solution is based on the pear mail class
*   To send emails
*   You must install pear mail
*   
*
*   install php-pear
*   pear install Mail
*   1. pear upgrade --force --alldeps http://pear.php.net/get/PEAR-1.10.1
*   2. pear clear-cache
*   3. pear update-channels
*   4. pear upgrade
*   5. pear upgrade-all
*   6. pear install Auth_SASL
*   7. pear install pear/Net_SMTP
*   8. check install https://pear.php.net/manual/en/installation.checking.php
*
*    next
*
*   To enable sending emails from the gmail account.
*   1. Create new account in google
*   2. Enable:
*      1). https://www.google.com/settings/security/lesssecureapps
*      and
*      2). https://accounts.google.com/DisplayUnlockCaptcha
*   
*
*/

/* Turn email sending on or off */

$enable_mail = false; /* true = on | false = off | default = false */
$your_mail_name = ''; /* your email name example: user@gmail.com */
$host_smtp = 'ssl://smtp.gmail.com'; 
$port_smtp = '465'; 
$auth_smtp = true;
$user = ''; /* login to gmail */
$pass = ''; /* password to gmail */

/************************************
* mail options end
*************************************/

$made_in_grlc = '<p class="mt-5 mb-3 text-muted text-center">2018-'.date("Y").' made in love of <a href="https://grlc.eu/pay">garlic</a> / <a href="?pid=api_code">API GET</a> <br> <a href="https://grlc.eu/pay/grlcpay.zip">Download script</a></p>';

/************************************
 Config end
************************************/

/* start style css */
define("STYLE_CSS",' 

<style>
html,
body {
  height: 100%;
}

body {
  display: -ms-flexbox;
  display: flex;
  -ms-flex-align: center;
  align-items: center;
  padding-top: 40px;
  padding-bottom: 40px;
  background-color: #f5f5f5;
}

.form-pay {
  width: 100%;
  max-width: 420px;
  padding: 15px;
  margin: auto;
}

.form-label-group {
  position: relative;
  margin-bottom: 1rem;
}

.form-label-group > input,
.form-label-group > label {
  height: 3.125rem;
  padding: .75rem;
}

.form-label-group > label {
  position: absolute;
  top: 0;
  left: 0;
  display: block;
  width: 100%;
  margin-bottom: 0; /* Override default `<label>` margin */
  line-height: 1.5;
  color: #495057;
  pointer-events: none;
  cursor: text; /* Match the input under the label */
  border: 1px solid transparent;
  border-radius: .25rem;
  transition: all .1s ease-in-out;
}

.form-label-group input::-webkit-input-placeholder {
  color: transparent;
}

.form-label-group input:-ms-input-placeholder {
  color: transparent;
}

.form-label-group input::-ms-input-placeholder {
  color: transparent;
}

.form-label-group input::-moz-placeholder {
  color: transparent;
}

.form-label-group input::placeholder {
  color: transparent;
}

.form-label-group input:not(:placeholder-shown) {
  padding-top: 1.25rem;
  padding-bottom: .25rem;
}

.form-label-group input:not(:placeholder-shown) ~ label {
  padding-top: .25rem;
  padding-bottom: .25rem;
  font-size: 12px;
  color: #777;
}

/* Fallback for Edge
-------------------------------------------------- */
@supports (-ms-ime-align: auto) {
  .form-label-group > label {
    display: none;
  }
  .form-label-group input::-ms-input-placeholder {
    color: #777;
  }
}

/* Fallback for IE
-------------------------------------------------- */
@media all and (-ms-high-contrast: none), (-ms-high-contrast: active) {
  .form-label-group > label {
    display: none;
  }
  .form-label-group input:-ms-input-placeholder {
    color: #777;
  }
}
</style>

'); 
/* end style css */

if ($debug_mode)
{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

if (preg_match("/^[a-z0-9]{32}$/i", $_GET['q']))
{
    header("Location: ?pid=load&id=".$_GET['q']);exit;
}

header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache"); 
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: max-age=2592000");

/************************************

 All functions start 
 - contains html code

************************************/

function html_error ($error)
{
        return die($error);
}

function html_header ($title='', $refresh='', $html='')
{
      if ($html == '')
      {   

         return '<!doctype html>
                 <html lang="en">
                 <head>
                 <meta charset="utf-8">
                 '.$refresh.'
                 <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
                 <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
                 '.STYLE_CSS.'
                 <title>'.$title.'</title>
                 <link rel="apple-touch-icon" sizes="180x180" href="https://grlc.eu/garlicoin.png">
	         <link rel="icon" type="image/png" sizes="32x32" href="https://grlc.eu/garlicoin.png">
	         <link rel="icon" type="image/png" sizes="16x16" href="https://grlc.eu/garlicoin.png">
                 <script>function copyText(a){document.getElementById(a).onclick = function() {this.select();document.execCommand(\'copy\');}}</script>
                 </head><body>';
      }
}

function html_footer ($html='')
{
      if ($html == '')
      {   
         return '<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
                 <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
                 <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
                 </body>
                 </html>';
      }
}

function html_form ($error='') 
{
  global $made_in_grlc;
  return '<form class="form-pay" method="post">
  <div class="text-center mb-4">
    <a href="?start"><img class="mb-4" src="https://grlc.eu/garlicoin.png" alt="" width="72" height="72"></a>
    <h1 class="h3 mb-3 font-weight-normal">Generating grlc web payments</h1>
    <p>Create payment. After payment, the user automatically receives the access code to your service.</p>
  </div>

  <div class="form-label-group">
    <input type="text" id="addr" name="addr" class="form-control'.(($error['addr'] OR $error['balance']) ? " is-invalid" : "").'" placeholder="Your fresh grlc address" required autofocus>
    <label for="addr">Your fresh grlc address</label>
    <div class="invalid-feedback">
        This must be a new grlc address that has not been used. Generate a new address in your cold wallet and enter it here.
    </div>
  </div>

  <div class="form-label-group">
    <input type="email" id="inputEmail" name="email" class="form-control" placeholder="Your email optional" autofocus>
    <label for="inputEmail">Your email optional</label>
  </div> 

  <div class="form-label-group">
    <input type="number" step="0.01" id="amount" name="amount" class="form-control'.(($error['amount']) ? " is-invalid" : "").'" placeholder="Amount" required autofocus>
    <label for="amount">Amount, price in grlc</label>
    <div class="invalid-feedback">
        This field is required
    </div>
  </div>

  <div class="form-label-group">
    <input type="text" id="code" name="code" class="form-control'.(($error['code']) ? " is-invalid" : "").'" placeholder="Access code, displayed after purchase" required autofocus>
    <div class="invalid-feedback">
        This field is required
    </div>
    <label for="code">Access code or etc, displayed after purchase</label>
  </div> 
  <input type="hidden" name="pid" value="add" />
  <button class="btn btn-lg btn-primary btn-block" type="submit">Generate Link &#x2192</button>
  '.$made_in_grlc.'
  </form>';
}

function html_load_link ($link)
{
   global $made_in_grlc;
   return '<form class="form-pay"><div class="text-center mb-4">'.
          '<div class="text-center mb-4">'.
          '<a href="?start"><img class="mb-4" src="https://grlc.eu/garlicoin.png" alt="" width="72" height="72"></a>'.
          '<h1 class="h3 mb-3 font-weight-normal">Your new grlc payment link</h1>'.
          '<p></p>'.
          '</div>'. 
          '<img class="mb-4" src="https://grlc.eu/qr.php?code='.$link.'" alt=""><br><textarea type="text" onclick="copyText(\'link\')" id="link" class="form-control">'.$link.'</textarea>'.
          '<p><a href="'.$domain_name.'">Go back</a></p>'.
          '</div>'.
          $made_in_grlc.
          '</form>';
}

function html_load_error ($html)
{
   global $domain_name, $made_in_grlc;
   return '<form class="form-pay"><div class="text-center mb-4">'.
          '<div class="text-center mb-4">'.
          '<a href="?start"><img class="mb-4" src="https://grlc.eu/garlicoin.png" alt="" width="72" height="72"></a>'.
          '<h1 class="h3 mb-3 font-weight-normal">'.$html.'</h1>'.
          '<p><a href="'.$domain_name.'">Go back</a></p>'.
          '</div>'. 
          '</div>'.
          $made_in_grlc.
          '</form>';
}

function html_load_pay ($amount, $addr)
{
   global $made_in_grlc;
   return '<form class="form-pay"><div class="text-center mb-4">'.
          '<div class="text-center mb-4">'.
          '<a href="?start"><img class="mb-4" src="https://grlc.eu/garlicoin.png" alt="" width="72" height="72"></a>'.
          '<h1 class="h3 mb-3 font-weight-normal">Grlc payment address</h1>'.
          '</div>'. 
          '<img class="mb-4" src="https://grlc.eu/qr.php?code='.$addr.'" alt=""><br><textarea type="text" onclick="copyText(\'link\')" id="link" class="form-control">'.$addr.'</textarea>'.
          '<p>Payment amount: '.$amount.' GRLC</p>'.
          '<p>Status: waiting for payment</p>'.
          '<p><code>Do not close this page until payment is confirmed!</code></p>'.
          '</div>'.
          $made_in_grlc.
          '</form>';
}

function html_pay_ok ($code)
{
   global $made_in_grlc;

   /********************************************* 

      If the secret code is the URL, redirect it to the address provided.  
      for example https://your_domain/pay?code=secret_code 

   *********************************************/
   
   if (filter_var($code, FILTER_VALIDATE_URL) !== false)
   {
       $code_print = 'Loading...<script>document.location.href="'.$code.'";</script>';
   }
    else
   {
       $code_print = $code;
   }

   return '<form class="form-pay"><div class="text-center mb-4">'.
          '<div class="text-center mb-4">'.
          '<a href="?start"><img class="mb-4" src="https://grlc.eu/garlicoin.png" alt="" width="72" height="72"></a>'.
          '<h1 class="h3 mb-3 font-weight-normal">Payment completed!</h1>'.
          '<p>Your secret code: '.$code_print.'</p>'.
          '</div>'. 
          'Thank you very much'.
          '</div>'.
          $made_in_grlc.
          '</form>';
}

function html_api_code ()
{
   global $made_in_grlc, $domain_name;
   return '<form class="form-pay"><div class="text-center mb-4">'.
          '<div class="text-center mb-4">'.
          '<a href="?start"><img class="mb-4" src="https://grlc.eu/garlicoin.png" alt="" width="72" height="72"></a>'.
          '<h1 class="h3 mb-3 font-weight-normal">Grlc payment address API</h1>'.
          '</div>'.
          '<p>Request GET: '.$domain_name.'?pid=api_get&amp;amount={price}&amp;addr={grlc_address}&amp;email={your_emial_optional}&amp;code={content_displayed_after_purchase}</p>'.
          '<p><code>response json {link_id => "HASZLINK"} or json {error => 1} if error</code></p>'.
          '</div>'.
          $made_in_grlc.
          '</form>';
}

function link_encrypt ($data, $key, $exp="86400", $crypt="aes-256-cbc")
{
        /* create vector encrypt */
        $iv = openssl_random_pseudo_bytes(16);
        $iv2 = openssl_random_pseudo_bytes(16);
        $iv = substr($iv, 0, 16);
        $hasz = substr($iv2, 0, 16);
        /* create maxlife time */
        $time = time()+$exp;
        /* encrypt metadata */
        $data = $time.$data; 
        $encrypted = openssl_encrypt($data, $crypt, $key, 0, $iv);
        $encrypted = base64_encode(base64_encode(gzcompress($hasz.$iv.$encrypted, 9)));
        if ($encrypted != '') {return $encrypted;} else {return false;}
}

function link_decrypt ($data, $key, $crypt="aes-256-cbc")
{
        $encrypted = gzuncompress(base64_decode(base64_decode(urldecode(trim($data)))));
        /* data encrypt */
        $parts['0'] = substr($encrypted, 16+16, strlen($encrypted));
        /* vector encrypt */  
        $parts['1'] = substr($encrypted, 16+0, 16);
        /* metadata decrypt */
        $decrypted = openssl_decrypt($parts[0], $crypt, $key, 0, $parts[1]);
        /* time decrypt */
        $array['time'] = substr($decrypted, 0, 10);
        /* data decrypt */
        $array['data'] = substr($decrypted, 10, strlen($decrypted));
        if ($array['data'] != '') {return $array;} else {return false;}
}

function load_var_decrypt ($array_url)
{
    if (trim($array_url) != '')
    {
        $array_url = explode("&", $array_url);
        if (@count($array_url) > 0) 
        {
            foreach($array_url as $v_crypt)
            {
               $get_v = @explode("=", $v_crypt);
               if (trim($get_v[0]) == '') {continue;} 
               $C_GET[$get_v[0]] = trim($get_v[1]); 
            }
        }
    } return $C_GET;
}

function explorers_get ($addr)
{
    /* default explorers */	
    $url_explorer['0'] = "https://insight.garli.co.in/insight-grlc-api/addr/".$addr."/?noTxList=1";
    $url_explorer['1'] = "https://api.freshgrlc.net/blockchain/grlc/address/".$addr."/";
    /* reserve explorer	(uncomment to enable)
    $url_explorer['0'] = "https://explorer.grlc.eu/addr.php?&api=1&op=balance&a=".$addr;
    */
    $explorer['0'] = json_decode(@file_get_contents($url_explorer['0']), 1);
    $explorer['1'] = json_decode(@file_get_contents($url_explorer['1']), 1);
    return $explorer;
}

function check_addr_balance ($addr, $explorer, $amount=0, $option=1)
{
    if ($addr == '') {return false;}
    $check['0'] = $explorer['0']['balance'];
    $check['1'] = $explorer['1']['balance'];
    switch ($option)
    {
      case "1":
       return (($check['0'] >= $amount OR $check['1'] >= $amount) AND $amount != 0) ? true : false;
      break;
      case "2":
       return ($check['0'] == $amount OR $check['1'] == $amount) ? true : false;
      break;
      default: return false;
    }    
}

function pear_mail ($subject, $body, $to, $from, $host, $port, $auth, $user, $pass)
{
    require_once "Mail.php";
    if (trim($to) == '' OR trim($subject) == '' OR trim($body) == '' OR trim($from) == '') {return false;}

    $headers = array('From' => $from, 'To' => $to, 'Subject' => $subject);

    $smtp = Mail::factory('smtp', array(
        'host' => $host,
        'port' => $port,
        'auth' => true,
        'username' => $user,
        'password' => $pass
    ));

    $mail = $smtp->send($to, $headers, $body);

    if (PEAR::isError($mail)) 
    {
        return false;
    } 
     else 
    {
        return true; 
    }
}

/************************************

 All functions end 

************************************/


/************************************

 Control start 

************************************/

switch ($_REQUEST['pid'])
{

   case "add":

    if (!is_dir($data_dir)) {mkdir($data_dir, 0777);}
		
    $_POST['amount'] = str_replace(",", ".", $_POST['amount']);
    $amount = (preg_match("/^([0-9\.]{1,100})$/i", trim($_POST['amount']))) ? $_POST['amount'] : '';
    $addr = (preg_match("/^([a-zA-Z0-9]{30,100})$/i", trim($_POST['addr']))) ? $_POST['addr'] : '';
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $code = filter_var(trim($_POST['code']), FILTER_SANITIZE_STRING); 

    $explorer = explorers_get($addr);

    $check_addr = (check_addr_balance($addr, $explorer, 0, 2)) ? 1 : 0;

    $error['balance'] = !$check_addr; 
    $error['addr'] = ($addr == '') ? 1 : 0;
    $error['amount'] = ($amount == '') ? 1 : 0; 
    $error['code'] = ($code == '') ? 1 : 0; 

    if ($amount == '' OR $addr == '' OR !$check_addr OR $code == '') {echo html_header('Fill out all fields correctly').html_form($error).html_footer();exit;}

    $aurl_hasz = 'a='.$amount.'&addr='.$addr.'&mail='.$email.'&code='.$code;
    $encrypt_link = link_encrypt($aurl_hasz, $encryption_key, $link_validity_in_seconds);

    $uniqid = md5(uniqid('', true).microtime());

    if ($f = fopen($data_dir."/".$uniqid, wb))
    {
        fwrite($f, $encrypt_link);
        fclose($f);
        echo html_header('Payment link created');
        echo html_load_link($domain_name.'?q='.$uniqid);
        echo html_footer(); 
    }
     else
    {
        html_error('Error write metafile (create dir "'.$data_dir.'" and set write permissions for the data directory to 777)'); 
    }

   break;

   case "api_get":

    if (!is_dir($data_dir)) {mkdir($data_dir, 0777);}

    $amount = (preg_match("/^([0-9\.]{1,100})$/i", trim($_GET['amount']))) ? $_GET['amount'] : '';
    $addr = (preg_match("/^([a-zA-Z0-9]{30,100})$/i", trim($_GET['addr']))) ? $_GET['addr'] : '';
    $email = filter_var(trim($_GET['email']), FILTER_SANITIZE_EMAIL);
    $code = filter_var(trim($_GET['code']), FILTER_SANITIZE_STRING); 

    $explorer = explorers_get($addr);

    $check_addr = (check_addr_balance($addr, $explorer, 0, 2)) ? 1 : 0;

    $error['balance'] = !$check_addr; 
    $error['addr'] = ($addr == '') ? 1 : 0;
    $error['amount'] = ($amount == '') ? 1 : 0; 
    $error['code'] = ($code == '') ? 1 : 0; 

    if ($amount == '' OR $addr == '' OR !$check_addr OR $code == '') {$json['error'] = 1; echo json_encode($json); exit;}

    $aurl_hasz = 'a='.$amount.'&addr='.$addr.'&mail='.$email.'&code='.$code;
    $encrypt_link = link_encrypt($aurl_hasz, $encryption_key, $link_validity_in_seconds);

    $uniqid = md5(uniqid('', true).microtime());

    if ($f = fopen($data_dir."/".$uniqid, wb))
    {
        fwrite($f, $encrypt_link);
        fclose($f);
        $json['link_id'] = $domain_name.'?q='.$uniqid; echo str_replace(array("\/"), array("/"), json_encode($json)); exit;
    }
     else
    {
        $json['error'] = 'set write permissions for the data directory to 777'; echo json_encode($json); exit;
    }

   break;

   case "load":

    $encrypt_link = (preg_match("/^[a-z0-9]{32}$/i", $_GET['id'])) ? $_GET['id'] : 'error'; 
    $decrypt_link = link_decrypt(trim(file_get_contents($data_dir."/".$encrypt_link)), $encryption_key);
    $array_url = $decrypt_link['data'];
    $get_var = load_var_decrypt($array_url);

    if ($decrypt_link['time'] > time())
    { 
        $explorer = explorers_get($get_var['addr']);
        if (check_addr_balance($get_var['addr'], $explorer, $get_var['a']))
        {
            echo html_header('Payment confirmed!');
            echo html_pay_ok($get_var['code']);
            if ($enable_mail)
            {
                $get_var['mail'] = ($get_var['mail'] != '') ? $get_var['mail'] : 'anonym';  
                if ($get_var['mail'] != 'anonym')
                {
                    pear_mail ('Payment confirmed!', "Hello\r\nNew payment has been received to the address: ".$get_var['addr']."\r\nAmount: ".$get_var['a']."\r\nGreetings", $get_var['mail'], $your_mail_name, $host_smtp, $port_smtp, $auth, $user, $pass);
                }
            }
            unlink($data_dir."/".$encrypt_link);
        }
         else
        {
            echo html_header('Waiting for payment...', '<meta http-equiv="refresh" content="40">');
            echo html_load_pay($get_var['a'], $get_var['addr']);
        }
    }
     else
    {
        echo html_header('The link has expired :-(');
        echo html_load_error('The link has expired :-(');
    }
    echo html_footer(); 
 
   break;

   case "api_code":

    echo html_header('Api code').html_api_code().html_footer();

   break;

   default:
    echo html_header('Generating grlc web payments').html_form().html_footer();
   break;

}


/************************************

 Control end 

************************************/

?>
