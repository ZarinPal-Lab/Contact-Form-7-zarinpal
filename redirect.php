<?php


global $wpdb;
global $postid;

$wpcf7 = WPCF7_ContactForm::get_current();
$submission = WPCF7_Submission::get_instance();
$user_email = '';
$user_mobile = '';
$description = '';
$user_price = '';

if ($submission) {
    $data = $submission->get_posted_data();
    $user_email = isset($data['user_email']) ? $data['user_email'] : "";
    $user_mobile = isset($data['user_mobile']) ? $data['user_mobile'] : "";
    $description = isset($data['description']) ? $data['description'] : "";
    $user_price = isset($data['user_price']) ? $data['user_price'] : "";
}


$price = get_post_meta($postid, "_cf7pp_price", true);
if ($price == "") {
    $price = $user_price;
}
$options = get_option('cf7pp_options');
foreach ($options as $k => $v) {
    $value[$k] = $v;
}
$active_gateway = 'ZarinPal';
$MID = $value['gateway_merchantid'];
$url_return = $value['return'];


//$user_email;
// Set Data -> Table Trans_ContantForm7
$table_name = $wpdb->prefix . "cfZ7_transaction";
$_x = array();
$_x['idform'] = $postid;
$_x['transid'] = ''; // create dynamic or id_get
$_x['gateway'] = $active_gateway; // name gateway
$_x['cost'] = $price;
$_x['created_at'] = time();
$_x['email'] = $user_email;
$_x['user_mobile'] = $user_mobile;
$_x['description'] = $description;
$_x['status'] = 'none';
$_y = array('%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s');

if ($active_gateway == 'ZarinPal') {


    $MerchantID = $MID; //Required
    $Amount = $price; //Amount will be based on Toman - Required
    $Description = $description; // Required
    $Email = $user_email; // Optional
    $Mobile = $user_mobile; // Optional
    $CallbackURL = get_site_url() . '/' . $url_return; // Required

   //  if (is_string($Mobile)==true){

    // }else {
         $strmobile=(string)$Mobile;
         if (strlen($strmobile)==0){
             $strmobile='0';
             $data = array("merchant_id" => $MerchantID,
                 "amount" => $Amount,
                 "callback_url" => $CallbackURL,
                 'description' => $Description,
                 'metadata' => ['mobile' => $strmobile,
                     'email' => $Email,],
             );
         }else {
             $data = array("merchant_id" => $MerchantID,
                 "amount" => $Amount,
                 "callback_url" => $CallbackURL,
                 'description' => $Description,
                 'metadata' => ['mobile' => $strmobile,
                     'email' => $Email,],
             );
         }


   //  }

    $jsonData = json_encode($data);
    $ch = curl_init('https://api.zarinpal.com/pg/v4/payment/request.json');
    curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ));

    $result = curl_exec($ch);
    $err = curl_error($ch);
  // echo '<br>' . var_dump($err) . '</br>';
    $result = json_decode($result, true, JSON_PRETTY_PRINT);
    curl_close($ch);
   // echo '<br>' . var_dump($result) . '</br>';

    

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        if (empty($result['errors'])) {

            if ($result['data']['code'] == 100) {

                $_x['transid'] = $result['data']["authority"];

                $s = $wpdb->insert($table_name, $_x, $_y);

                if ($value['zaringate'] == 1) {
                    header('Location: https://www.zarinpal.com/pg/StartPay/' . $result['data']["authority"] . '/ZarinGate');

                } else {
                    header('Location: https://www.zarinpal.com/pg/StartPay/' . $result['data']["authority"]);
                }
            } else {
                /*echo '<p>' .
                    $result['errors']['code'] . '<br>' .
                    $result['errors']['message'] . '<br>';
                echo '</p>';*/
                $tmp = 'خطایی رخ داده در اطلاعات پرداختی درگاه' . '<br>Error:' .  $result['errors']['code'] . '<br> لطفا به مدیر اطلاع دهید <br><br>';
                echo 'متن خطا' . $result['errors']['message'] . '</br>';
                $tmp .= '<a href="' . get_option('siteurl') . '" class="mrbtn_red" > بازگشت به سایت </a>';
                echo CreatePage_cf7('خطا در عملیات پرداخت', $tmp);
            }

        }
    }
    
}

?>


        
