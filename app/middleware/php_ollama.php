<?php
// Initialize a cURL session
$ch = curl_init();

// Define the URL
$urlGenerate = 'http://localhost:11434/api/generate';
$urlChat = 'http://localhost:11434/api/chat';

// system => "....." hanya berlaku untuk ai/generate
$dataChat = json_encode([
	"model" => "llama3",
	"stream" => false,
	"options" => [
		"temperature" => 1,
	],
	"messages" => [
		[
			"role" => "system",
			"content" => "You are Mario from super mario bros, acting as an assistant."
		],
		[
			"role" => "user",
			"content" => "How do I save my princess?"
		]
	]
]);


// "prompt" => "siapa nama anak-anaknya?"
/*
*/
// Set the cURL options
curl_setopt($ch, CURLOPT_URL, $urlChat);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $dataChat);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Content-Type: application/json',
	'Content-Length: ' . strlen($dataChat)
]);

// Execute the cURL request
$response = curl_exec($ch);


// Check for errors
if (curl_errno($ch)) {
	echo 'Error:' . curl_error($ch);
} else {
	echo "<pre>\n";

	$response = json_decode($response, true);
	print_r($response);

	if (isset($response['response'])) {
		$output = $response['response'];
	} elseif (isset($response['message'])) {
		$output = $response['message']['content'];
	}

	// Display the response
	// print_r($output);
	echo "\n</pre>";
}

// Close the cURL session
curl_close($ch);
