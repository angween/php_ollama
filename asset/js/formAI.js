export class FormAI {
	constructor(parameter) {
		if (parameter.container) this.initForm(parameter.container)
	}

	initForm = (container) => {
		this.conversation = document.getElementById('conversations')
		
		this.form = document.getElementById(container)
   
		this.prompt = this.form.querySelector('textarea')

		this.form.querySelector('button').addEventListener('click', this.submitForm)
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


	createMessage = (data) => {
		console.log( data )
		let template = this.templateUserMessage()

		if (data['role'] == 'assistant') {
			template = this.templateAiMessage()
		}

		let message = this.formatMessage(data['content']);

		template = template.replace('##CONTENT##', message)
		template = template.replace('##TIME##', this.currentTime())

		this.conversation.innerHTML += template
	}


	formatMessage = (message) => {
		message = message.replaceAll('\n', '<br/>')

		// for **??**
		message = message.replace(/\*\*(.*?)\*\*/g, '<h4>$1</h4>');

		// for *??*
		// message = message.replace(/\*(.*?)\*/g, '<strong>$1</strong>');

		return message
	}


	currentTime = () => {
		var now = new Date()

		// Extract the day, month, year, hours, and minutes
		var day = String(now.getDate()).padStart(2, '0')
		var month = String(now.getMonth() + 1).padStart(2, '0') // Months are 0-indexed
		var year = now.getFullYear()
		var hours = String(now.getHours()).padStart(2, '0')
		var minutes = String(now.getMinutes()).padStart(2, '0')
	
		// Format the date and time
		var formattedTime = `${day}/${month}/${year} at ${hours}:${minutes}`
	
		return formattedTime
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