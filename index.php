<?
session_start();
if(isset($_GET['logout'])){ session_unset(); session_destroy(); header("Location: /obtanium"); }
if(isset($_GET['reset'])) { $_SESSION['images'] = set_default_images(); header("Location: /obtainium"); }

include "link-rel-parser.php";

$base = "https://apps.rhiaro.co.uk/obtanium";
if(isset($_GET['code'])){
  $auth = auth($_GET['code'], $_GET['state']);
  if($auth !== true){ $errors = $auth; }
  else{
    $response = get_access_token($_GET['code'], $_GET['state']);
    if($response !== true){ $errors = $auth; }
    else {
      header("Location: ".$_GET['state']);
    }
  }
}

// VIP cache
$vips = array("http://rhiaro.co.uk", "http://rhiaro.co.uk/", "http://tigo.rhiaro.co.uk/");
$images = set_default_images();

if(isset($_SESSION['images'])){
  $images = $_SESSION['images'];
}elseif(isset($_SESSION['me']) && in_array($_SESSION['me'], $vips)){
  $images = get_images("http://img.amy.gy/obtanium");
}
if(isset($_POST['images_source'])){
  $fetch = get_images($_POST['images_source']);
  if(!$fetch){
    $errors["Problem fetching images"] = "The images url needs to return a single page AS2 Collection as JSON.";
  }else{
    $images = $fetch;
  }
}
if(isset($images["@id"])){
  $images_source = $images["@id"];
}

function dump_headers($curl, $header_line ) {
  echo "<br>YEAH: ".$header_line; // or do whatever
  return strlen($header_line);
}

function auth($code, $state, $client_id="https://apps.rhiaro.co.uk/obtanium"){
  
  $params = "code=".$code."&redirect_uri=".urlencode($state)."&state=".urlencode($state)."&client_id=".$client_id;
  $ch = curl_init("https://indieauth.com/auth");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded", "Accept: application/json"));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
  //curl_setopt($ch, CURLOPT_HEADERFUNCTION, "dump_headers");
  $response = curl_exec($ch);
  $response = json_decode($response, true);
  $_SESSION['me'] = $response['me'];
  $info = curl_getinfo($ch);
  curl_close($ch);
  
  if(isset($response) && ($response === false || $info['http_code'] != 200)){
    $errors["Login error"] = $info['http_code'];
    if(curl_error($ch)){
      $errors["curl error"] = curl_error($ch);
    }
    return $errors;
  }else{
    return true;
  }
}

function get_access_token($code, $state, $client_id="https://apps.rhiaro.co.uk/obtainium"){
  
  $params = "me={$_SESSION['me']}&code=$code&redirect_uri=".urlencode($state)."&state=".urlencode($state)."&client_id=$client_id";
  $token_ep = discover_endpoint($_SESSION['me'], "token_endpoint");
  $ch = curl_init($token_ep);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
  $response = Array();
  parse_str(curl_exec($ch), $response);
  $info = curl_getinfo($ch);
  curl_close($ch);
  
  if(isset($response) && ($response === false || $info['http_code'] != 200)){
    $errors["Login error"] = $info['http_code'];
    if(curl_error($ch)){
      $errors["curl error"] = curl_error($ch);
    }
    return $errors;
  }else{
    $_SESSION['access_token'] = $response['access_token'];
    return true;
  }
  
}

function discover_endpoint($url, $rel="micropub"){
  if(isset($_SESSION[$rel])){
    return $_SESSION[$rel];
  }else{
    $res = head_http_rels($url);
    $rels = $res['rels'];
    if(!isset($rels[$rel][0])){
      $parsed = json_decode(file_get_contents("https://pin13.net/mf2/?url=".$url), true);
      if(isset($parsed['rels'])){ $rels = $parsed['rels']; }
    }
    if(!isset($rels[$rel][0])){
      // TODO: Try in body
      return "Not found";
    }
    $_SESSION[$rel] = $rels[$rel][0];
    return $rels[$rel][0];
  }
}

function as2(){
  return array(
      "@context" => "https://www.w3.org/ns/activitystreams#"
    );
}

function get_images($source=null){
  if($source){
    // TODO: get images from source
    if(is_array($_SESSION['images']) && !empty($_SESSION['images'])){
      return $_SESSION['images'];
    }
    curl_close($ch);
  }
  return false;
}

function set_default_images(){
  $_SESSION['images'] = array(array("@id" => "http://rhiaro.co.uk/stash/dp.png"));
}

