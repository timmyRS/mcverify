<?php
// Config:
$id_length = 4;
$domain = "mcverify.de";
// Config Over.

require "vendor/autoload.php";

$web_sock = stream_socket_server("tcp://0.0.0.0:80", $errno, $errstr) or die($errstr."\n");
$mc_sock = stream_socket_server("tcp://0.0.0.0:25565", $errno, $errstr) or die($errstr."\n");
$mc_priv = openssl_pkey_new(["private_key_bits" => 1024, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
$mc_server = new \Phpcraft\Server($mc_sock, $mc_priv);
$mc_server->join_function = function($con)
{
	global $id_length, $domain;
	$hostname = $con->hostname;
	if(substr($hostname, -1) == ".")
	{
		$hostname = substr($hostname, 0, -1);
	}
	if(strlen($hostname) == strlen($domain) + $id_length + 1 && substr($hostname, (strlen($domain) * -1) - 1) == ".".$domain)
	{
		global $challenges;
		$challenge = substr($hostname, 0, $id_length);
		if(array_key_exists($challenge, $challenges))
		{
			if($challenges[$challenge]["data"] == "{}")
			{
				$challenges[$challenge]["expiry"] = time() + 60;
				$challenges[$challenge]["data"] = json_encode([
					"username" => $con->username,
					"uuid" => $con->uuid
				]);
				$con->disconnect(["text" => "Thanks! You may now return to ".$challenges[$challenge]["service"]."."]);
			}
			else
			{
				$con->disconnect(["text" => "As I said, you may now return to ".$challenges[$challenge]["service"]."."]);
			}
		}
		else
		{
			$con->disconnect(["text" => "Unknown challenge. It may have already expired."]);
		}
	}
	else
	{
		$con->disconnect(["text" => "Something's wrong."]);
	}
};
$mc_server->list_ping_function = function($con)
{
	global $domain;
	return [
		"version" => [
			"name" => $domain,
			"protocol" => -1337
		],
		"description" => \Phpcraft\Phpcraft::textToChat("This server is for verifying Minecraft accounts.\n§4§lDon't keep it.")
	];
};

function str_rand($length)
{
	$str = "";
	$chars = range("a", "z");
	for($i = 0; $i < $length; $i++)
	{
		$str .= $chars[array_rand($chars)];
	}
	return $str;
}

$challenges = [];
$next_cleanup = time() + 300;
do
{
	$start = microtime(true);
	while(($stream = @stream_socket_accept($web_sock, 0)) !== false)
	{
		stream_set_blocking($stream, false);
		$con = new \Phpcraft\Connection(-1, $stream);
		if($con->readRawPacket(1.000, 128))
		{
			if(substr($con->read_buffer, 0, 12) == "GET /status/")
			{
				$challenge = substr($con->read_buffer, 12, $id_length);
				if(isset($challenges[$challenge]))
				{
					fwrite($stream, "HTTP/1.0 200\r\nContent-Type: application/json\r\n\r\n".$challenges[$challenge]["data"]);
					$challenges[$challenge]["expiry"] = time() + 60;
				}
				else
				{
					fwrite($stream, "HTTP/1.0 404\r\n\r\n");
				}
				fflush($stream);
			}
			else if(substr($con->read_buffer, 0, 9) == "OPTIONS /")
			{
				fwrite($stream, "HTTP/1.0 204\r\nAccess-Control-Allow-Origin: *\r\n\r\n");
				fflush($stream);
			}
			else if(substr($con->read_buffer, 0, 19) == "GET /start?service=")
			{
				do
				{
					$id = str_rand($id_length);
				}
				while(array_key_exists($id, $challenges));
				$challenges[$id] = [
					"service" => urldecode(explode(" ", substr($con->read_buffer, 19))[0]),
					"expiry" => time() + 60,
					"data" => "{}"
				];
				fwrite($stream, "HTTP/1.0 200\r\nContent-Type: text/plain\r\n\r\n{$id}.{$domain}");
				fflush($stream);
			}
			else if(substr($con->read_buffer, 0, 11) == "GET / HTTP/")
			{
				fwrite($stream, "HTTP/1.0 301\r\nLocation: https://github.com/timmyrs/mcverify#using-the-rest-api\r\n\r\n");
				fflush($stream);
			}
		}
		fclose($stream);
	}
	$mc_server->accept();
	$mc_server->handle();
	if(time() >= $next_cleanup)
	{
		$deleted = 0;
		foreach($challenges as $i => $challenge)
		{
			if(time() >= $challenge["expiry"])
			{
				unset($challenges[$i]);
				$deleted++;
			}
		}
		echo "Deleted {$deleted} expired challenges.\n";
		$next_cleanup = time() + 60;
	}
	if(($remaining = (0.020 - (microtime(true) - $start))) > 0)
	{
		time_nanosleep(0, $remaining * 1000000000);
	}
}
while(true);
