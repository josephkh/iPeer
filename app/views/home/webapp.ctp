<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>iPeer - <?php echo $title_for_layout; ?></title>
  <!-- Needed to force IE back to standards mode when it ignores the doctype -->
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
  <meta http-equiv="Content-Language" content="en" />
  <link rel="shortcut icon" href="/img/favicon.png" type="image/png" />
  
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">

  <?php echo $html->css('ipeer'); ?>
</head>
<body>
  <div id="root" class="containerOuter pagewidth">
    
    <div id="bannerLarge" class="banner">
      <div id="ipeerLogo">
        <a href="/" id="home">
          <img src="/img/layout/ipeer_logo.png" id="bannerLogoImgLeft" alt="logo"><span id="ipeerI">i</span><span id="ipeerText">Peer</span> <span id="bannerLogoText">3.4.8 with TeamMaker</span>
        </a>
      </div>
      <div id="customLogo">
      </div>
    </div>

    <div id="navigationOuter" class="navigation">
      <ul>
        <li id="current">
          <a>Home</a>
        </li>
      </ul>
    </div>

    
    <h3>Logged in user</h3>
    <pre id="user"></pre>
    <hr/>
    <h2>Logged in user events</h2>
    <pre id="events"></pre>
  </div>

  <script>

    const user = fetch('/api/v1/user')
      .then(json => json.json())
      .then(data => document.getElementById('user').innerHTML = JSON.stringify(data, null, 2))
      .catch(err => console.error(err));

    const events = fetch(`/api/v1/events`)
      .then(json => json.json())
      .then(data => document.getElementById('events').innerHTML = JSON.stringify(data, null, 2))
      .catch(err => console.error(err));
    
  </script>
</body>
</html>



