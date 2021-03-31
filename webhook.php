<?php

try {
    $myfile = fopen("log.txt", "w+") or die("Unable to open file!");
    $txt = "Came Request \n";
    fwrite($myfile, $txt);
    fwrite($myfile, "Today is " . date("Y/m/d H:i:s") . "\n");

// facebook variables
    $challenge = isset($_REQUEST['hub_challenge']) ? $_REQUEST['hub_challenge'] : '';
    $verify_token = isset($_REQUEST['hub_verify_token']) ? $_REQUEST['hub_verify_token'] : '';
    $fb_access_token = "{ access-token }";

// this is used to subscribe to facebook webhook
    if ($verify_token === "mytoken") { //
        echo $challenge;
    }

// Process retrieved data from facebook webhook
    $data = json_decode(file_get_contents("php://input"), true);
    fwrite($myfile, $data);
    fwrite($myfile, error_log(print_r($data, true)));
    $leadgen_id = $data['entry'][0]['changes'][0]['value']['leadgen_id']; // extract leadgen ID
    $form_id = $data['entry'][0]['changes'][0]['value']['form_id']; // extract form_id ID
    $created_time = $data['entry'][0]['changes'][0]['value']['created_time']; // extract created_time

    if ($leadgen_id) {
        if ($form_id) {
            $response = curl($form_id, $fb_access_token);
            $formInfo = json_decode($response, true);
            $formName = $formInfo['name'];
        }
        $response = curl($leadgen_id, $fb_access_token);
        $data = json_decode($response, true);

        $list = ["email", "phone_number", "province", "full_name"];
        $fields = [
            "email" => "",
            "phone_number" => "",
            "full_name" => "",
            "province" => "",
        ];
        for ($i = 0; $i < 4; $i++) {
            $field = $data['field_data'][$i]['name'];
            foreach ($list as $item) {
                if ($field == $item) {
                    $fields[$field] = $data['field_data'][$i]['values'][0];
                }
            }
        }

        $email = $fields['email'];
        $phone = $fields['phone_number'];
        $fullName = $fields['full_name'];
        $city = $fields['province'];

        fwrite($myfile, $email . "\n");
        fwrite($myfile, $phone . "\n");
        fwrite($myfile, $fullName . "\n");
        fwrite($myfile, $city . "\n");
        fwrite($myfile, $formName . "\n");


    }
    if ($email) {

        $xml_data = "<?xml version='1.0' encoding='utf-8'?>
                                    <subscription_data>
                                    <version>111</version>
                                    <subscriber>
                                    <attribute>
                                    <attr_name>email</attr_name>
                                    <attr_value><![CDATA[$email]]></attr_value>
                                    </attribute>
                                    <attribute>
                                    <attr_name>name</attr_name>
                                    <attr_value><![CDATA[$fullName]]></attr_value>
                                    </attribute>
                                    <attribute>
                                    <attr_name>phone</attr_name>
                                    <attr_value><![CDATA[$phone]]></attr_value>
                                    </attribute>
                                    <attribute>
                                    <attr_name>city</attr_name>
                                    <attr_value><![CDATA[$city]]></attr_value>
                                    </attribute>
                                    <attribute>
                                    <attr_name>facebook_form_name</attr_name>
                                    <attr_value><![CDATA[$formName]]></attr_value>
                                    </attribute>
                                    </subscriber>
                                    </subscription_data>";

        $ch1 = curl_init("www.myserver-test.com");
        curl_setopt($ch1, CURLOPT_POST, 1);
        curl_setopt($ch1, CURLOPT_POSTFIELDS, $xml_data);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch1, CURLOPT_HTTPHEADER, array("Content-Type: application/xml; charset=UTF-8"));
        $output = curl_exec($ch1);
        curl_close($ch1);
    }
} catch (Exception $e) {
    fwrite($myfile, "error: " . $e->getMessage());

}
fclose($myfile);

function curl($id, $fb_access_token)
{
    $ch = curl_init();
    $url = "https://graph.facebook.com/v10.0/" . $id;
    $url_query = "access_token=" . $fb_access_token;
    $url_final = $url . '?' . $url_query;
    curl_setopt($ch, CURLOPT_URL, $url_final);
    curl_setopt($ch, CURLOPT_HTTPGET, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

