<?php

/*
*
* This is a simple script to handle web payments in grlc
* Does not require sql database
* PHP 5.6+ / PHP 7.x / PHP 8.x + optional PEAR Mail
*
* Payment metadata is encrypted at rest. Keep the encryption key private.
* Generating payments is only possible when using a cold wallet address.
* Demo https://grlc.eu/pay
*
* Written by: tomiiiii
* Contact: t0mi[:-)]protonmail.com
* Website: https://grlc.eu/pay
* Original date: 2019-09-02
* Updated: 2026-09-24
* Version: 2.0
* License: Lesser General Public License (LGPL)   
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

/* Private encryption key lives in config.php, which is not committed to Git. */
$config_file = __DIR__ . '/config.php';
$encryption_key = '';

if (is_file($config_file))
{
    require $config_file;
}

$encryption_key = (isset($encryption_key) && is_string($encryption_key)) ? trim($encryption_key) : '';
$encryption_key_is_configured = (strlen($encryption_key) >= 32);

$data_dir = "./data"; /* keep this directory non-public and writable by the PHP user */
$debug_mode = false;
$domain_name = 'index.php'; /* https://domain/path.file where the script will run */
$link_validity_in_seconds =  3600*24*7; /* 7 day */

/*********************************
* Mail options
*
* Optional email notifications use PEAR Mail.
* Install the Mail, Net_SMTP and Auth_SASL packages required by
* your SMTP provider before enabling this feature.
*
* Use credentials and authentication supported by your provider.
*********************************/

/* Turn email sending on or off */

$enable_mail = false; /* true = on | false = off | default = false */
$your_mail_name = ''; /* sender email address */
$host_smtp = 'ssl://smtp.gmail.com'; 
$port_smtp = '465'; 
$auth_smtp = true;
$user = ''; /* SMTP username */
$pass = ''; /* SMTP password */

/************************************
* mail options end
*************************************/

$made_in_grlc = '<footer class="site-footer">'.
                '<span>GRLC Pay</span>'.
                '<span class="footer-dot">·</span>'.
                '<a href="?pid=api_code">API</a>'.
                '<span class="footer-dot">·</span>'.
                '<a href="https://github.com/tomiiiiiiiiiiiiii/grlcpay" target="_blank" rel="noopener">GitHub</a>'.
                '<span class="footer-year">2018-'.date("Y").'</span>'.
                '</footer>';

/************************************
 Config end
************************************/

/* start style css */
define("STYLE_CSS",'
<style>
* {
  box-sizing: border-box;
}

html {
  min-height: 100%;
  background: #f6f7f3;
}

body {
  min-height: 100vh;
  margin: 0;
  padding: 32px 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    radial-gradient(circle at top, rgba(123, 150, 74, 0.10), transparent 34rem),
    #f6f7f3;
  color: #1c2119;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
}

a {
  color: #526d2b;
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}

.app-shell {
  width: 100%;
  max-width: 560px;
  margin: auto;
}

.card {
  width: 100%;
  padding: 32px;
  background: rgba(255,255,255,0.96);
  border: 1px solid #e7e9e2;
  border-radius: 22px;
  box-shadow: 0 20px 60px rgba(27, 36, 20, 0.08);
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 30px;
}

.brand-mark {
  width: 52px;
  height: 52px;
  padding: 5px;
  flex: 0 0 auto;
  border: 1px solid #e3e7dc;
  border-radius: 16px;
  background: linear-gradient(145deg, #ffffff, #f3f6ed);
  box-shadow: 0 8px 24px rgba(53, 69, 38, 0.09);
}

.brand-logo {
  width: 100%;
  height: 100%;
  display: block;
  border-radius: 11px;
}

.brand-copy {
  min-width: 0;
}

.brand-name {
  margin: 0;
  font-size: 18px;
  line-height: 1.1;
  font-weight: 780;
  letter-spacing: -0.025em;
}

.brand-subtitle {
  margin: 4px 0 0;
  color: #777d71;
  font-size: 13px;
}

.brand-badge {
  margin-left: auto;
  padding: 7px 10px;
  border: 1px solid #e3e8dc;
  border-radius: 999px;
  background: #f7f9f3;
  color: #667356;
  font-size: 11px;
  font-weight: 750;
  white-space: nowrap;
}

.hero {
  margin-bottom: 24px;
}

.trust-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: -4px 0 26px;
}

.trust-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 10px;
  border: 1px solid #e7eae2;
  border-radius: 999px;
  background: #fafbf8;
  color: #71786c;
  font-size: 11px;
  font-weight: 700;
}

.trust-icon {
  color: #6f8b43;
  font-size: 11px;
}

.form-panel {
  padding: 20px;
  border: 1px solid #e8eae4;
  border-radius: 17px;
  background: linear-gradient(180deg, #fbfcfa 0%, #f8faf6 100%);
}

.section-title {
  margin: 0 0 4px;
  color: #242a21;
  font-size: 14px;
  font-weight: 800;
}

.section-description {
  margin: 0 0 18px;
  color: #858b80;
  font-size: 12px;
  line-height: 1.45;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0 14px;
}

.form-group.full {
  grid-column: 1 / -1;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 10px;
  color: #5e733a;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.eyebrow-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #7c9a4d;
  box-shadow: 0 0 0 5px rgba(124,154,77,0.11);
}

h1 {
  margin: 0;
  color: #171b15;
  font-size: 30px;
  line-height: 1.12;
  letter-spacing: -0.035em;
}

.lead {
  margin: 12px 0 0;
  color: #6e7469;
  font-size: 15px;
  line-height: 1.6;
}

.form-group {
  margin-bottom: 18px;
}

.form-label {
  display: block;
  margin-bottom: 7px;
  color: #343a30;
  font-size: 13px;
  font-weight: 700;
}

.form-hint {
  margin: 6px 0 0;
  color: #8a9084;
  font-size: 12px;
  line-height: 1.45;
}

.input,
.textarea {
  width: 100%;
  border: 1px solid #d9ddd3;
  border-radius: 12px;
  background: #fbfcfa;
  color: #1c2119;
  font: inherit;
  outline: none;
  transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}

.input {
  height: 48px;
  padding: 0 14px;
}

.textarea {
  min-height: 82px;
  padding: 12px 14px;
  resize: vertical;
  line-height: 1.45;
}

.input:focus,
.textarea:focus {
  border-color: #819b59;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(129,155,89,.14);
}

.input.invalid {
  border-color: #c75d55;
  background: #fffafa;
}

.error-text {
  margin: 7px 0 0;
  color: #a74740;
  font-size: 12px;
  line-height: 1.4;
}

.button {
  width: 100%;
  height: 50px;
  margin-top: 4px;
  border: 0;
  border-radius: 13px;
  background: #5f7d34;
  color: #ffffff;
  font: inherit;
  font-size: 15px;
  font-weight: 750;
  cursor: pointer;
  box-shadow: 0 8px 22px rgba(95,125,52,.22);
  transition: transform .12s ease, background .12s ease, box-shadow .12s ease;
}

.button:hover {
  background: #536f2e;
  box-shadow: 0 10px 26px rgba(95,125,52,.27);
}

.button:active {
  transform: translateY(1px);
}

.status {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 18px;
  padding: 7px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
}

.status.waiting {
  background: #fff7dc;
  color: #816515;
}

.status.success {
  background: #e9f6e5;
  color: #3f6e31;
}

.status.error {
  background: #fdebea;
  color: #994942;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: currentColor;
}

.payment-status-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
}

