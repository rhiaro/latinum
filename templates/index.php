<!doctype html>
<html>
  <head>
    <title>Latinum</title>
    <link rel="stylesheet" type="text/css" href="../../views/normalize.min.css" />
    <link rel="stylesheet" type="text/css" href="../../views/core.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
      main {
        width: 50%; margin-left: auto; margin-right: auto;
      }
      pre {
        max-height: 300px;
        overflow: scroll;
      }
      form p {
        display: flex;
        width: 100%;
      }
      form label {
        width: 20%;
        display: inline-block;
      }
      form input[type="text"], textarea {
        flex-grow: 1;
      }
      form input[type="submit"] {
        flex-grow: 1;
        padding: 0.4em;
      }
      form span label {
        width: auto;
      }
    </style>
  </head>
  <body>
    <main>
      <h1>Latinum</h1>

      <?if(isset($errors) && isset($errors["errno"]) && $errors["errno"] > 0):?>
        <div class="fail">
          <p><strong><?=$errors["errno"]?> error</strong></p>
          <?foreach($errors["errors"] as $key=>$error):?>
            <p><strong><?=$key?>: </strong><?=$error?></p>
          <?endforeach?>
        </div>
      <?endif?>

      <?if(isset($result)):?>
        <p class="win">Post created.. <strong><a href="<?=$result->headers['location']?>"><?=$result->headers['location']?></a></strong></p>
      <?endif?>

      <form method="post" role="form" id="repicator" class="align-center">

        <p>
          <label for="content">Description</label>
          <input type="text" name="content" id="content"<?=(isset($_POST['content'])) ? ' value="'.$_POST['content'].'"' : ''?> />
        </p>

        <p>
          <label for="cost">Cost</label>
          <input type="text" name="cost" id="cost"<?=(isset($_POST['cost'])) ? ' value="'.$_POST['cost'].'"' : ''?> size="5" placeholder="amount and currency" />

          <input type="text" name="amountEur" id="amountEur"<?=(isset($_POST['amountEur'])) ? ' value="'.$_POST['amountEur'].'"' : ''?> size="5" placeholder="eur" />

          <input type="text" name="amountGbp" id="amountGbp"<?=(isset($_POST['amountGbp'])) ? ' value="'.$_POST['amountGbp'].'"' : ''?> size="5" placeholder="gbp" />

          <input type="text" name="amountUsd" id="amountUsd"<?=(isset($_POST['amountUsd'])) ? ' value="'.$_POST['amountUsd'].'"' : ''?> size="5" placeholder="usd" />
        </p>

        <p>
          <label for="tags">Tags</label>
          <input type="text" name="tags[string]" id="tags"<?=(isset($_POST['tags']['string'])) ? ' value="'.$_POST['tags']['string'].'"' : ''?> />
        </p>
        <?if(isset($tags)):?>
          <p>
            <label></label>
            <span>
              <?foreach($tags as $label => $tag):?>
                <input type="checkbox" value="<?=$tag?>" name="tags[]" id="<?=$label?>"<?=(in_array($tag, $_POST['tags'])) ? " checked" : ""?> /><label for="<?=$label?>"><?=$label?></label>
              <?endforeach?>
            </span>
          </p>
        <?endif?>

        <p>
          <label>Published</label>
          <select name="year" id="year">
            <?for($i=date("Y");$i>=2018;$i--):?>
              <option value="<?=$i?>"<?=($i==$_POST['year']) ? " selected" : ""?>><?=$i?></option>
            <?endfor?>
          </select>
          <select name="month" id="month">
            <?for($i=1;$i<=12;$i++):?>
              <? $i = date("m", strtotime("2016-$i-01")); ?>
              <option value="<?=$i?>"
                <?=(isset($_POST['month']) && $_POST['month'] == $i) ? " selected" : (!isset($_POST['month']) && date("n") == $i) ? " selected" : ""?>>
                <?=date("M", strtotime("2016-$i-01"))?>
              </option>
            <?endfor?>
          </select>
          <select name="day" id="day">
            <?for($i=1;$i<=31;$i++):?>
              <? $i = date("d", strtotime("2016-01-$i")); ?>
              <option value="<?=$i?>"
                <?=(isset($_POST['day']) && $_POST['day'] == $i) ? " selected" : (!isset($_POST['day']) && date("j") == $i) ? " selected" : ""?>>
                <?=$i?>
              </option>
            <?endfor?>
          </select>
          <input type="text" name="time" id="time" value="<?=(isset($_POST['time'])) ? $_POST['time'] : date("H:i:s")?>" size="8" />
          <input type="text" name="zone" id="zone" value="<?=(isset($_POST['zone'])) ? $_POST['zone'] : date("P")?>" size="5" />
          <button id="reload">&gt;&gt;</button>
        </p>
        <p>
          <input type="submit" name="obtained" value="Post" />
        </p>
        <hr/>
        <!-- temp -->
        <select name="endpoint_uri">
          <option value="https://rhiaro.co.uk/outgoing/">rhiaro.co.uk</option>
          <option value="http://localhost/outgoing/">localhost</option>
        </select>
        <input type="password" name="endpoint_key"<?=isset($_SESSION['key']) ? 'value="'.$_SESSION['key'].'"' : ''?>" />
        <!--/ temp -->
        <hr/>
      </form>

      <div class="color3-bg inner">
        <?if(isset($_SESSION['me'])):?>
          <p class="wee">You are logged in as <strong><?=$_SESSION['me']?></strong> <a href="?logout=1">Logout</a></p>
        <?else:?>
          <form action="https://indieauth.com/auth" method="get" class="inner clearfix">
            <label for="indie_auth_url">Domain:</label>
            <input id="indie_auth_url" type="text" name="me" placeholder="yourdomain.com" />
            <input type="submit" value="signin" />
            <input type="hidden" name="client_id" value="https://rhiaro.co.uk" />
            <input type="hidden" name="redirect_uri" value="<?=$base?>" />
            <input type="hidden" name="state" value="<?=$base?>" />
            <input type="hidden" name="scope" value="post" />
          </form>
        <?endif?>

      </div>
    </main>
    <footer class="w1of2 center">
      <p><a href="https://github.com/rhiaro/latinum">Code</a> | <a href="https://github.com/rhiaro/latinum/issues">Issues</a>
      <?if(isset($_SESSION['access_token'])):?>
        | <a href="https://apps.rhiaro.co.uk/latinum?token=<?=$_SESSION['access_token']?>">Quicklink</a>
      <?endif?>
      </p>
    </footer>

    <script src="js/reload-button.js"></script>
  </body>
</html>