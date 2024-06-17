<?php
require_once("app/config.php");

require_once("app/session.php");

$uniq = '?t=' . uniqid();

$greetingMessage = CHAT_GREETING;
?>

<!doctype html>
<html lang="id">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Chat With Your Database</title>

	<!-- Vendor Assets -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
		crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

	<!-- Local Asset -->
	<link href="asset/css/style.css<?= $uniq ?>" rel="stylesheet">

	<!-- Google Fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@100..900&display=swap" rel="stylesheet">
</head>

<body>
	<div class="text-center my-3">Most of the feature are not working yet...</div>

	<div class="container-xl chat-wrapper bg-light px-0">
		<div class="left-panel">
			<div class="left-nav-bar p-3 bg-light">
				<!-- Login info -->
				<!-- // TODO -->
				<div class="d-flex justify-content-start">
					<img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="" class="img-avatar pull-left">

					<div class="ms-2 d-none d-lg-block">User<br> anonymous</div>
				</div>

				<!-- History search -->
				<!-- // TODO -->
				<div class="mt-4">
					<form id="frmSearchChat">
						<div class="input-group">
							<input type="text" class="form-control" placeholder="Search...">

							<button class="btn btn-outline-secondary" type="submit">
								<i class="bi bi-search"></i>
							</button>
						</div>
					</form>
				</div>
			</div>

			<!-- Conversation history -->
			<div id="user-chats" class="user-list overflow-x-hidden overflow-y-auto bg-light"></div>
		</div>
		<div class="right-panel">
			<div class="right-nav-bar d-flex align-items-center bg-light p-3">
				<div class="d-lg-none d-md-block" id="ms-menu-trigger">
					<button type="button" class="btn btn-outline-secondary"><i class="bi bi-list"></i></button>
				</div>

				<div class="d-none d-lg-block">
					<div class="d-flex align-items-center px-3">
						<img src="https://bootdey.com/img/Content/avatar/avatar1.png" alt="" class="img-avatar m-r-10">

						<div class="lv-avatar px-2">
						</div>

						<span>Assistant</span>
					</div>
				</div>

				<div class="list-group list-group-horizontal ms-auto">
					<a href="#" class="list-group-item list-group-item-action">
						<i class="bi bi-trash text-danger"></i>
					</a>

					<a href="#" class="list-group-item list-group-item-action">
						<i class="bi bi-arrow-clockwise"></i>
					</a>

					<a href="#" class="list-group-item list-group-item-action">
						<i class="bi bi-share"></i>
					</a>
				</div>
			</div>

			<!-- Conversation container -->
			<div id="conversations" class="chat-window pb-5">
				<button id="scroll-button" class="scroll-button"><i class="bi bi-arrow-down"></i></button>
			</div>

			<form class="message-input" id="frmPrompt">
				<input type="hidden" name="path" value="ollama/prompt" />

				<input type="hidden" name="sessionId" value="new" />

				<textarea placeholder="Type a message..." rows="2" name="prompt"></textarea>

				<button type="button" id="submit"><i class="bi bi-send"></i></button>
			</form>
		</div>
	</div>

	<script type="module">
		import { FormAI } from "./asset/js/formAI.js<?= $uniq ?>"

		new FormAI({
			container: 'frmPrompt',
			greeting: '<?= $greetingMessage ?>'
		})
	</script>
</body>


</html>