.payment-status-row .status {
  margin: 0;
}

.status-check {
  padding: 7px 10px;
  border: 1px solid #e1e5da;
  border-radius: 999px;
  background: #f8faf5;
  color: #657252;
  font: inherit;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
}

.status-check:hover {
  background: #f0f4ea;
}

.expiry-card {
  margin: 0 0 22px;
  padding: 13px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  border: 1px solid #e6e9e1;
  border-radius: 13px;
  background: #fafbf8;
}

.expiry-copy {
  min-width: 0;
}

.expiry-label {
  margin: 0 0 3px;
  color: #858b80;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: .07em;
  text-transform: uppercase;
}

.expiry-time {
  margin: 0;
  color: #3d4438;
  font-size: 12px;
  line-height: 1.45;
}

.expiry-countdown {
  flex: 0 0 auto;
  color: #596b3d;
  font-size: 13px;
  font-weight: 800;
  white-space: nowrap;
}

.qr-wrap {
  display: flex;
  justify-content: center;
  margin: 24px 0;
}

.qr-card {
  width: 100%;
  max-width: 290px;
  padding: 15px 15px 13px;
  border: 1px solid #e2e6dd;
  border-radius: 22px;
  background: #ffffff;
  box-shadow: 0 12px 34px rgba(35, 45, 28, 0.07);
  text-align: center;
}

.qr-label {
  margin: 0 0 11px;
  color: #777e70;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.qr-frame {
  padding: 12px;
  border: 1px solid #edf0e9;
  border-radius: 16px;
  background: #fbfcfa;
}

.qr {
  width: min(210px, 62vw);
  height: auto;
  display: block;
  margin: auto;
  background: #ffffff;
  border-radius: 10px;
}

.qr-caption {
  margin: 11px 0 0;
  color: #8a9084;
  font-size: 11px;
  line-height: 1.4;
}

.data-box {
  margin-top: 18px;
  padding: 14px;
  border: 1px solid #e4e7df;
  border-radius: 14px;
  background: #f8faf6;
}

.data-label {
  margin: 0 0 7px;
  color: #777d71;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .06em;
  text-transform: uppercase;
}

.data-value {
  margin: 0;
  color: #1e241b;
  font-size: 15px;
  font-weight: 750;
  word-break: break-word;
}

.copy-field {
  margin-top: 18px;
}

.copy-control {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 9px;
  align-items: stretch;
}

.copy-control .textarea {
  min-height: 62px;
  resize: none;
}

.copy-button {
  min-width: 84px;
  padding: 0 15px;
  border: 1px solid #dce2d4;
  border-radius: 12px;
  background: #f6f9f2;
  color: #526b34;
  font: inherit;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.copy-button:hover {
  background: #eef4e8;
}

.copy-note {
  margin: 7px 0 0;
  color: #8a9084;
  font-size: 12px;
}

.amount-display {
  margin: 4px 0 0;
  color: #20261d;
  font-size: 34px;
  line-height: 1.05;
  font-weight: 820;
  letter-spacing: -0.04em;
}

.amount-unit {
  margin-left: 5px;
  color: #78816e;
  font-size: 17px;
  font-weight: 750;
  letter-spacing: -0.01em;
}

.divider {
  height: 1px;
  margin: 24px 0;
  background: #eceee9;
}

.notice {
  padding: 14px 15px;
  border-radius: 13px;
  background: #f7f8f4;
  color: #676d62;
  font-size: 13px;
  line-height: 1.55;
}

.secret {
  margin: 22px 0 8px;
  padding: 18px;
  border: 1px solid #dfe7d7;
  border-radius: 14px;
  background: #f4f8f0;
  color: #27361f;
  font-size: 16px;
  font-weight: 750;
  line-height: 1.5;
  word-break: break-word;
}

.back-link {
  display: inline-block;
  margin-top: 22px;
  font-size: 13px;
  font-weight: 700;
}

.code-block {
  margin-top: 18px;
  padding: 14px 15px;
  overflow-x: auto;
  border: 1px solid #e1e4dc;
  border-radius: 13px;
  background: #20241d;
  color: #eef3e9;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 12px;
  line-height: 1.55;
  white-space: pre-wrap;
}

.site-footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 7px;
  margin-top: 18px;
  color: #8b9185;
  font-size: 12px;
}

.site-footer a {
  color: #68735c;
}

.footer-dot {
  opacity: .45;
}

.footer-year {
  width: 100%;
  margin-top: 1px;
  text-align: center;
  opacity: .72;
}

