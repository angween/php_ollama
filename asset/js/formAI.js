export class FormAI {
	constructor(parameter) {
		this.initVariables(parameter)

		// chat form
		this.initForm()

		// load conversation history
		this.sessionHistoryLoader()

		// trigger
		this.initTrigger()

		// greeting message
		this.sessionNew()

		// disclaimer
		alert('Some features are still on development.')
	}


	initVariables = (parameter) => {
		this.form = document.getElementById(parameter.frmElement)
		this.sessionName = this.form.querySelector('input[name="sessionId"]')
		this.prompt = this.form.querySelector('textarea')
		this.loader = document.getElementById('loader')
	
		this.conversation = document.getElementById('conversation')
		this.scrollButton = document.getElementById('scrollButton')
		this.sessionHistoryContainer = document.getElementById('sessionHistory')
		this.chatWindow = document.querySelector('.chat-window')

		this.btnConversationNew = document.getElementById('btnConversationNew')
		this.btnConversationDownload = document.getElementById('btnConversationDownload')
		this.btnConversationDelete = document.getElementById('btnConversationDelete')

		this.greetingMessage = parameter.greetingMessage
	}


	initTrigger = () => {
		this.btnConversationNew.addEventListener('click', this.sessionNew)
		this.btnConversationDownload.addEventListener('click', this.sessionDownload)
		this.btnConversationDelete.addEventListener('click', this.sessionDelete)

		// conversation load
		this.sessionHistoryClick()

		// scrollbutton pressed
		this.scrollButton.addEventListener('click', () => {
			this.scrollToBottomChat()
		})

		// button toggle AI options
		document.getElementById('btnShowParameters').addEventListener('click', () => {
			document.getElementById('modelParameters').classList.toggle('active')
		})

		// this.conversation.addEventListener('scroll', () => {
		this.chatWindow.addEventListener('scroll', () => {
			this.toggleScrollButtonHide();
		})

		// toggle show/hide scroll to bottom button
		this.toggleScrollButtonHide()

		// hide page loader
		this.loaderToggle()
	}


	initForm = () => {
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

		// send / submit button
		this.form.querySelector('button').addEventListener('click', this.submitForm)
	}


	loaderToggle = () => {
		this.loader.classList.toggle('d-none')
	}


	sessionHistoryLoader = async () => {
		this.sessionHistoryID = []

		// load all session history
		await this.sessionHistoryLoad()

		// list all the loaded conversation history
		this.sessionHistoryIDgetAll()
	}


	sessionNew = () => {
		this.conversation.innerHTML = ''

		// unset sessionId value
		this.sessionName.value = 'new'

		setTimeout(() => {
			this.createMessage({
				role: 'assistant',
				content: this.greetingMessage
			})
		}, 200)
	}



	sessionHistoryClick = () => {
		this.sessionHistoryContainer.addEventListener('click', (event) => {
			event.preventDefault()

			// Check if the clicked element or its parent is an <a> with the class 'list-group-item'
			const linkElement = event.target.closest('a.list-group-item');

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
					this.sessionHistoryRemoveActive()

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

		// send prompts
		const formData = new FormData(this.form)
		const formObject = {}

		// convert form to object
		formData.forEach((value, key) => {
			if (formObject[key]) {
				if (Array.isArray(formObject[key])) {
					formObject[key].push(value);
				} else {
					formObject[key] = [formObject[key], value];
				}
			} else {
				formObject[key] = value;
			}
		})

		// Convert formObject to query string
		const queryString = new URLSearchParams(formObject).toString();

		// start requestiong SSE
		this.simulationChatEvent = new EventSource(`app/Router.php?${queryString}`)

		// monitoring stream data
		this.simulationChatEvent.onmessage = (event) => {
			const response = JSON.parse(event.data)

			const status = response.status || 'error'
			const role = response.role || null
			const content = response.content || null
			const created = response.created || null

			// AI is done generating answer
			if (status == 'success') {
				// hapus simulasi chat
				let simulasiChat = document.getElementById('simulationChat')

				if (simulasiChat) {
					simulasiChat.remove();
				}

				// update message
				this.sessionIDset(response.sessionID)

				// update conversation history panel
				this.sessionHistoryAppend(response.sessionID, true, true)

				// TODO - not all the responses format have been translated
				console.log(response.content)

				// create message
				this.createMessage(response)
				
				// destroy SSE
				this.simulationChatEvent.close()
			
			// Debuging report
			} else if (status=='debug') {
				console.log('DEBUG:', response)

			// AI still working on answer
			} else {
				if (content) {
					// hapus simulasi chat
					let simulasiChat = document.getElementById('simulationChat')

					if (simulasiChat) {
						simulasiChat.querySelector('.mf-content .information').innerHTML = content
					} else {
						// create simulation message
						this.simulationReplyShows(content, created)
					}

				}
			}
		}

		// on error
		this.simulationChatEvent.onerror = () => {
			let simulasiChat = document.getElementById('simulationChat')

			if (simulasiChat) {
				simulasiChat.querySelector('.mf-content').innerHTML = "I can not get the answer. Please try again."
			} else {
				this.createMessage({
					role: 'assistant',
					content: "I can't get the answer. Please try again."
				})
			}

			// destroy SSE
			this.simulationChatEvent.close()
		}


		// Clear prompt
		this.prompt.value = ''
	}


	simulationReplyShows = (content, created) => {
		let simulationChat = this.templateAiMessage() 

		let simulationElement = `
			<span class="spinner-grow spinner-grow-sm" style="height:12px;width:12px"></span>
  			<span role="status" class="information" style="color: var(--bs-gray-300)">${content}</span>`
		
		simulationChat = simulationChat.replace('#ID#', 'id="simulationChat"')
		simulationChat = simulationChat.replace('##CONTENT##', simulationElement)
		simulationChat = simulationChat.replace('##TIME##', this.currentTime(created))

		this.conversation.innerHTML += simulationChat

		this.simulationChatExists = true
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
			this.sessionHistoryRemoveActive()

			template = template.replace('##ACTIVE##', 'active')
		} else {
			template = template.replace('##ACTIVE##', '')
		}

		template = template.replace('##ID##', sessionID['id'])
		template = template.replace('##TIME##', created)
		template = template.replace('##TITLE##', sessionID['title'])


		if (!onTop) {
			this.sessionHistoryContainer.innerHTML += template
		} else {
			console.log('ontop')
			this.sessionHistoryContainer.insertAdjacentHTML('beforebegin', template)
		}

		this.sessionHistoryID.push(sessionID['id'])
	}

	sessionHistoryRemoveActive = () => {
		this.sessionHistoryContainer.querySelectorAll('a.list-group-item').forEach(link => {
			link.classList.remove('active')
		})
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
<div class="message-feed media d-flex flex-row" #ID#>
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
		return new Promise((resolve, reject) => {
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

						const jsonResponse = JSON.parse(response)

						if (options.success) {
							options.success(jsonResponse)
						}

						resolve(jsonResponse)
					} else {
						if (options.error) {
							options.error(xhr)
						}

						reject(xhr)
					}
					if (options.complete) {
						options.complete(xhr)
					}
				}
			}

			// Send FormData or JSON data
			if (data instanceof FormData) {
				xhr.setRequestHeader('Accept', 'application/json')

				xhr.send(data)
			} else {
				xhr.setRequestHeader('Content-Type', 'application/json')

				xhr.setRequestHeader('Accept', 'application/json')

				xhr.send(JSON.stringify(data))
				//xhr.send(data ? JSON.stringify(data) : null)
			}
		})
	}

	

	sessionHistoryIDgetAll = () => {
		const anchors = document.querySelectorAll('#sessionHistory a')

		// Extract the data-id attributes
		this.sessionHistoryID = Array.from(anchors).map(anchor => anchor.getAttribute('data-id'))
	}


	sessionHistoryLoad = async () => {
		await this.ajax({
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

				this.sessionHistoryContainer.innerHTML = ""

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

		return
	}


	sessionDownload = () => {
		// Get the content of the HTML element
		const title = this.sessionName.value
		const messages = this.conversation.querySelectorAll('.message-feed')

		let textContent = ''

		// convert the conversation to text
		messages.forEach(message => {
			const isUser = message.classList.contains('right')
			const userType = isUser ? 'USER' : 'BOT'

			const dateElement = message.querySelector('.mf-date')
			const dateText = dateElement ? dateElement.textContent.trim() : ''

			const contentElement = message.querySelector('.mf-content')
			let contentText = contentElement ? contentElement.textContent.replace(/\s+/g, ' ').trim() : ''

			textContent += `(${dateText}) ${userType}: ${contentText}\n\n`
		})


		// Create a Blob from the content
		const blob = new Blob([textContent], { type: 'text/plain' })

		// Create a download link for the Blob
		const link = document.createElement('a')

		link.href = URL.createObjectURL(blob)

		link.download = `${title}.txt` // Specify the file name

		// Programmatically click the download link to trigger the download
		link.click()

		// Revoke the object URL after the download
		URL.revokeObjectURL(link.href)
	}


	sessionDelete = () => {
		// get current session ID
		const sessionID = this.sessionName.value

		if (sessionID == 'new') return 

		if (!confirm('Are you sure you want to delete this conversation?')) return

		this.ajax({
			url: 'app/Router.php',
			method: 'POST',
			data: {
				path: 'ollama/deleteSession',
				sessionID: sessionID
			},
			headers: {
				Accept: 'application/json'
			},
			success: (data) => {
				if (data.status != 'success') {
					alert(data.message)

					return
				}

				alert('Coversation deleted.')

				// TODO
				this.sessionHistoryLoader()
				
				this.sessionNew()
			},
			error: (xhr) => {
				console.error('Error:', xhr)
			}
		})
	}
}