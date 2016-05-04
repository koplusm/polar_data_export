<?php
	$EMAIL = ''; // Login Email
	$PASSWORD = ''; // Login Password
	$cookie_file_path = "/tmp/cookies.txt";
	$LOGINURL = 'https://www.polarpersonaltrainer.com/';
	$agent = "Nokia-Communicator-WWW-Browser/2.0 (Geos 3.0 Nokia-9000i)";

	$ch = curl_init();

	$headers[] = "Accept: */*";
	$headers[] = "Connection: Keep-Alive";

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);

	curl_setopt($ch, CURLOPT_URL, $LOGINURL);

	$content = curl_exec($ch);

	$fields = getFormFields($content);

	$fields['email'] = $EMAIL;
	$fields['password'] = $PASSWORD;

	$x = '';
	if (preg_match('/index\.tpl\?x=(\d+)/i', $content, $match)) {
		$x = $match[1];
	}

	$LOGINURL   = "https://www.polarpersonaltrainer.com/index.ftl?";

	$POSTFIELDS = http_build_query($fields);

	curl_setopt($ch, CURLOPT_URL, $LOGINURL);

	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);

	// perform login
	$result = curl_exec($ch);

	$feedURL = 'https://www.polarpersonaltrainer.com/user/liftups/feed.ftl?feedCount=900&feedFilterItems=target&feedFilterItems=result&feedFilterItems=message&feedFilterItems=fitnessdata&feedFilterItems=challenge&rmzer=1462351056075';
	curl_setopt($ch, CURLOPT_URL, $feedURL);
	$result = curl_exec($ch);
	$dom = new DOMDocument();
	@$dom->loadHTML($result);
	$links = $dom->getElementsByTagName('a');
	foreach($links as $link)
	{
		$addressList[] = $link->getAttribute('href');
	}

	foreach($addressList as $address)
	{
		$parts = parse_url($address);
		$output = [];
		parse_str($parts['query'], $output);
		$id = $output['id'];
		curl_setopt($ch, CURLOPT_URL, 'https://www.polarpersonaltrainer.com/user/calendar/index.jxml?.action=export&items.0.item='.$id.'&items.0.itemType=OptimizedExercise&.filename=Kontrabass_04.05.2016_export.xml');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);

		$myfile = fopen("files/".$id.".xml", "w") or die("Unable to open file!");
		fwrite($myfile, $result);
		fclose($myfile);
	}

	function getFormFields($data)
	{
		if (preg_match('/(<form name="login" action="index.ftl".*?<\/form>)/is', $data, $matches)) {
			$inputs = getInputs($matches[1]);

			return $inputs;
		} else {
			die('didnt find login form');
		}
	}

	function getInputs($form)
	{
		$inputs = array();

		$elements = preg_match_all('/(<input[^>]+>)/is', $form, $matches);

		if ($elements > 0) {
			for($i = 0; $i < $elements; $i++) {
				$el = preg_replace('/\s{2,}/', ' ', $matches[1][$i]);

				if (preg_match('/name=(?:["\'])?([^"\'\s]*)/i', $el, $name)) {
					$name  = $name[1];
					$value = '';

					if (preg_match('/value=(?:["\'])?([^"\'\s]*)/i', $el, $value)) {
						$value = $value[1];
					}

					$inputs[$name] = $value;
				}
			}
		}

		return $inputs;
	}