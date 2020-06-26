<?php
// If is not empty it sets a header From in e-mail message (sets sender e-mail).
// Note: some hosting servers can block sending e-mails with custom From field in header.
//       If so, leave this field as empty.
define('FROM_EMAIL', 'sistemas@ewarecorp.com');
// Recipient's e-mail. To this e-mail messages will be sent.
// e.g.: john@example.com
// multiple recipients e.g.: john@example.com, andy@example.com
define('TO_EMAIL', 'servicioalcliente@ewarecorp.com');
/**
 * Function for sending messages. Checks input fields, prepares message and sends it.
 */
function sendMessage() {
  // Variables init

  $json = array();
  $token = "9320087105434084715";

  $contact_name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
  $contact_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
  $contact_tel = filter_input(INPUT_POST, 'tel', FILTER_SANITIZE_STRING);
 // $contact_department = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING);

  $contact_message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

  // This field is special, and it's used for anti bot protection.
  $contact_secret = filter_input(INPUT_POST, 'contact_secret', FILTER_SANITIZE_STRING);

  // Decode secret
  $contact_secret = strrev($contact_secret);

  // Token set in JS file have to be the same as in PHP file
  if ($contact_secret !== $token) {
      $json['result'] = 'NO_SPAM';
      header('Access-Control-Allow-Origin: *');
      echo json_encode($json);
      die();
  }

  // Adding e-mail headers
  $headers = "";
  if (FROM_EMAIL !== '') {
      $headers .= 'From: '.FROM_EMAIL."\r\n";
  }
  $headers .= 'Reply-To: '.$contact_email."\r\n";
  $headers .= 'Content-Type: text/plain; charset=UTF-8'."\r\n";

  /*
   * Formatting message.
   * It can be customizable in any way you like.
   */
  $title = 'Contacto Web - Nuevo Mensaje de '.$contact_name;
  $message = 'Se ha recibido un nuevo mensaje del sitio web. Detalles abajo:'."\n\n"
      .'IP de Origen: '.getIp()."\n"
      .'Asunto: Contacto Web'."\n"
      .'Nombre: '.$contact_name."\n"
      .'E-mail: '.$contact_email."\n"
      .'Teléfono: '.$contact_tel."\n"
      // .'Departamento Seleccionado: '.$contact_department."\n\n"
      .'Mensaje:'."\n"
      .$contact_message;

  // Internal mail
  $result = mail(TO_EMAIL, $title, $message, $headers);

  // Contact mail data  
  $subject_contact_mail = "Hemos recibido tu información desde el site de Eware";
  $message_contact_mail = "Estimad@ ".  $contact_name . "\n"
    . "Confirmamos que hemos recibido tu informacion desde el site de Eware, por lo que te contactaremos a la brevedad. \n"
    . "\n\n"
    . "Cordialmente, \n"
    . "Equipo Eware";
  $header_contact_mail = "";
  if (isset($contact_email) and $contact_email != "") {
    $header_contact_mail .= "From: " . TO_EMAIL . "\r\n";
  }
  $header_contact_mail .= 'Reply-To: ' . TO_EMAIL . "\r\n";
  $header_contact_mail .= 'Content-Type: text/plain; charset=UTF-8 ' . "\r\n";
  // Contact mail
  $result_contact = mail($contact_email, $subject_contact_mail, $message_contact_mail, $header_contact_mail);
  
  if ($result and $result_contact) {
    $json['result'] = 'OK';
  } else {
    $json['result'] = 'SEND_ERROR';
    // Debug message
    $json['message'] = "";
    if (!$result) {
        $json['message'] .= 'No se logro enviar el email de contacto';
    }
    if (!$result_contact) {
        $json['message'] .= 'No se logro enviar el email de contacto';
    }
  }
  header('Access-Control-Allow-Origin: *');
  echo json_encode($json);
  die();
}

/**
 * Function for getting visitor's IP address
 * @return string
 */
function getIp() {
    $ip = '';

    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } else if(getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } else if(getenv('HTTP_X_FORWARDED')) {
        $ip = getenv('HTTP_X_FORWARDED');
    } else if(getenv('HTTP_FORWARDED_FOR')) {
        $ip = getenv('HTTP_FORWARDED_FOR');
    } else if(getenv('HTTP_FORWARDED')) {
        $ip = getenv('HTTP_FORWARDED');
    } else if(getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    } else {
        $ip = 'N/A';
    }

    return $ip;
}

/*
 * Calling a from only when post request is detected (data was sent by form).
 * Otherwise it returns OK, which can be handy with checking that the script is alive.
 */


sendMessage();
die();
/*if(!empty($_POST["recaptcha"])) {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $recaptcha = $_POST["recaptcha"];
    $ip = $_SERVER['REMOTE_ADDR'];
    $url = 'https://www.google.com/recaptcha/api/siteverify?secret=6LeurDUUAAAAAIdBeWGpEOQrugOwaCDN8ThP1r_n&response='.$recaptcha."&remoteip=".$ip;

    $verify = file_get_contents($url);
    $captcha_success = json_decode($verify, true);
    if (isset($captcha_success['success']) && $captcha_success['success']) {
        sendMessage();
        die();
    } else {
        $json['result'] = 'SEND_ERROR';
        header('Access-Control-Allow-Origin: *');
        echo json_encode($json);
        die();
    }
  } else {
      $json['result'] = 'SEND_ERROR';
      header('Access-Control-Allow-Origin: *');
      echo json_encode($json);
      die();
  }
} else {
  $json['result'] = 'NO_CAPTCHA';
  header('Access-Control-Allow-Origin: *');
  echo json_encode($json);
  die();
}