@media (max-width: 620px) {
  body {
    padding: 18px 12px;
    align-items: flex-start;
  }

  .card {
    padding: 24px 20px;
    border-radius: 18px;
  }

  h1 {
    font-size: 27px;
  }

  .brand {
    margin-bottom: 24px;
  }

  .brand-badge {
    display: none;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-group.full {
    grid-column: auto;
  }

  .copy-control {
    grid-template-columns: 1fr;
  }

  .copy-button {
    min-height: 44px;
  }

  .amount-display {
    font-size: 31px;
  }
}
</style>
');
/* end style css */

function h ($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function secure_random_bytes ($length)
{
    $length = (int)$length;
    if ($length < 1) { return false; }

    if (function_exists('random_bytes'))
    {
        try
        {
            return random_bytes($length);
        }
        catch (Exception $e)
        {
            /* Fall through to OpenSSL for PHP versions/environments where
               random_bytes() is unavailable at runtime. */
        }
    }

    if (!function_exists('openssl_random_pseudo_bytes'))
    {
        return false;
    }

    $strong = false;
    $bytes = openssl_random_pseudo_bytes($length, $strong);

    if ($bytes === false || !$strong || strlen($bytes) !== $length)
    {
        return false;
    }

    return $bytes;
}

function secure_random_id ()
{
    $bytes = secure_random_bytes(16);
    return ($bytes !== false) ? bin2hex($bytes) : false;
}
function ensure_data_dir ($data_dir)
{
    if (!is_dir($data_dir))
    {
        if (!mkdir($data_dir, 0700, true) && !is_dir($data_dir))
        {
            return false;
        }
    }

    return is_writable($data_dir);
}

if ($debug_mode)
{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

$q = (isset($_GET['q']) && is_string($_GET['q'])) ? $_GET['q'] : '';
if (preg_match("/^[a-z0-9]{32}$/i", $q))
{
    header("Location: ?pid=load&id=".$q);
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");

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
                 <meta name="viewport" content="width=device-width, initial-scale=1">
                 <meta name="theme-color" content="#f6f7f3">
                 '.STYLE_CSS.'
                 <title>'.h($title).'</title>
                 <link rel="apple-touch-icon" sizes="180x180" href="https://grlc.eu/garlicoin.png">
                 <link rel="icon" type="image/png" sizes="32x32" href="https://grlc.eu/garlicoin.png">
                 <link rel="icon" type="image/png" sizes="16x16" href="https://grlc.eu/garlicoin.png">
                 <script>
                 function copyText(id, buttonId){
                   var el=document.getElementById(id);
                   if(!el){return;}
                   el.select();
                   var done=function(){
                     if(!buttonId){return;}
                     var btn=document.getElementById(buttonId);
                     if(!btn){return;}
                     var old=btn.innerHTML;
                     btn.innerHTML="Copied";
                     window.setTimeout(function(){btn.innerHTML=old;},1200);
                   };
                   if(navigator.clipboard && window.isSecureContext){
                     navigator.clipboard.writeText(el.value).then(done, done);
                   } else {
                     document.execCommand("copy");
                     done();
                   }
                 }
                 </script>
                 </head><body>';
      }
}

function html_footer ($html='')
{
      if ($html == '')
      {
         return '</body></html>';
      }
}

function html_form ($error=array())
{
  global $made_in_grlc;
  $error = is_array($error) ? $error : array();
  $error += array('addr' => 0, 'balance' => 0, 'amount' => 0, 'code' => 0);

  $addr_error = ($error['addr'] OR $error['balance']) ? true : false;
  $amount_error = $error['amount'] ? true : false;
  $code_error = $error['code'] ? true : false;

  return '<main class="app-shell">
  <section class="card">
    <div class="brand">
      <a class="brand-mark" href="?start"><img class="brand-logo" src="https://grlc.eu/garlicoin.png" alt="Garlicoin"></a>
      <div class="brand-copy">
        <p class="brand-name">GRLC Pay</p>
        <p class="brand-subtitle">Simple Garlicoin payments</p>
      </div>
      <span class="brand-badge">Open source · self-hosted</span>
    </div>

    <div class="hero">
      <div class="eyebrow"><span class="eyebrow-dot"></span>Create payment</div>
      <h1>Generate a payment link</h1>
      <p class="lead">Create a one-time GRLC checkout. The buyer pays to your address and protected content is released automatically.</p>
    </div>

    <div class="trust-row">
      <span class="trust-pill"><span class="trust-icon">✓</span>No database</span>
      <span class="trust-pill"><span class="trust-icon">✓</span>One-time access</span>
      <span class="trust-pill"><span class="trust-icon">✓</span>Self-hosted</span>
    </div>

    <form method="post" novalidate>
      <div class="form-panel">
        <p class="section-title">Payment details</p>
        <p class="section-description">A fresh address keeps each checkout isolated and easy to verify.</p>

        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label" for="addr">Fresh GRLC address</label>
            <input class="input'.($addr_error ? ' invalid' : '').'" type="text" id="addr" name="addr" placeholder="Enter a new unused address" required autofocus>
            '.($addr_error ? '<p class="error-text">Use a new, valid GRLC address with a zero balance.</p>' : '<p class="form-hint">Generate a new address in your wallet. It must not have been used before.</p>').'
          </div>

          <div class="form-group">
            <label class="form-label" for="amount">Amount in GRLC</label>
            <input class="input'.($amount_error ? ' invalid' : '').'" type="number" step="0.00000001" min="0.00000001" id="amount" name="amount" placeholder="1.00000000" required>
            '.($amount_error ? '<p class="error-text">Enter an amount greater than zero.</p>' : '').'
          </div>

          <div class="form-group">
            <label class="form-label" for="inputEmail">Email <span style="font-weight:500;color:#959b90">(optional)</span></label>
            <input class="input" type="email" id="inputEmail" name="email" placeholder="you@example.com">
          </div>

          <div class="form-group full">
            <label class="form-label" for="code">Content released after payment</label>
            <input class="input'.($code_error ? ' invalid' : '').'" type="text" id="code" name="code" placeholder="Access code or https://..." required>
            '.($code_error ? '<p class="error-text">Enter an access code or URL.</p>' : '<p class="form-hint">HTTPS URLs redirect automatically after payment.</p>').'
          </div>
        </div>
      </div>

      <input type="hidden" name="pid" value="add">
      <button class="button" type="submit">Generate payment link</button>
    </form>
  </section>
  '.$made_in_grlc.'
  </main>';
}

function html_load_link ($link)
{
   global $made_in_grlc, $domain_name;

   return '<main class="app-shell">
   <section class="card">
     <div class="brand">
       <a class="brand-mark" href="?start"><img class="brand-logo" src="https://grlc.eu/garlicoin.png" alt="Garlicoin"></a>
       <div class="brand-copy">
         <p class="brand-name">GRLC Pay</p>
         <p class="brand-subtitle">Payment link created</p>
       </div>
       <span class="brand-badge">Open source · self-hosted</span>
     </div>

     <div class="hero">
       <div class="status success"><span class="status-dot"></span>Ready</div>
       <h1>Your new GRLC payment link</h1>
       <p class="lead">Share this link with the buyer. The link remains valid until it is paid, used or expires.</p>
     </div>

     <div class="qr-wrap">
       <div class="qr-card">
         <p class="qr-label">Scan payment link</p>
         <div class="qr-frame">
           <img class="qr" src="https://grlc.eu/qr.php?code='.rawurlencode($link).'" alt="Payment link QR code">
         </div>
         <p class="qr-caption">Open the checkout instantly on another device.</p>
       </div>
     </div>

     <div class="copy-field">
       <label class="form-label" for="link">Payment link</label>
       <div class="copy-control">
         <textarea class="textarea" id="link" readonly>'.h($link).'</textarea>
         <button class="copy-button" id="copy-link" type="button" onclick="copyText(\'link\',\'copy-link\')">Copy</button>
       </div>
       <p class="copy-note">Share this private one-time checkout link with the buyer.</p>
     </div>

     <a class="back-link" href="'.h($domain_name).'">← Create another payment</a>
   </section>
   '.$made_in_grlc.'
   </main>';
}

function html_load_error ($html)
{
   global $domain_name, $made_in_grlc;

   return '<main class="app-shell">
   <section class="card">
     <div class="brand">
       <a class="brand-mark" href="?start"><img class="brand-logo" src="https://grlc.eu/garlicoin.png" alt="Garlicoin"></a>
       <div class="brand-copy">
         <p class="brand-name">GRLC Pay</p>
         <p class="brand-subtitle">Payment unavailable</p>
       </div>
       <span class="brand-badge">Open source · self-hosted</span>
     </div>

     <div class="status error"><span class="status-dot"></span>Unavailable</div>
     <div class="hero">
       <h1>'.h($html).'</h1>
       <p class="lead">The payment link may be invalid, expired or already used.</p>
     </div>

     <a class="back-link" href="'.h($domain_name).'">← Back to GRLC Pay</a>
   </section>
   '.$made_in_grlc.'
   </main>';
}

function html_load_pay ($amount, $addr, $payment_id, $expires_at)
{
   global $made_in_grlc, $domain_name;

   $expires_at = (int)$expires_at;
   $remaining_seconds = max(0, $expires_at - time());
   $status_url = $domain_name.'?pid=status&id='.rawurlencode($payment_id);

   return '<main class="app-shell">
   <section class="card">
     <div class="brand">
       <a class="brand-mark" href="?start"><img class="brand-logo" src="https://grlc.eu/garlicoin.png" alt="Garlicoin"></a>
       <div class="brand-copy">
         <p class="brand-name">GRLC Pay</p>
         <p class="brand-subtitle">Secure one-time payment</p>
       </div>
       <span class="brand-badge">Open source · self-hosted</span>
     </div>

     <div class="payment-status-row">
       <div class="status waiting" id="payment-status" aria-live="polite"><span class="status-dot"></span><span id="payment-status-text">Status: waiting for payment</span></div>
       <button class="status-check" id="check-payment-now" type="button">Check now</button>
     </div>

     <div class="expiry-card">
       <div class="expiry-copy">
         <p class="expiry-label">Payment link expires</p>
         <p class="expiry-time" id="expiry-time" data-expires-at="'.h($expires_at).'">Loading expiry time...</p>
       </div>
       <div class="expiry-countdown" id="expiry-countdown" aria-live="polite">Loading...</div>
     </div>

     <div class="hero">
       <p class="data-label">Exact amount</p>
       <p class="amount-display">'.h($amount).'<span class="amount-unit">GRLC</span></p>
       <p class="lead">Send the exact amount to the address below. This page checks the blockchain automatically.</p>
     </div>

     <div class="qr-wrap">
       <div class="qr-card">
         <p class="qr-label">Scan to pay</p>
         <div class="qr-frame">
           <img class="qr" src="https://grlc.eu/qr.php?code='.rawurlencode($addr).'" alt="GRLC address QR code">
         </div>
         <p class="qr-caption">Use your Garlicoin wallet to scan this address.</p>
       </div>
     </div>

     <div class="copy-field">
       <label class="form-label" for="link">Payment address</label>
       <div class="copy-control">
         <textarea class="textarea" id="link" readonly>'.h($addr).'</textarea>
         <button class="copy-button" id="copy-address" type="button" onclick="copyText(\'link\',\'copy-address\')">Copy</button>
       </div>
       <p class="copy-note">Payment status refreshes automatically.</p>
     </div>

     <div class="divider"></div>
     <div class="notice">Keep this page open until the payment is confirmed. The secret is released only after the required balance is detected.</div>

     <noscript><p class="form-hint">JavaScript is disabled. Reload this page manually to check the payment status.</p></noscript>

     <script>
     (function(){
       var statusUrl='.json_encode($status_url).';
       var statusText=document.getElementById("payment-status-text");
       var checkButton=document.getElementById("check-payment-now");
       var expiryTime=document.getElementById("expiry-time");
       var expiryCountdown=document.getElementById("expiry-countdown");
       var expiresAt='.json_encode($expires_at).';
       var remainingSeconds='.json_encode($remaining_seconds).';
       var timer=null;
       var countdownTimer=null;
       var checking=false;

       function schedule(delay){
         if(timer){window.clearTimeout(timer);}
         timer=window.setTimeout(checkPayment, delay);
       }

       function setText(text){
         if(statusText){statusText.textContent=text;}
       }

       function pad(value){
         value=parseInt(value,10);
         return value < 10 ? "0"+value : String(value);
       }

       function formatRemaining(seconds){
         seconds=Math.max(0, parseInt(seconds,10) || 0);
         var days=Math.floor(seconds/86400);
         seconds=seconds%86400;
         var hours=Math.floor(seconds/3600);
         seconds=seconds%3600;
         var minutes=Math.floor(seconds/60);
         var secs=seconds%60;

         if(days > 0){
           return days+"d "+pad(hours)+":"+pad(minutes)+":"+pad(secs);
         }
         return pad(hours)+":"+pad(minutes)+":"+pad(secs);
       }

       function renderExpiry(){
         if(expiryTime && expiresAt){
           var date=new Date(expiresAt*1000);
           expiryTime.textContent=date.toLocaleString();
         }

         if(expiryCountdown){
           expiryCountdown.textContent=remainingSeconds > 0
             ? "Remaining: "+formatRemaining(remainingSeconds)
             : "Expired";
         }
       }

       function startCountdown(){
         renderExpiry();
         if(countdownTimer){window.clearInterval(countdownTimer);}
         countdownTimer=window.setInterval(function(){
           if(remainingSeconds > 0){
             remainingSeconds--;
             renderExpiry();
           } else {
             renderExpiry();
             window.clearInterval(countdownTimer);
             checkPayment();
           }
         },1000);
       }

       function checkPayment(){
         if(checking){return;}

         if(typeof window.fetch !== "function"){
           setText("Status: automatic checks unavailable");
           if(checkButton){
             checkButton.textContent="Reload";
             checkButton.onclick=function(){window.location.reload();};
           }
           return;
         }

         checking=true;
         setText("Status: checking blockchain...");
         if(checkButton){checkButton.disabled=true;}

         fetch(statusUrl, {
           method: "GET",
           cache: "no-store",
           credentials: "same-origin",
           headers: {"Accept": "application/json"}
         })
         .then(function(response){
           if(!response.ok){throw new Error("status_request_failed");}
           return response.json();
         })
         .then(function(data){
           checking=false;
           if(checkButton){checkButton.disabled=false;}

           if(data && typeof data.expires_at !== "undefined"){
             expiresAt=parseInt(data.expires_at,10) || expiresAt;
           }
           if(data && typeof data.remaining_seconds !== "undefined"){
             remainingSeconds=Math.max(0, parseInt(data.remaining_seconds,10) || 0);
             renderExpiry();
           }

           if(data && (data.status === "paid" || data.status === "expired" || data.status === "gone")){
             setText(data.status === "paid" ? "Status: payment detected" : "Status: updating...");
             window.location.reload();
             return;
           }

           setText("Status: waiting for payment");
           schedule(8000);
         })
         .catch(function(){
           checking=false;
           if(checkButton){checkButton.disabled=false;}
           setText("Status: waiting for payment");
           schedule(12000);
         });
       }

       if(checkButton){
         checkButton.addEventListener("click", function(){
           if(timer){window.clearTimeout(timer);}
           checkPayment();
         });
       }

       startCountdown();
       schedule(2500);
     }());
     </script>
   </section>
   '.$made_in_grlc.'
   </main>';
}

function html_pay_ok ($code)
{
   global $made_in_grlc;

   $validated_url = filter_var($code, FILTER_VALIDATE_URL);
   $scheme = ($validated_url !== false) ? strtolower((string)parse_url($code, PHP_URL_SCHEME)) : '';

   if ($validated_url !== false && in_array($scheme, array('http', 'https'), true))
   {
       $code_print = 'Redirecting...<script>window.location.href='.json_encode($code, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).';</script>';
   }
   else
   {
       $code_print = h($code);
   }

   return '<main class="app-shell">
   <section class="card">
     <div class="brand">
       <a class="brand-mark" href="?start"><img class="brand-logo" src="https://grlc.eu/garlicoin.png" alt="Garlicoin"></a>
       <div class="brand-copy">
         <p class="brand-name">GRLC Pay</p>
         <p class="brand-subtitle">Payment confirmed</p>
       </div>
       <span class="brand-badge">Open source · self-hosted</span>
     </div>

     <div class="status success"><span class="status-dot"></span>Paid</div>

     <div class="hero">
       <h1>Payment completed!</h1>
       <p class="lead">The payment has been confirmed. Your protected content is available below.</p>
     </div>

     <div class="secret">'.$code_print.'</div>
     <p class="form-hint">This payment link is one-time use and is now consumed.</p>
   </section>
   '.$made_in_grlc.'
   </main>';
}

function html_api_code ()
{
   global $made_in_grlc, $domain_name;

   return '<main class="app-shell">
   <section class="card">
     <div class="brand">
       <a class="brand-mark" href="?start"><img class="brand-logo" src="https://grlc.eu/garlicoin.png" alt="Garlicoin"></a>
       <div class="brand-copy">
         <p class="brand-name">GRLC Pay</p>
         <p class="brand-subtitle">Developer API</p>
       </div>
       <span class="brand-badge">Open source · self-hosted</span>
     </div>

     <div class="hero">
       <div class="eyebrow"><span class="eyebrow-dot"></span>API</div>
       <h1>Create payments programmatically</h1>
       <p class="lead">Use POST for new integrations so protected content is sent in the request body instead of the URL.</p>
     </div>

     <div class="code-block">POST '.h($domain_name).'
pid=api_create
amount=1.25
addr=YOUR_FRESH_GRLC_ADDRESS
email=optional@example.com
code=CONTENT_OR_HTTPS_URL</div>

     <div class="notice" style="margin-top:18px">Legacy <strong>pid=api_get</strong> remains available for compatibility, but GET query strings may be stored in browser, proxy or server logs.</div>

     <div class="code-block">{ "link_id": "index.php?q=..." }</div>

     <a class="back-link" href="'.h($domain_name).'">← Back to GRLC Pay</a>
   </section>
   '.$made_in_grlc.'
   </main>';
}

function base64url_encode ($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode ($data)
{
    if (!is_string($data) || !preg_match('/^[A-Za-z0-9_-]+$/', $data))
    {
        return false;
    }

    $padding = strlen($data) % 4;
    if ($padding > 0)
    {
        $data .= str_repeat('=', 4 - $padding);
    }

    return base64_decode(strtr($data, '-_', '+/'), true);
}

function link_encrypt ($data, $key, $exp="86400")
{
    if (!function_exists('openssl_encrypt') || !function_exists('hash_hmac'))
    {
        return false;
    }

    /* v3 is deliberately based on AES-256-CBC + HMAC-SHA256 so newly
       created payment files remain portable across PHP 5.6, 7.x and 8.x.
       Authentication is encrypt-then-MAC. */
    $iv = secure_random_bytes(16);
    if ($iv === false) { return false; }

    $payload = json_encode(array(
        'time' => time() + (int)$exp,
        'data' => (string)$data
    ));
    if ($payload === false) { return false; }

    $key_material = hash('sha512', (string)$key, true);
    $enc_key = substr($key_material, 0, 32);
    $mac_key = substr($key_material, 32, 32);

    $ciphertext = openssl_encrypt(
        $payload,
        'aes-256-cbc',
        $enc_key,
        OPENSSL_RAW_DATA,
        $iv
    );
    if ($ciphertext === false) { return false; }

    $version = "grlcpay:v3";
    $mac = hash_hmac('sha256', $version.$iv.$ciphertext, $mac_key, true);

    return 'v3.'.base64url_encode($iv.$mac.$ciphertext);
}

function link_decrypt_v3 ($data, $key)
{
    $blob = base64url_decode(substr($data, 3));
    if ($blob === false || strlen($blob) <= 48)
    {
        return false;
    }

    $iv = substr($blob, 0, 16);
    $mac = substr($blob, 16, 32);
    $ciphertext = substr($blob, 48);

    $key_material = hash('sha512', (string)$key, true);
    $enc_key = substr($key_material, 0, 32);
    $mac_key = substr($key_material, 32, 32);

    $expected_mac = hash_hmac('sha256', "grlcpay:v3".$iv.$ciphertext, $mac_key, true);
    if (!hash_equals($expected_mac, $mac))
    {
        return false;
    }

    $payload = openssl_decrypt(
        $ciphertext,
        'aes-256-cbc',
        $enc_key,
        OPENSSL_RAW_DATA,
        $iv
    );
    if ($payload === false) { return false; }

    $decoded = json_decode($payload, true);
    if (!is_array($decoded) || !isset($decoded['time'], $decoded['data']))
    {
        return false;
    }

    if (!is_numeric($decoded['time']) || !is_string($decoded['data']) || $decoded['data'] === '')
    {
        return false;
    }

    return array('time' => (int)$decoded['time'], 'data' => $decoded['data']);
}

function link_decrypt_v2_gcm ($data, $key)
{
    /* GCM tag parameters were added to PHP's OpenSSL API in PHP 7.1.
       Keep this reader only to preserve links created by the interim v2 code. */
    if (PHP_VERSION_ID < 70100)
    {
        return false;
    }

    $blob = base64url_decode(substr($data, 3));
    if ($blob === false || strlen($blob) <= 28)
    {
        return false;
    }

    $iv = substr($blob, 0, 12);
    $tag = substr($blob, 12, 16);
    $ciphertext = substr($blob, 28);
    $derived_key = hash('sha256', (string)$key, true);

    $payload = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $derived_key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        'grlcpay:v2'
    );

    if ($payload === false) { return false; }

    $decoded = json_decode($payload, true);
    if (!is_array($decoded) || !isset($decoded['time'], $decoded['data']))
    {
        return false;
    }

    if (!is_numeric($decoded['time']) || !is_string($decoded['data']) || $decoded['data'] === '')
    {
        return false;
    }

    return array('time' => (int)$decoded['time'], 'data' => $decoded['data']);
}

function link_decrypt_legacy ($data, $key, $crypt="aes-256-cbc")
{
    $outer = base64_decode(trim((string)$data), true);
    if ($outer === false) { return false; }

    $inner = base64_decode($outer, true);
    if ($inner === false) { return false; }

    $encrypted = @gzuncompress($inner);
    if ($encrypted === false || strlen($encrypted) <= 32) { return false; }

    $ciphertext = substr($encrypted, 32);
    $iv = substr($encrypted, 16, 16);
    $decrypted = openssl_decrypt($ciphertext, $crypt, $key, 0, $iv);

    if (!is_string($decrypted) || strlen($decrypted) <= 10) { return false; }

    $time = substr($decrypted, 0, 10);
    $plain = substr($decrypted, 10);

    if (!ctype_digit($time) || $plain === '') { return false; }

    return array('time' => $time, 'data' => $plain);
}

function link_decrypt ($data, $key)
{
    $data = trim((string)$data);

    if (strpos($data, 'v3.') === 0)
    {
        return link_decrypt_v3($data, $key);
    }

    if (strpos($data, 'v2.') === 0)
    {
        return link_decrypt_v2_gcm($data, $key);
    }

    return link_decrypt_legacy($data, $key);
}
function load_var_decrypt ($array_url)
{
    $C_GET = array();

    if (trim($array_url) != '')
    {
        $array_url = explode("&", $array_url);
        if (count($array_url) > 0) 
        {
            foreach($array_url as $v_crypt)
            {
               $get_v = explode("=", $v_crypt, 2);
               if (trim($get_v[0]) == '') {continue;}
               $C_GET[$get_v[0]] = isset($get_v[1]) ? urldecode(trim($get_v[1])) : ''; 
            }
        }
    } return $C_GET;
}

function explorers_get ($addr)
{
    /* configured explorer endpoints */
    $url_explorer = array(
        "https://explorer.grlc.eu/addr.php?&api=1&op=balance&a=".rawurlencode($addr)
    );

    $context = stream_context_create(array(
        'http' => array(
            'timeout' => 6,
            'ignore_errors' => true,
            'user_agent' => 'grlcpay/1.2'
        ),
        'ssl' => array(
            'verify_peer' => true,
            'verify_peer_name' => true
        )
    ));

    $explorer = array();

    foreach ($url_explorer as $i => $url)
    {
        $raw = @file_get_contents($url, false, $context);
        $decoded = ($raw !== false) ? json_decode($raw, true) : null;
        $explorer[$i] = is_array($decoded) ? $decoded : array();
    }

    return $explorer;
}

function check_addr_balance ($addr, $explorer, $amount=0, $option=1)
{
    if ($addr == '' || !is_array($explorer)) {return false;}

    $balances = array();

    foreach ($explorer as $response)
    {
        if (is_array($response) && isset($response['balance']) && is_numeric($response['balance']))
        {
            $balances[] = (float)$response['balance'];
        }
    }

    if (count($balances) === 0) {return false;}

    switch ($option)
    {
      case "1":
       if ((float)$amount <= 0) {return false;}

       foreach ($balances as $balance)
       {
           if ($balance >= (float)$amount) {return true;}
       }

       return false;
      break;

      case "2":
       foreach ($balances as $balance)
       {
           if (abs($balance - (float)$amount) > 0.000000001) {return false;}
       }

       return true;
      break;

      default:
       return false;
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
        'auth' => (bool)$auth,
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

$pid = '';
if (isset($_POST['pid']) && is_string($_POST['pid'])) { $pid = $_POST['pid']; }
elseif (isset($_GET['pid']) && is_string($_GET['pid'])) { $pid = $_GET['pid']; }

switch ($pid)
{

   case "add":

    if (!$encryption_key_is_configured) {html_error('Configuration error: copy config.example.php to config.php and set $encryption_key to a private random value of at least 32 characters.');}

    if (!ensure_data_dir($data_dir)) {html_error('Data directory is not writable.');}

    $post_amount = (isset($_POST['amount']) && is_string($_POST['amount'])) ? str_replace(",", ".", trim($_POST['amount'])) : '';
    $post_addr = (isset($_POST['addr']) && is_string($_POST['addr'])) ? trim($_POST['addr']) : '';
    $post_email = (isset($_POST['email']) && is_string($_POST['email'])) ? trim($_POST['email']) : '';
    $post_code = (isset($_POST['code']) && is_string($_POST['code'])) ? trim($_POST['code']) : '';

    $amount = (preg_match("/^[0-9]+(?:\.[0-9]{1,8})?$/", $post_amount) && (float)$post_amount > 0) ? $post_amount : '';
    $addr = (preg_match("/^[a-zA-Z0-9]{30,100}$/", $post_addr)) ? $post_addr : '';
    $email = ($post_email === '' || filter_var($post_email, FILTER_VALIDATE_EMAIL) !== false) ? $post_email : '';
    $code = (strlen($post_code) > 0 && strlen($post_code) <= 2048) ? $post_code : '';

    $explorer = explorers_get($addr);

    $check_addr = (check_addr_balance($addr, $explorer, 0, 2)) ? 1 : 0;

    $error['balance'] = !$check_addr; 
    $error['addr'] = ($addr == '') ? 1 : 0;
    $error['amount'] = ($amount == '') ? 1 : 0; 
    $error['code'] = ($code == '') ? 1 : 0; 

    if ($amount == '' OR $addr == '' OR !$check_addr OR $code == '') {echo html_header('Fill out all fields correctly').html_form($error).html_footer();exit;}

    $aurl_hasz = http_build_query(array('a' => $amount, 'addr' => $addr, 'mail' => $email, 'code' => $code), '', '&', PHP_QUERY_RFC3986);
    $encrypt_link = link_encrypt($aurl_hasz, $encryption_key, $link_validity_in_seconds);

    $uniqid = secure_random_id();
    if ($encrypt_link === false || $uniqid === false) {html_error('Unable to securely create the payment link.');}

    if ($f = fopen($data_dir."/".$uniqid, 'xb'))
    {
        $bytes_written = fwrite($f, $encrypt_link);
        fclose($f);
        @chmod($data_dir."/".$uniqid, 0600);
        if ($bytes_written === false || $bytes_written !== strlen($encrypt_link))
        {
            @unlink($data_dir."/".$uniqid);
            html_error('Unable to write complete payment metadata.');
        }
        echo html_header('Payment link created');
        echo html_load_link($domain_name.'?q='.$uniqid);
        echo html_footer(); 
    }
     else
    {
        html_error('Unable to create payment metadata file in "'.h($data_dir).'".'); 
    }

   break;

   case "api_get":
   case "api_create":

    header('Content-Type: application/json; charset=utf-8');

    if (!$encryption_key_is_configured)
    {
        http_response_code(500);
        echo json_encode(array('error' => 'encryption_key_not_configured'));
        exit;
    }

    $api_input = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;

    if (!ensure_data_dir($data_dir)) {$json = array('error' => 'data_directory_not_writable'); echo json_encode($json); exit;}

    $get_amount = (isset($api_input['amount']) && is_string($api_input['amount'])) ? str_replace(",", ".", trim($api_input['amount'])) : '';
    $get_addr = (isset($api_input['addr']) && is_string($api_input['addr'])) ? trim($api_input['addr']) : '';
    $get_email = (isset($api_input['email']) && is_string($api_input['email'])) ? trim($api_input['email']) : '';
    $get_code = (isset($api_input['code']) && is_string($api_input['code'])) ? trim($api_input['code']) : '';

    $amount = (preg_match("/^[0-9]+(?:\.[0-9]{1,8})?$/", $get_amount) && (float)$get_amount > 0) ? $get_amount : '';
    $addr = (preg_match("/^[a-zA-Z0-9]{30,100}$/", $get_addr)) ? $get_addr : '';
    $email = ($get_email === '' || filter_var($get_email, FILTER_VALIDATE_EMAIL) !== false) ? $get_email : '';
    $code = (strlen($get_code) > 0 && strlen($get_code) <= 2048) ? $get_code : '';

    $explorer = explorers_get($addr);

    $check_addr = (check_addr_balance($addr, $explorer, 0, 2)) ? 1 : 0;

    $error['balance'] = !$check_addr; 
    $error['addr'] = ($addr == '') ? 1 : 0;
    $error['amount'] = ($amount == '') ? 1 : 0; 
    $error['code'] = ($code == '') ? 1 : 0; 

    if ($amount == '' OR $addr == '' OR !$check_addr OR $code == '') {$json['error'] = 1; echo json_encode($json); exit;}

    $aurl_hasz = http_build_query(array('a' => $amount, 'addr' => $addr, 'mail' => $email, 'code' => $code), '', '&', PHP_QUERY_RFC3986);
    $encrypt_link = link_encrypt($aurl_hasz, $encryption_key, $link_validity_in_seconds);

    $uniqid = secure_random_id();
    if ($encrypt_link === false || $uniqid === false) {$json = array('error' => 'secure_link_generation_failed'); echo json_encode($json); exit;}

    if ($f = fopen($data_dir."/".$uniqid, 'xb'))
    {
        $bytes_written = fwrite($f, $encrypt_link);
        fclose($f);
        @chmod($data_dir."/".$uniqid, 0600);
        if ($bytes_written === false || $bytes_written !== strlen($encrypt_link))
        {
            @unlink($data_dir."/".$uniqid);
            $json['error'] = 'payment_metadata_write_failed'; echo json_encode($json); exit;
        }
        $json['link_id'] = $domain_name.'?q='.$uniqid; echo str_replace(array("\/"), array("/"), json_encode($json)); exit;
    }
     else
    {
        $json['error'] = 'payment_metadata_write_failed'; echo json_encode($json); exit;
    }

   break;

   case "status":

    header('Content-Type: application/json; charset=utf-8');

    if (!$encryption_key_is_configured)
    {
        http_response_code(500);
        echo json_encode(array('status' => 'error', 'error' => 'encryption_key_not_configured'));
        exit;
    }

    $status_id = (isset($_GET['id']) && is_string($_GET['id'])) ? $_GET['id'] : '';
    $status_id = (preg_match("/^[a-z0-9]{32}$/i", $status_id)) ? $status_id : '';
    $status_file = ($status_id !== '') ? $data_dir."/".$status_id : '';

    if ($status_file === '' || !is_file($status_file) || !is_readable($status_file))
    {
        echo json_encode(array('status' => 'gone'));
        exit;
    }

    $status_handle = @fopen($status_file, 'rb');
    if ($status_handle === false || !flock($status_handle, LOCK_SH))
    {
        if (is_resource($status_handle)) { fclose($status_handle); }
        http_response_code(503);
        echo json_encode(array('status' => 'error', 'error' => 'payment_temporarily_unavailable'));
        exit;
    }

    if (!is_file($status_file))
    {
        flock($status_handle, LOCK_UN);
        fclose($status_handle);
        echo json_encode(array('status' => 'gone'));
        exit;
    }

    $status_payload = stream_get_contents($status_handle);
    $status_link = ($status_payload !== false) ? link_decrypt(trim($status_payload), $encryption_key) : false;

    if (!is_array($status_link) || !isset($status_link['time'], $status_link['data']))
    {
        flock($status_handle, LOCK_UN);
        fclose($status_handle);
        http_response_code(500);
        echo json_encode(array('status' => 'error', 'error' => 'invalid_payment_link'));
        exit;
    }

    $status_var = load_var_decrypt($status_link['data']);

    if (!isset($status_var['addr'], $status_var['a']))
    {
        flock($status_handle, LOCK_UN);
        fclose($status_handle);
        http_response_code(500);
        echo json_encode(array('status' => 'error', 'error' => 'invalid_payment_metadata'));
        exit;
    }

    if ((int)$status_link['time'] <= time())
    {
        flock($status_handle, LOCK_UN);
        fclose($status_handle);
        echo json_encode(array(
            'status' => 'expired',
            'expires_at' => (int)$status_link['time'],
            'remaining_seconds' => 0
        ));
        exit;
    }

    $status_explorer = explorers_get($status_var['addr']);
    $status_paid = check_addr_balance($status_var['addr'], $status_explorer, $status_var['a']);

    flock($status_handle, LOCK_UN);
    fclose($status_handle);

    echo json_encode(array(
        'status' => $status_paid ? 'paid' : 'waiting',
        'expires_at' => (int)$status_link['time'],
        'remaining_seconds' => max(0, (int)$status_link['time'] - time())
    ));
    exit;

   break;

   case "load":

    if (!$encryption_key_is_configured) {html_error('Configuration error: copy config.example.php to config.php and set $encryption_key to a private random value of at least 32 characters.');}

    $load_id = (isset($_GET['id']) && is_string($_GET['id'])) ? $_GET['id'] : '';
    $encrypt_link = (preg_match("/^[a-z0-9]{32}$/i", $load_id)) ? $load_id : '';
    $payment_file = ($encrypt_link !== '') ? $data_dir."/".$encrypt_link : '';

    if ($payment_file === '' || !is_file($payment_file) || !is_readable($payment_file))
    {
        echo html_header('Payment link not found').html_load_error('Payment link not found or already used.').html_footer();
        exit;
    }

    $payment_handle = @fopen($payment_file, 'r+b');
    if ($payment_handle === false || !flock($payment_handle, LOCK_EX))
    {
        if (is_resource($payment_handle)) { fclose($payment_handle); }
        echo html_header('Payment temporarily unavailable').html_load_error('Unable to lock payment data. Please retry.').html_footer();
        exit;
    }

    /* A second request may have opened the file before the first one consumed it.
       Re-check the pathname after acquiring the lock to preserve one-time delivery. */
    if (!is_file($payment_file))
    {
        flock($payment_handle, LOCK_UN);
        fclose($payment_handle);
        echo html_header('Payment link not found').html_load_error('Payment link not found or already used.').html_footer();
        exit;
    }

    $encrypted_payload = stream_get_contents($payment_handle);
    $decrypt_link = ($encrypted_payload !== false) ? link_decrypt(trim($encrypted_payload), $encryption_key) : false;

    if (!is_array($decrypt_link) || !isset($decrypt_link['time'], $decrypt_link['data']))
    {
        flock($payment_handle, LOCK_UN);
        fclose($payment_handle);
        echo html_header('Invalid payment link').html_load_error('Invalid payment link.').html_footer();
        exit;
    }

    $get_var = load_var_decrypt($decrypt_link['data']);

    if (!isset($get_var['addr'], $get_var['a'], $get_var['code'], $get_var['mail']))
    {
        flock($payment_handle, LOCK_UN);
        fclose($payment_handle);
        echo html_header('Invalid payment link').html_load_error('Invalid payment metadata.').html_footer();
        exit;
    }

    if ((int)$decrypt_link['time'] > time())
    { 
        $explorer = explorers_get($get_var['addr']);
        if (check_addr_balance($get_var['addr'], $explorer, $get_var['a']))
        {
            @unlink($payment_file);
            flock($payment_handle, LOCK_UN);
            fclose($payment_handle);

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
        }
         else
        {
            flock($payment_handle, LOCK_UN);
            fclose($payment_handle);
            echo html_header('Waiting for payment...');
            echo html_load_pay($get_var['a'], $get_var['addr'], $load_id, $decrypt_link['time']);
        }
    }
     else
    {
        @unlink($payment_file);
        flock($payment_handle, LOCK_UN);
        fclose($payment_handle);
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
