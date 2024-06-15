<!doctype html>
<html lang="id">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Chat With Your Database</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
		crossorigin="anonymous">
	<link href="asset/css/styles.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
	<div class="container bootstrap snippets bootdey">
		<div class="tile tile-alt" id="messages-main">
			<div class="ms-menu">
				<div class="ms-user clearfix d-flex justify-content-start px-3">
					<img src="https://bootdey.com/img/Content/avatar/avatar1.png" alt="" class="img-avatar pull-left">

					<div>Signed in as <br> m-hollaway@gmail.com</div>
				</div>

				<div class="p-3">
					<form id="frmSearchChat">
						<div class="input-group">
							<input type="text" class="form-control" placeholder="Search...">

							<button class="btn btn-outline-secondary" type="submit">
								<i class="bi bi-search"></i>
							</button>
						</div>
					</form>
				</div>

				<div class="list-group lg-alt">
					<a class="list-group-item list-group-item-action d-flex justify-content-start px-3 mb-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<small class="username">Davil Parnell</small>
							<small class="text-truncate">Fierent fastidii recteque ad pro fastidii recteque ad pro
							</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start px-3 mb-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar3.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">Ann Watkinson</div>
							<small class="text-truncate">Cum sociis natoque penatibus </small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start px-3 mb-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar4.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">Marse Walter</div>
							<small class="text-truncate">Suspendisse sapien ligula</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start px-3 mb-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar5.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">Jeremy Robbins</div>
							<small class="text-truncate">Phasellus porttitor tellus nec</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start px-3 mb-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar6.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">Reginald Horace</div>
							<small class="text-truncate">Quisque consequat arcu eget</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start px-3 mb-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">Shark Henry</div>
							<small class="text-truncate">Nam lobortis odio et leo maximu</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start px-3 mb-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar1.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">Paul Van Dack</div>
							<small class="text-truncate">Nam posuere purus sed velit auctor
								sodales</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start px-3 mb-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">James Anderson</div>
							<small class="text-truncate">Vivamus imperdiet sagittis quam</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start px-3 mb-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar3.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">Kane Williams</div>
							<small class="text-truncate">Suspendisse justo nulla luctus nec</small>
						</div>
					</a>
				</div>


			</div>

			<div class="ms-body">
				<div class="action-header clearfix d-flex align-items-center px-3">
					<div class="visible-xs" id="ms-menu-trigger">
						<i class="bi bi-list"></i>
					</div>

					<div class="d-flex align-items-center px-3">
						<img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="" class="img-avatar m-r-10">
						<div class="lv-avatar px-2">

						</div>
						<span>David Parbell</span>
					</div>

					<ul class="ah-actions actions ms-auto">
						<li>
							<a href="">
								<i class="bi bi-trash"></i>
							</a>
						</li>
						<li>
							<a href="">
								<i class="bi bi-arrow-clockwise"></i>
							</a>
						</li>
						<li>
							<a href="">
								<i class="bi bi-share"></i>
							</a>
						</li>
					</ul>
				</div>

				<div class="message-feed media d-flex flex-row">
					<div class="avatar-bot">
						<img src="https://bootdey.com/img/Content/avatar/avatar1.png" alt="" class="img-avatar">
					</div>
					<div class="media-body px-2">
						<div class="mf-content">
							Quisque consequat arcu eget odio cursus, ut tempor arcu vestibulum. Etiam ex arcu, porta a
							urna non, lacinia pellentesque orci. Proin semper sagittis erat, eget condimentum sapien
							viverra et. Mauris volutpat magna nibh, et condimentum est rutrum a. Nunc sed turpis mi. In
							eu massa a sem pulvinar lobortis.
						</div>
						<small class="mf-date"><i class="bi bi-clock-history"></i> 20/02/2015 at 09:00</small>
					</div>
				</div>

				<div class="message-feed right d-flex flex-row-reverse">
					<div class="avatar-user">
						<img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="" class="img-avatar">
					</div>
					<div class="media-body px-2">
						<div class="mf-content">
							Mauris volutpat magna nibh, et condimentum est rutrum a. Nunc sed turpis mi. In eu massa a
							sem pulvinar lobortis.
						</div>
						<small class="mf-date"><i class="bi bi-clock-history"></i> 20/02/2015 at 09:30</small>
					</div>
				</div>

				<div class="message-feed media d-flex flex-row">
					<div class="avatar-bot">
						<img src="https://bootdey.com/img/Content/avatar/avatar1.png" alt="" class="img-avatar">
					</div>
					<div class="media-body px-2">
						<div class="mf-content">
							Etiam ex arcumentum
						</div>
						<small class="mf-date"><i class="bi bi-clock-history"></i> 20/02/2015 at 09:33</small>
					</div>
				</div>

				<div class="message-feed right d-flex flex-row-reverse">
					<div class="avatar-user">
						<img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="" class="img-avatar">
					</div>
					<div class="media-body px-2">
						<div class="mf-content">
							Etiam nec facilisis lacus. Nulla imperdiet augue ullamcorper dui ullamcorper, eu laoreet sem
							consectetur. Aenean et ligula risus. Praesent sed posuere sem. Cum sociis natoque penatibus
							et magnis dis parturient montes,
						</div>
						<small class="mf-date"><i class="bi bi-clock-history"></i> 20/02/2015 at 10:10</small>
					</div>
				</div>

				<div class="message-feed media d-flex flex-row">
					<div class="avatar-bot">
						<img src="https://bootdey.com/img/Content/avatar/avatar1.png" alt="" class="img-avatar">
					</div>
					<div class="media-body px-2">
						<div class="mf-content">
							Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Etiam
							ac tortor ut elit sodales varius. Mauris id ipsum id mauris malesuada tincidunt. Vestibulum
							elit massa, pulvinar at sapien sed, luctus vestibulum eros. Etiam finibus tristique ante,
							vitae rhoncus sapien volutpat eget
						</div>
						<small class="mf-date"><i class="bi bi-clock-history"></i> 20/02/2015 at 10:24</small>
					</div>
				</div>

				<div class="msb-reply">
					<textarea placeholder="What's on your mind..."></textarea>
					<button><i class="bi bi-send"></i></button>
				</div>
			</div>
		</div>
	</div>


	<?php
	// include ("src/php_ollama.php");
	?>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
		crossorigin="anonymous"></script>
</body>

</html>