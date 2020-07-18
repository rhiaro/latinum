<?
session_start();
require 'vendor/autoload.php';
$ns = rhiaro\ERH\ns();

$tz = rhiaro\ERH\get_timezone_from_rdf("https://rhiaro.co.uk/tz");
date_default_timezone_set($tz);


if(isset($_POST['obtained'])){

    $errors = array("errno"=>0,"errors"=>array());

    if(isset($_POST['endpoint_key'])){
        $_SESSION['key'] = $_POST['endpoint_key'];
    }
    $endpoint = $_POST['endpoint_uri'];
    $payload = rhiaro\latinum\make_payload($_POST);

    if(is_array($payload)){
        $errors["errno"] += count($payload);
        $errors["errors"] = $payload;
        unset($payload);
    }else{
        $result = rhiaro\ERH\post_to_endpoint($endpoint, $_SESSION["key"], $payload);
        if($result->status_code == "201"){
            unset($_POST);
        }else{
            $errors["errno"] += 1;
            $errors["errors"]["status_code"] = $result->status_code;
            $errors["errors"]["raw"] = htmlentities($result->raw);
            unset($result);
        }
    }

}

if(!isset($tags)){
    $tags = rhiaro\latinum\get_tags();
}
include('templates/index.php');
?>