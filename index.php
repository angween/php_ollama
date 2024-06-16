<?php
$uniq = '?t=' . uniqid();
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
	<div class="container-xl chat-wrapper bg-light px-0">
		<div class="left-panel">
			<div class="left-nav-bar p-3 bg-light">
				<div class="d-flex justify-content-start">
					<img src="https://bootdey.com/img/Content/avatar/avatar1.png" alt="" class="img-avatar pull-left">

					<div class="ms-2 d-none d-lg-block">Signed in as <br> m-hollaway@gmail.com</div>
				</div>

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

			<div class="user-list overflow-x-hidden overflow-y-auto bg-light">
				<div class="list-group">
					<a class="list-group-item list-group-item-action d-flex justify-content-start p-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<small class="username">Davil Parnell</small>
							<small class="text-truncate">Fierent fastidii recteque ad pro fastidii recteque ad pro
								recteque ad pro fastidii recteque ad pro recteque ad pro fastidii recteque ad pro
							</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start p-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar3.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">Ann Watkinson</div>
							<small class="text-truncate">Cum sociis natoque penatibus </small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start p-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar4.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">Marse Walter</div>
							<small class="text-truncate">Suspendisse sapien ligula</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start p-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar5.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">Jeremy Robbins</div>
							<small class="text-truncate">Phasellus porttitor tellus nec</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start p-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar6.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">Reginald Horace</div>
							<small class="text-truncate">Quisque consequat arcu eget</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start p-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">Shark Henry</div>
							<small class="text-truncate">Nam lobortis odio et leo maximu</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start p-3 active"
						href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar1.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">Paul Van Dack</div>
							<small class="text-truncate">Nam posuere purus sed velit auctor
								sodales</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start p-3" href="">
						<div class="avatar">
							<img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="" class="img-avatar">
						</div>
						<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
							<div class="username">James Anderson</div>
							<small class="text-truncate">Vivamus imperdiet sagittis quam</small>
						</div>
					</a>

					<a class="list-group-item list-group-item-action d-flex justify-content-start p-3" href="">
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
		</div>
		<div class="right-panel">
			<div class="right-nav-bar d-flex align-items-center bg-light p-3">
				<div class="d-lg-none d-md-block" id="ms-menu-trigger">
					<button type="button" class="btn btn-outline-secondary"><i class="bi bi-list"></i></button>
				</div>

				<div class="d-none d-lg-block">
					<div class="d-flex align-items-center px-3">
						<img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="" class="img-avatar m-r-10">
						<div class="lv-avatar px-2">

						</div>
						<span>David Parbell</span>
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

			<div class="chat-window">
				<div class="message-feed media d-flex flex-row">
					<div class="avatar-bot">
						<img src="https://bootdey.com/img/Content/avatar/avatar1.png" alt="" class="img-avatar">
					</div>
					<div class="media-body px-2">
						<div class="mf-content">
							Quisque consequat arcu eget odio cursus, ut tempor arcu vestibulum. Etiam ex arcu, porta
							a
							urna non, lacinia pellentesque orci. Proin semper sagittis erat, eget condimentum sapien
							viverra et. Mauris volutpat magna nibh, et condimentum est rutrum a. Nunc sed turpis mi.
							In
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
							Mauris volutpat magna nibh, et condimentum est rutrum a. Nunc sed turpis mi. In eu massa
							a
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
							Etiam nec facilisis lacus. Nulla imperdiet augue ullamcorper dui ullamcorper, eu laoreet
							sem
							consectetur. Aenean et ligula risus. Praesent sed posuere sem. Cum sociis natoque
							penatibus
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
							Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.
							Etiam
							ac tortor ut elit sodales varius. Mauris id ipsum id mauris malesuada tincidunt.
							Vestibulum
							elit massa, pulvinar at sapien sed, luctus vestibulum eros. Etiam finibus tristique
							ante,
							vitae rhoncus sapien volutpat eget
						</div>
						<small class="mf-date"><i class="bi bi-clock-history"></i> 20/02/2015 at 10:24</small>
					</div>
				</div>

			</div>

			<div class="message-input">
				<textarea placeholder="Type a message..." rows="2"></textarea>

				<button><i class="bi bi-send"></i></button>
			</div>
		</div>
	</div>
</body>


</html>