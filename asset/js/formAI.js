export class FormAI {
	constructor(parameter) {
		if (parameter.container) this.initForm(parameter.container)

		// greeting message
		setTimeout(() => {
			this.createMessage({
				role: 'assistant',
				content: parameter.greeting
			})
		}, 1000)
	}


	initForm = (container) => {
		this.conversation = document.getElementById('conversations')
		
		this.scrollButton = document.getElementById('scroll-button')

		this.sessionHistory = document.getElementById('sessionHistory')

		this.form = document.getElementById(container)

		this.sessionName = this.form.querySelector('input[name="sessionId"]')
		
		this.prompt = this.form.querySelector('textarea')

		this.form.querySelector('button').addEventListener('click', this.submitForm)

		this.conversation.addEventListener('scroll', this.toggleScrollButton)

		this.toggleScrollButton()

		this.prompt.addEventListener('keypress', function (e) {
			if (e.key === 'Enter') {
				// Allow new line if Shift + Enter is pressed
				if (e.shiftKey) {
					return
				}

				e.preventDefault()

				document.getElementById('submit').click() // Trigger the button click
			}
		})

		// button parameters
		document.getElementById('btnShowParameters').addEventListener('click', () => {
			document.getElementById('modelParameters').classList.toggle('active')
		})
	}


	submitForm = () => {
		// create message buble
		this.createMessage({
			role: 'user',
			content: this.prompt.value
		})


		let formData = new FormData(this.form)

		this.ajax({
			url: 'app/Router.php',
			method: 'POST',
			data: formData,
			headers: {
				Accept: 'application/json'
			},
			success: (data) => {
				console.log(data)

				const sessionID = data['sessionID'] || null

				if (sessionID) {
					this.setSessionID(sessionID)
	
					this.appendSessionHistory(sessionID, true)					
				}

				this.createMessage(data)
			},
			error: (xhr) => {
				console.error('Error:', xhr)
			},
			complete: (xhr) => {
				// console.log('Request completed')
			}
		})

		// Clear prompt
		this.prompt.value = ''
	}


	setSessionID = (sessionID) => {
		this.sessionName.value = sessionID['id']
	}


	appendSessionHistory = (sessionID, isActive) => {
		let template = this.templateSessionHistory()

		let created = this.currentTime(sessionID['created'])

		if (isActive) {
			template = template.replace('##ACTIVE##', 'active')
		} else {
			template = template.replace('##ACTIVE##', '')
		}

		template = template.replace('##ID##', sessionID['id'])
		template = template.replace('##TIME##', created)
		template = template.replace('##TITLE##', sessionID['title'])

		this.sessionHistory.innerHTML += template
	}


	createMessage = (data) => {
		let template = this.templateUserMessage()

		if (data['status'] && data['status'] == 'error') {
			alert(data['message'])

			return
		}

		if (data['role'] == 'assistant') {
			template = this.templateAiMessage()
		}

		let message = this.formatMessage(data['content']);

		template = template.replace('##CONTENT##', message)
		template = template.replace('##TIME##', this.currentTime())

		this.conversation.innerHTML += template

		this.scrollToBottomChat()
	}


	formatMessage = (message) => {
		message = message.replaceAll('\n', '<br/>')

		// for **??**
		message = message.replace(/\*\*(.*?)\*\*/g, '<h5>$1</h5>');

		// for *??*
		// message = message.replace(/\*(.*?)\*/g, '<strong>$1</strong>');

		return message
	}


	currentTime = (unixTimestamp) => {
		var date = new Date()

		if (unixTimestamp) {
			const timestamp = parseInt(unixTimestamp, 10);

			// Create a new Date object using the timestamp (multiply by 1000 to convert to milliseconds)
			date = new Date(timestamp * 1000);			
		}

		// Extract the day, month, year, hours, and minutes
		var day = String(date.getDate()).padStart(2, '0')
		var month = String(date.getMonth() + 1).padStart(2, '0') // Months are 0-indexed
		var year = date.getFullYear()
		var hours = String(date.getHours()).padStart(2, '0')
		var minutes = String(date.getMinutes()).padStart(2, '0')

		// Format the date and time
		var formattedTime = `${day}/${month}/${year} at ${hours}:${minutes}`

		return formattedTime
	}


	toggleScrollButton = () => {
		if (this.conversation.scrollTop + this.conversation.clientHeight >= this.conversation.scrollHeight - 1) {
			console.log('1')
			// this.scrollButton.style.display = 'none !important'
			this.scrollButton.classList.remove('d-block')
		} else {
			console.log('2', this.scrollButton )
			// this.scrollButton.style.display = 'block !important'
			this.scrollButton.classList.add('d-block')
		}
	}

	scrollToBottomChat = () => {
		this.conversation.scrollTo({
			top: this.conversation.scrollHeight,
			behavior: 'smooth'
		})

		this.toggleScrollButton()
	}

	templateUserMessage = () => {
		return `
<div class="message-feed right d-flex flex-row-reverse">
	<div class="avatar-user">
		<img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="" class="img-avatar">
	</div>
	<div class="media-body px-2">
		<div class="mf-content">
		##CONTENT##
		</div>
		<small class="mf-date"><i class="bi bi-clock-history"></i> ##TIME##</small>
	</div>
</div>
`
	}


	templateAiMessage = () => {
		return `
<div class="message-feed media d-flex flex-row">
	<div class="avatar-bot">
		<img src="https://bootdey.com/img/Content/avatar/avatar1.png" alt="" class="img-avatar">
	</div>
	<div class="media-body px-2">
		<div class="mf-content">
		##CONTENT##
		</div>
		<small class="mf-date"><i class="bi bi-clock-history"></i> ##TIME##</small>
	</div>
</div>
`
	}


	templateSessionHistory = () => {
		return `
<a class="list-group-item list-group-item-action d-flex justify-content-start p-3 ##ACTIVE##" href="" data-id="##ID##">
	<div class="avatar">
		<img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="" class="img-avatar">
	</div>
	<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
		<small class="username">##TIME##</small>
		<small class="text-truncate">##TITLE##</small>
	</div>
</a>
`
	}


	ajax = (options) => {
		const xhr = new XMLHttpRequest()
		const method = options.method || 'GET'
		const url = options.url || ''
		const async = options.async !== undefined ? options.async : true
		const data = options.data || null
		const headers = options.headers || {}

		xhr.open(method, url, async)

		// Set headers
		for (const header in headers) {
			if (headers.hasOwnProperty(header)) {
				xhr.setRequestHeader(header, headers[header])
			}
		}

		xhr.onreadystatechange = function () {
			if (xhr.readyState === XMLHttpRequest.DONE) {
				if (xhr.status >= 200 && xhr.status < 300) {
					const response = xhr.responseText
					if (options.success) {
						options.success(JSON.parse(response))
					}
				} else {
					if (options.error) {
						options.error(xhr)
					}
				}
				if (options.complete) {
					options.complete(xhr)
				}
			}
		}

		// Send FormData or JSON data
		if (data instanceof FormData) {
			xhr.send(data);
		} else {
			xhr.setRequestHeader('Content-Type', 'application/json')
			xhr.setRequestHeader('Accept', 'application/json')
			xhr.send(data ? JSON.stringify(data) : null)
		}
	}
}