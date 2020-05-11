<?php

	if($_SERVER['REQUEST_METHOD'] == "POST"){
		$result = [];

		if(isset($_FILES["recordedFile"]) && !$_FILES["recordedFile"]['error']){
			move_uploaded_file($_FILES["recordedFile"]['tmp_name'], "uploads/" . date("YmdHis") . ".ogg");
			$result["error"] = 0;
			$result["message"] = "File uploaded successfully";
		}else{
			$result["error"] = 1;
			$result["message"] = "Error: " . $_FILES["recordedFile"]['error'];
		}

		echo json_encode($result);
	}else{
		$maxRecordTime = "00:05";
?>
<!DOCTYPE html>
<html>
<head>
	<title>Audio Record | Test Programs</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

	<!-- Styles -->
	<link rel="stylesheet" href="assets/materialize/css/materialize.min.css" />
	<link rel="stylesheet" href="assets/materialize/icon/icon.css" />
	<style type="text/css">
		html, body
		{
			height: 100%;
			width: 100%;
			margin: 0;
		}

		body > .main-container
		{
			min-height: 100%;
			width: 100%;
			display: inline-flex;
			justify-content: center;
			align-items: center;
			position: relative;
		}

		.center-child
		{
			text-align: center;
		}

		#progress
		{
			width: 50rem;
			display: none;
		}
	</style>
</head>
<body>

	<div class="container main-container">
		<div id="progress">
			<div class="progress">
				<div class="indeterminate"></div>
			</div>
		</div>

		<div class="center-child" id="parent-recorder">
			<h4>Click on the Mic button to record audio</h4>
			<br>

			<audio id="recorder" style="display: none;"></audio>

			<div id="parent-player">
				<audio id="player" controls style="display: none;"></audio>

				<span id="recordTiming"><?=$maxRecordTime;?></span>

				<br><br>
			</div>

			<a class="waves-effect waves-light btn-floating btn-large default" id="btnRecordAudio">
				<i class="large material-icons">mic</i>
			</a>

			<a class="waves-effect waves-light btn-floating btn-large default" id="btnPlayRecordedAudio" style="display: none;">
				<i class="large material-icons">play_arrow</i>
			</a>

			<a class="waves-effect waves-light btn-floating btn-large default tooltipped" id="btnDownloadRecordedAudio" download data-position="right" data-tooltip="Download" style="display: none;">
				<i class="large material-icons">file_download</i>
			</a>

			<br><br>

			<a class="waves-effect waves-light btn-floating btn-large default tooltipped" id="btnUploadRecordedAudio" data-tooltip="Upload to Server" style="display: none;">
				<i class="large material-icons">cloud_upload</i>
			</a>

			<form method="POST" enctype="multipart/form-data" id="frmUploadAudio" style="display: none;"></form>
		</div>
	</div>

	<!-- Scripts -->
	<script src="assets/js/jquery-3.4.1.min.js"></script>
	<script src="assets/materialize/js/materialize.min.js"></script>
	<script type="text/javascript">
		function initAudioRecording()
		{
			if(navigator.mediaDevices && navigator.mediaDevices.getUserMedia){
				navigator.mediaDevices.getUserMedia({ audio: true })
						 .then(function(stream){
						 	window.mediaRecorder = new MediaRecorder(stream);
						 	btnRecordAudio.removeAttr('disabled');
						 })
						 .catch(function(err){
						 	console.error(err);
						 	btnRecordAudio.attr('disabled', true);
						 });
			}else{
				console.error("Browser not supported");
				btnRecordAudio.attr('disabled', true);
			}
		}

		function recordAudio()
		{
			if(typeof(mediaRecorder) != 'undefined'){
				var action = btnRecordAudio.hasClass('recording') ? 'stop' : 'record';

				if(action == 'record'){
					btnRecordAudio.children('.material-icons').html('stop');
					btnRecordAudio.removeClass('default').addClass('red');

					window.audioTracks = [];

					mediaRecorder.ondataavailable = function(e){
						audioTracks.push(e.data);
					};

					mediaRecorder.onstop = function(e){
						var blob = new Blob(window.audioTracks, {type: 'audio/ogg; codecs=opus'});
						var audioURL = window.URL.createObjectURL(blob);

						window.audioBlob = blob;
						player[0].src = audioURL;

						btnDownloadRecordedAudio.attr('href', audioURL);
						btnDownloadRecordedAudio.show();
						btnUploadRecordedAudio.show();

						btnPlayRecordedAudio.children('.material-icons').html('play_arrow');
						//btnPlayRecordedAudio.show();
						player.show();
						recordTiming.hide();
					};

					mediaRecorder.onstart = function(){
						setTimeout(function(){
							$('#progress').hide();
							$('#parent-recorder').show();
							window.intvlRecordTiming = setInterval(function(){
								runRecordTimer();
							}, 1000);
						}, 1000);
					};

					mediaRecorder.start();

					btnRecordAudio.addClass('recording');

					console.log("Recording started");
					console.log(mediaRecorder.state);

					//btnPlayRecordedAudio.hide();
					btnDownloadRecordedAudio.hide();
					btnUploadRecordedAudio.hide();
					player.hide();
					recordTiming.show();
					$('#progress').show();
					$('#parent-recorder').hide();
				}else{
					btnRecordAudio.children('.material-icons').html('mic');
					btnRecordAudio.removeClass('red').addClass('default');

					clearInterval(intvlRecordTiming);
					recordTiming.html("<?=$maxRecordTime;?>");
					mediaRecorder.stop();

					btnRecordAudio.removeClass('recording');

					console.log("Recording stopped");
					console.log(mediaRecorder.state);
				}
			}
		}

		function runRecordTimer()
		{
			var time = recordTiming.text();
				time = time.split(':');
			var min  = Number(time[0]);
			var sec  = Number(time[1]);

			if(sec <= 1){
				if(min > 0){
					sec = 59;
					min--;
				}else{
					sec = 0;
					clearInterval(intvlRecordTiming);
					recordTiming.html("<?=$maxRecordTime;?>");
					btnRecordAudio.trigger('click');
					return;
				}
			}else{
				sec--;
			}

			if(sec < 10){
				sec = "0" + sec;
			}

			if(min < 10){
				min = "0" + min;
			}

			recordTiming.html(min + ":" + sec);
		}

		$(document).ready(function(){
			window.recorder = $('#recorder');
			window.player = $('#player');
			window.btnRecordAudio = $('#btnRecordAudio');
			window.btnPlayRecordedAudio = $('#btnPlayRecordedAudio');
			window.btnDownloadRecordedAudio = $('#btnDownloadRecordedAudio');
			window.btnUploadRecordedAudio = $('#btnUploadRecordedAudio');
			window.frmUploadAudio = $('#frmUploadAudio');
			window.recordTiming = $('#recordTiming');

			player[0].onended = function(){
				btnPlayRecordedAudio.removeClass('playing').removeClass('playing');
				btnPlayRecordedAudio.children('.material-icons').html('replay');
			};

			$('.tooltipped').tooltip();
			initAudioRecording();
		});

		$(document).on('click', '#btnRecordAudio', function(e){
			e.preventDefault();
			recordAudio();
		});

		$(document).on('click', '#btnPlayRecordedAudio', function(e){
			e.preventDefault();

			var playIcon = $(this).children('.material-icons');
			
			if($(this).hasClass('playing')){
				$(this).removeClass('playing');
				playIcon.html('play_arrow');
				player[0].pause();
			}else{
				playIcon.html('pause');
				$(this).addClass('playing');
				player[0].play();
			}
		});

		$(document).on('click', '#btnUploadRecordedAudio', function(e){
			e.preventDefault();

			var data = new FormData(frmUploadAudio[0]);
				data.append('recordedFile', audioBlob);

			$('#progress').show();
			$('#parent-recorder').hide();

			$.ajax({
				url: '<?="";?>',
				type: 'POST',
				data: data,
				contentType: false,
				processData: false,
				success: function(data){
					var jsonData = JSON.parse(data);
					window.uploadMessage = jsonData.message;
				},
				error: function(error){
					console.log(error);
				},
				complete: function(xhr, status){
					setTimeout(function(){
						$('#progress').hide();
						$('#parent-recorder').show();
						M.toast({html: window.uploadMessage});
						delete uploadMessage;
					}, 1000);
				}
			});
		});
	</script>
</body>
</html>

<?php
	}
?>