function form_to_json($post){
  $data = as2();
  $data['location'] = $post['location'];
  if(isset($post['published'])){
    $data['published'] = $post['published'];
  }else{
    $data['published'] = date(DATE_ATOM);
  }
  $json = stripslashes(json_encode($data, JSON_PRETTY_PRINT));
  return $json;
}

function post_to_endpoint($json, $endpoint){
  $ch = curl_init($endpoint);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/activity+json"));
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer ".$_SESSION['access_token']));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
  $response = Array();
  parse_str(curl_exec($ch), $response);
  $info = curl_getinfo($ch);
  curl_close($ch);
  
  return $response;
}

if(isset($_POST['obtain'])){
  if(isset($_SESSION['me'])){
    $endpoint = discover_endpoint($_SESSION['me']);
    $result = post_to_endpoint(form_to_json($_POST), $endpoint);
  }else{
    $errors["Not signed in"] = "You need to sign in to post.";
  }
}

?>
<!doctype html>
<html>
  <head>
    <title>Obtainium</title>
    <link rel="stylesheet" type="text/css" href="https://apps.rhiaro.co.uk/css/normalize.min.css" />
    <link rel="stylesheet" type="text/css" href="https://apps.rhiaro.co.uk/css/main.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <main class="w1of2 center">
      <h1>Obtainium</h1>
      
      <?if(isset($errors)):?>
        <div class="fail">
          <?foreach($errors as $key=>$error):?>
            <p><strong><?=$key?>: </strong><?=$error?></p>
          <?endforeach?>
        </div>
      <?endif?>
      
      <?if(isset($result)):?>
        <div>
          <p>The response from you your micropub endpoint:</p>
          <code><?=$endpoint?></code>
          <pre>
            <? var_dump($result); ?>
          </pre>
        </div>
      <?endif?>
      
      <form method="post" role="form" id="obtain">
        <p><input type="submit" value="Post" class="neat" name="obtain" /></p>
        <p><label for="summary" class="neat">Description</label> <input type="text" name="summary" id="summary" class="neat" /></p>
        <p><label for="cost" class="neat">Cost</label> <input type="text" name="cost" id="cost"class="neat" /></p>
        <p><label for="published" class="neat">Published</label> <input type="text" name="published" id="published" class="neat" /></p>
        <ul>
          <?foreach($images as $image):?>
            <li><p><input type="radio" name="image[]" id="image" /> <label for="image"><img src="<?=$image["@id"]?>" /></label></p></li>
          <?endforeach?>
        </ul>
      </form>
      
      <div class="color3-bg inner">
        <?if(isset($_SESSION['me'])):?>
          <p class="wee">You are logged in as <strong><?=$_SESSION['me']?></strong> <a href="?logout=1">Logout</a></p>
        <?else:?>
          <form action="https://indieauth.com/auth" method="get" class="inner clearfix">
            <label for="indie_auth_url">Domain:</label>
            <input id="indie_auth_url" type="text" name="me" placeholder="yourdomain.com" />
            <input type="submit" value="signin" />
            <input type="hidden" name="client_id" value="http://rhiaro.co.uk" />
            <input type="hidden" name="redirect_uri" value="<?=$base?>" />
            <input type="hidden" name="state" value="<?=$base?>" />
            <input type="hidden" name="scope" value="post" />
          </form>
        <?endif?>
        
        <h2>Customise</h2>
        <h3>Images</h3>
        <?if(isset($images_source)):?>
          <p class="wee">Your images are from <strong><?=$images_source?></strong> <a href="?reset=1">Reset</a></p>
        <?else:?>
          <form method="post" class="inner wee clearfix">
            <p>If you have a directory with images you'd like to choose from, enter the URL here.</p>
            <label for="images_source">URL of a list of images:</label>
            <input id="images_source" name="images_source" value="http://img.amy.gy/obtainium" />
            <input type="submit" value="Fetch" />
          </form>
        <?endif?>
        <h3>Post...</h3>
        <form method="post" class="inner wee clearfix">
          <select name="posttype">
            <option value="as2" selected>AS2 JSON</option>
            <option value="mp" disabled>Micropub (form-encoded)</option>
            <option value="mp" disabled>Micropub (JSON)</option>
            <option value="ttl" disabled>Turtle</option>
          </select>
          <input type="submit" value="Save" />
        </form>
      </div>
    </main>
  </body>
</html>