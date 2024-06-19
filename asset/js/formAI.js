export class FormAI {
	constructor(parameter) {
		if (parameter.container) this.initForm(parameter.container)

		// load all session history
		this.sessionIDloadAll()

		// list all the loaded conversation history
		this.sessionHistoryIDgetAll()

		// greeting message
		this.sessionNew(parameter.greeting)

		alert('Some features still on development.')
	}


	initForm = (container) => {
		this.form = document.getElementById(container)
		this.sessionName = this.form.querySelector('input[name="sessionId"]')
		this.prompt = this.form.querySelector('textarea')
		this.loader = document.getElementById('loader')

		this.conversation = document.getElementById('conversation')
		this.scrollButton = document.getElementById('scrollButton')
		this.sessionHistory = document.getElementById('sessionHistory')
		this.chatWindow = document.querySelector('.chat-window')


		// button parameters
		document.getElementById('btnShowParameters').addEventListener('click', () => {
			document.getElementById('modelParameters').classList.toggle('active')
		})

		// session load
		this.triggerSessionLoad()

		// prompt entered
		this.prompt.addEventListener('keypress', (e) => {
			if (e.key === 'Enter') {
				// Allow new line if Shift + Enter is pressed
				if (e.shiftKey) {
					return
				}

				e.preventDefault()

				document.getElementById('submit').click() // Trigger the button click
			}
		})


		// scrollbutton pressed
		this.scrollButton.addEventListener('click', () => {
			this.scrollToBottomChat()
		})

		this.form.querySelector('button').addEventListener('click', this.submitForm)

		// this.conversation.addEventListener('scroll', () => {
		this.chatWindow.addEventListener('scroll', () => {
			this.toggleScrollButtonHide();
		})

		this.toggleScrollButtonHide()

		// hide loader
		this.loaderToggle()
	}


	sessionNew = (greeting) => {
		this.conversation.innerHTML = ''

		// unset sessionId value
		this.sessionName.value = 'new'

		setTimeout(() => {
			this.createMessage({
				role: 'assistant',
				content: greeting
			})
		}, 200)
	}


	loaderToggle = () => {
		this.loader.classList.toggle('d-none')
	}


	triggerSessionLoad = () => {
		this.sessionHistory.addEventListener('click', (event) => {
			event.preventDefault()

			const linkElement = event.target.closest('a.list-group-item');

			// Check if the clicked element or its parent is an <a> with the class 'list-group-item'
			if (!linkElement) return

			// Get the data-id attribute value
			const dataId = linkElement.getAttribute('data-id')

			// show loader
			this.loaderToggle()

			// Request the session data
			this.ajax({
				url: 'app/Router.php',
				method: 'POST',
				data: {
					path: 'ollama/loadSessionId',
					sessionID: dataId
				},
				success: (respon) => {
					if (respon.status != 'success') {
						alert(respon.message)
					}

					// empty the conversation
					this.conversation.innerHTML = ''

					// render the conversation
					respon.data.forEach(chat => {
						this.createMessage(chat)
					})

					// hide loader
					this.loaderToggle()

					// scroll conversation to bottom
					setTimeout(() => {
						this.scrollToBottomChat()
					}, 100);


					// remove active from all a.list-group-item
					this.sessionHistory.querySelectorAll('a.list-group-item').forEach(link => {
						link.classList.remove('active')
					})

					// set linkelement became active
					linkElement.classList.add('active')

					// set sessionID to the prompt
					this.sessionIDset({id:dataId})
				}
			})

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
				console.log(data) // TODO not all the response's format have been translated

				const sessionID = data['sessionID'] || null

				if (sessionID) {
					this.sessionIDset(sessionID)

					this.sessionHistoryAppend(sessionID, true, true)
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


	sessionIDset = (sessionID) => {
		this.sessionName.value = sessionID['id']
	}


	sessionHistoryAppend = (sessionID, isActive, onTop) => {
		// if sessionID is in sessionHistoryID then return
		if (this.sessionHistoryID.includes(sessionID['id'])) return

		// or append in left-side
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


		if (!onTop) {
			this.sessionHistory.innerHTML += template
		} else {
			console.log('ontop')
			this.sessionHistory.insertAdjacentHTML('beforebegin', template)
		}

		this.sessionHistoryID.push(sessionID['id'])
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
		message = message.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

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


	toggleScrollButtonHide = () => {
        // Check if the conversation is scrolled to the bottom
		// 50 is total padding height
        const isAtBottom = this.chatWindow.scrollHeight - this.chatWindow.scrollTop <= this.chatWindow.clientHeight + 50;

		if (isAtBottom) {
            this.scrollButton.style.display = 'none'; // Hide scroll button
        } else {
            this.scrollButton.style.display = 'block'; // Show scroll button
        }
	}


	scrollToBottomChat = () => {
		const lastMessage = this.conversation.lastElementChild

		if (lastMessage) {
			lastMessage.scrollIntoView({ behavior: 'smooth', block: 'end' });
		}

		this.toggleScrollButtonHide()
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
<a class="load-session list-group-item list-group-item-action d-flex justify-content-start p-3 ##ACTIVE##" href="" data-id="##ID##">
	<div class="avatar">
		<img src="https://bootdey.com/img/Content/avatar/avatar2.png" alt="" class="img-avatar">
	</div>
	<div class="media-body px-2 ps-2 d-flex flex-column justify-content-between">
		<small class="username">##TIME##</small>
		<small class="text-truncate fw-light">##TITLE##</small>
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


	sessionHistoryIDgetAll = () => {
		const anchors = document.querySelectorAll('#sessionHistory a');

		// Extract the data-id attributes
		this.sessionHistoryID = Array.from(anchors).map(anchor => anchor.getAttribute('data-id'));
	}


	sessionIDloadAll = () => {
		// return

		this.ajax({
			url: 'app/Router.php',
			method: 'POST',
			data: {
				path: 'ollama/loadSessionHistory',
			},
			headers: {
				Accept: 'application/json'
			},
			success: (data) => {
				if (data.status != 'success') return

				this.sessionHistory.innerHTML = ""

				data.rows.reverse().forEach(row => {
					this.sessionHistoryAppend(row, false)
				})
			},
			error: (xhr) => {
				console.error('Error:', xhr)
			},
			complete: (xhr) => {
				// console.log('Request completed')
			}
		})

	}
}