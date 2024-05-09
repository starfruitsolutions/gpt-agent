<div id="container">
    <div id="messages"></div>
    <form id="messageForm">
        <input id="message" type="text" name="message" autocomplete="off">
        <input id="submit" type="submit" value="Send">
        <form>
</div>
<script>
    let messages = [{
        from: 'Agent',
        content: 'Hello, I am your Agent. I am here to help.'
    }, ];
    const messageForm = document.getElementById("messageForm")
    messageForm.addEventListener("submit", (e) => {
        e.preventDefault()

        // get the message from thee input
        const messageInput = document.getElementById('message')
        let message = messageInput.value
        messageInput.value = ''

        // add the message and render
        messages.push({
            from: 'You',
            content: message
        })
        renderMessages()

        // send the message to Agent
        fetch('agent.php', {
            method: "POST",
            body: JSON.stringify({
                message: message
            }),
            headers: {
                'Content-Type': 'application/json; charset=UTF-8'
            },
        }).then((response) => response.json()).then((data) => {
            console.log(data)
            messages.push({
                from: 'Agent',
                content: data.message
            })
            renderMessages()
        })
    })
    const messageDiv = document.getElementById('messages')

    function renderMessages() {
        messageDiv.innerHTML = ''
        for (const message of messages) {
            messageDiv.innerHTML += `<div class="message ${message.from === 'Agent' ? 'message-left' : 'message-right'}">${message.content}</div>`
        }
        messageDiv.scrollTop = messageDiv.scrollHeight;

    }

    renderMessages()
</script>

<style>
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: 500;
        color: white;
        background-color: #121212;
    }

    #container {
        height: 80%;
        width: 50%;
        padding: 15px;
        border-radius: 5px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background-color: #212121;
    }

    #messages {
        height: 80%;
        padding: 15px;
        overflow-y: auto;
        background-color: #121212;
    }

    #messageForm {
        margin: 0;
        height: 10%;
        display: flex;
        justify-content: space-between;
        color: white;
    }

    #message {
        width: 80%;
        height: 100%;
        padding: 15px;
        background-color: #121212;
        color: white;
    }

    #submit {
        width: 20%;
        height: 100%;
        background-color: #212121;
        color: white;
        font-size: 1.1em;
    }

    .message {
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 10px;
        width: 50%;
    }

    .message-left {
        float: left;
        background-color: blueviolet;
    }

    .message-right {
        float: right;
        background-color: limegreen;
    }

    .message img {
        max-width: 100%;
    }
</style>
