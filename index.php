<?
session_start();
date_default_timezone_set(file_get_contents("http://rhiaro.co.uk/tz"));
if(isset($_GET['logout'])){ session_unset(); session_destroy(); header("Location: /obtainium"); }
if(isset($_GET['reset'])) { $_SESSION['images'] = set_default_images(); header("Location: /obtainium"); }

include "link-rel-parser.php";

$base = "https://apps.rhiaro.co.uk/obtainium";
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
}//elseif(isset($_SESSION['me']) && in_array($_SESSION['me'], $vips)){
//  $images = get_images("http://img.amy.gy/obtainium");
//}
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

function auth($code, $state, $client_id="https://apps.rhiaro.co.uk/obtainium"){
  
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

function context(){
  return array(
      "@context" => array("as" => "https://www.w3.org/ns/activitystreams#", "blog" => "http://vocab.amy.so/blog#")
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
  $collection = json_decode(file_get_contents("http://img.amy.gy/files/food/food.json"), true);
  if(is_array($collection["@context"])) $aspref = array_search("http://www.w3.org/ns/activitystreams#", $collection["@context"]);
  if(isset($aspref)){
    $_SESSION['images'] = $collection[$aspref.":items"];
  }else{
    $_SESSION['images'] = $collection["items"];
  }
  $_SESSION['image_source'] = $collection["@id"];
  
}

function form_to_json($post){
  $data = context();
  $data = $post;
  unset($data['obtain']);
  $data["@type"] = array("blog:Acquisition");
  $data['as:published'] = $post['year']."-".$post['month']."-".$post['day']."T".$post['time'].$post['zone'];
  unset($data['year']); unset($data['month']); unset($data['day']); unset($data['time']); unset($data['zone']);
  if(isset($post['image'])) $data['as:image'] = array("@id" => $post['image'][0]);
  $data["blog:cost"] = $post['cost']; unset($data['cost']);
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
        <p>
          <select name="year" id="year">
            <option value="2016" selected>2016</option>
            <option value="2016">2015</option>
          </select>
          <select name="month" id="month">
            <?for($i=1;$i<=12;$i++):?>
              <option value="<?=date("m", strtotime("2016-$i-01"))?>"<?=(date("n") == $i) ? " selected" : ""?>><?=date("M", strtotime("2016-$i-01"))?></option>
            <?endfor?>
          </select>
          <select name="day" id="day">
            <?for($i=1;$i<=31;$i++):?>
              <option value="<?=date("d", strtotime("2016-01-$i"))?>"<?=(date("j") == $i) ? " selected" : ""?>><?=date("d", strtotime("2016-01-$i"))?></option>
            <?endfor?>
          </select>
          <input type="text" name="time" id="time" value="<?=date("H:i:s")?>" />
          <input type="text" name="zone" id="zone" value="<?=date("P")?>" />
        </p>
        <ul class="clearfix">
          <?foreach($images as $image):?>
            <li class="w1of5"><p><input type="radio" name="image[]" id="image" value="<?=$image["@id"]?>" /> <label for="image"><img src="<?=$image["@id"]?>" width="100px" /></label></p></li>
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