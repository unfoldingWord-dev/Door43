<?php
// Turn off output buffering
ini_set('output_buffering', 'off');
// Turn off PHP output compression
ini_set('zlib.output_compression', false);
         
//Flush (send) the output buffer and turn off output buffering
//ob_end_flush();
while (@ob_end_flush());

// Implicitly flush the buffer(s)
ini_set('implicit_flush', true);
ob_implicit_flush(true);

//prevent apache from buffering it for deflate/gzip
header( 'Content-type: text/html; charset=utf-8' );
header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
  
for($i = 0; $i < 1000; $i++)
{
  echo ' ';
}
           
flush();

/// Now start the program output
?>
<head>
	<title>Bootstrap</title>
</head>
<body>
<?php
echo 'Putting config files in place...<br/>';
echo `cp conf/local.php.dev conf/local.php`;
echo `cp conf/plugins.local.php.dev conf/plugins.local.php`;
echo `cp conf/acl.auth.php.dev conf/acl.auth.php`;
echo `cp conf/users.auth.php.dev conf/users.auth.php`;

flush();
echo 'Making git configurations...<br/>';
echo `git config core.fileMode false`;

flush();
echo 'Pulling submodules...<br/>';
echo `git submodule init`;
echo `git submodule update`;
?>
<p>
OK! Proceed to <a href="home">Home page</a>
</p>
</body>

