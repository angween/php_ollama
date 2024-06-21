<?php
require_once ("app/config.php");

require_once ("app/session.php");

$uniq = '?t=' . uniqid();

$greetingMessage = CHAT_GREETING;
?>

<!doctype html>
<html lang="id">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Chat With Your Database with Ollama</title>

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

<body class="pt-3">

	<div id="loader" class="d-flex justify-content-center align-items-center vh-100 bg-dark opacity-50">
		<div class="spinner-border text-light" role="status">
			<span class="visually-hidden">Loading...</span>
		</div>
	</div>

	<div class="container-lg chat-wrapper bg-light px-0">
		<div class="left-panel">
			<div class="left-nav-bar p-0 bg-light">
				<!-- Login info -->
				<!-- // TODO -->
				<div class="d-flex p-3 justify-content-start border-bottom" style="max-height: 75px">
					<img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="" class="img-avatar pull-left">

					<div class="ms-2 d-none d-lg-block">User<br><span class="fw-light">Your chat history</span></div>
				</div>

				<!-- History search -->
				<!-- // TODO -->
				<div class="p-3 border-top" style="margin-top: 4px">
					<form id="frmSearchSession">
						<div class="input-group">
							<input type="text" class="form-control" placeholder="Search..." name="search">

							<button class="btn btn-outline-secondary" type="submit"
								style="--bs-btn-border-color: var(--bs-border-color);">
								<i class="bi bi-search"></i>
							</button>
						</div>
					</form>
				</div>
			</div>

			<!-- Conversation history -->
			<div class="user-list overflow-x-hidden overflow-y-auto flex-fill flex-grow-1 bg-light">
				<div id="sessionHistory" class="list-group">

					<!-- Placeholder -->
					<span class="list-group-item list-group-item-action d-flex justify-content-start p-3 bg-light">
						<div class="avatar">
							<div class="img-avatar placeholder"></div>
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<small class="username"><span class="placeholder col-7"></span></small>
							<small class="text-truncate"><span class="placeholder col-12"></span></small>
						</div>
					</span>

					<span class="list-group-item list-group-item-action d-flex justify-content-start p-3 bg-light">
						<div class="avatar">
							<div class="img-avatar placeholder"></div>
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<small class="username"><span class="placeholder col-7"></span></small>
							<small class="text-truncate"><span class="placeholder col-12"></span></small>
						</div>
					</span>

					<span class="list-group-item list-group-item-action d-flex justify-content-start p-3 bg-light">
						<div class="avatar">
							<div class="img-avatar placeholder"></div>
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<small class="username"><span class="placeholder col-7"></span></small>
							<small class="text-truncate"><span class="placeholder col-12"></span></small>
						</div>
					</span>


				</div>
			</div>
		</div>

		<div class="right-panel w-100 overflow-hidden">
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
					<a id="btnConversationNew" href="#" title="New conversation" class="list-group-item list-group-item-action">
						<i class="bi bi-chat"></i>
					</a>

					<a id="btnConversationDownload" href="#" title="Download conversation" class="list-group-item list-group-item-action">
						<i class="bi bi-floppy"></i>
					</a>

					<a id="btnConversationDelete" href="#" title="Delete conversation" class="list-group-item list-group-item-action">
						<i class="bi bi-trash text-danger"></i>
					</a>
				</div>
			</div>

			<!-- Model's Parameters -->
			<div id="modelParameters" class="d-flex flex-column justify-content-start w-100">
				<div class="parameters py-3 px-5 bg-light border-bottom">
					<div class="row mb-3">
						<label for="llm"
							class="col-xl-2 col-form-label d-md-none d-lg-none d-xl-block d-none">LLM</label>

						<div class="col-xl-10">
							<select id="llm" name="llm" form="frmPrompt" class="form-select">
								<option value="0" selected>Llama3</option>

								<option value="1">Qwen2</option>
							</select>
						</div>
					</div>

					<div class="row mb-3">
						<label for="general"
							class="col-xl-2 col-form-label d-md-none d-lg-none d-xl-block d-none">Topic</label>

						<div class="col-xl-10">
							<input type="radio" class="btn-check" value="database" name="topic" form="frmPrompt"
								id="database" autocomplete="off">
							<label class="btn" for="database">Database</label>

							<input type="radio" class="btn-check" value="general" name="topic" form="frmPrompt"
								id="general" autocomplete="off" checked>
							<label class="btn" for="general">General Question</label>
						</div>
					</div>
				</div>

				<div
					class="handler rounded-5 rounded-top-0 ms-auto text-center pb-1 bg-light text-muted border border-top-0 position-relative">
					<button type="button" id="btnShowParameters"><i class="bi bi-caret-down"></i></button>
				</div>
			</div>

			<!-- Conversation container -->
			<div class="chat-window pb-5">
				<div id="conversation"></div>

				<button id="scrollButton" class="scroll-button rounded-circle position-absolute"><i
						class="bi bi-arrow-down"></i></button>
			</div>

			<form class="message-input border-top" id="frmPrompt">
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
			frmElement: 'frmPrompt',
			greetingMessage: '<?= $greetingMessage ?>'
		})
	</script>
</body>


</html>