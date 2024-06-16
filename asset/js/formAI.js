export class FormAI {
    constructor(parameter) {
        if (parameter.container) this.initForm(parameter.container)
    }

    initForm = (container) => {
        this.form = document.getElementById(container)

        this.form.querySelector('button').addEventListener('click', this.submitForm)
    }

    submitForm = () => {
        let formData = new FormData(this.form)

        this.ajax({
            url: 'app/router.php',
            method: 'POST',
            data: formData,
            success: function (data) {
                console.log('Success:', data)
            },
            error: function (xhr) {
                console.error('Error:', xhr)
            },
            complete: function (xhr) {
                console.log('Request completed')
            }
        })

    }

    ajax = (options) => {
        const xhr = new XMLHttpRequest();
        const method = options.method || 'GET';
        const url = options.url || '';
        const async = options.async !== undefined ? options.async : true;
        const data = options.data || null;
        const headers = options.headers || {};

        xhr.open(method, url, async);

        // Set headers
        for (const header in headers) {
            if (headers.hasOwnProperty(header)) {
                xhr.setRequestHeader(header, headers[header]);
            }
        }

        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = xhr.responseText;
                    if (options.success) {
                        options.success(JSON.parse(response));
                    }
                } else {
                    if (options.error) {
                        options.error(xhr);
                    }
                }
                if (options.complete) {
                    options.complete(xhr);
                }
            }
        };

        // Send FormData or JSON data
        if (data instanceof FormData) {
            xhr.send(data);
        } else {
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.send(data ? JSON.stringify(data) : null);
        }
    }
}