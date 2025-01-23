<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Journaly - My Chats</title>
    <link rel="stylesheet" href="Estyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="main-content" style="margin-top: 30px;">
    <h2>My Chats</h2>
    <div class="chat-container">
        <div class="chat-history">
            <div class="search-bar">
                <input type="text" id="searchUser" placeholder="Search by name or email..." />
                <button id="closeSearch" style="display: none;">×</button>
            </div>
            <ul>
                <!-- Chat history will be populated here -->
            </ul>
        </div>

        <!-- Chat section -->
        <div class="chat-section">
            <div class="chat-header">
                <!-- <span class="avatar"></span> -->
                <span class="chat-user-name">Select a user</span>
            </div>

            <div class="message-area">
                <!-- Messages will be populated here -->
            </div>

            <div class="input-area">
                <input type="text" id="chatInput" placeholder="Type a message..." />
                <button type="submit" id="sendButton" class='bx bxs-send'></button>
            </div>
        </div>
    </div>
</main>

<script>
    const chatItems = document.querySelector('.chat-history ul');
    const chatUserName = document.querySelector('.chat-user-name');
    const messageArea = document.querySelector('.message-area');
    const chatInput = document.getElementById('chatInput');
    const sendButton = document.getElementById('sendButton');

    function timeAgo(timestamp) {
    const now = new Date();
    const messageTime = new Date(timestamp);
    const timeDiff = now - messageTime; // Time difference in milliseconds

    const seconds = Math.floor(timeDiff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 0) {
        return `${days} day${days > 1 ? 's' : ''} ago`;
    } else if (hours > 0) {
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    } else if (minutes > 0) {
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    } else {
        return `${seconds} second${seconds > 1 ? 's' : ''} ago`;
    }
    }

    // Function to fetch and populate chat history
    function fetchChatHistory() {
        fetch('chat-history.php')
        .then(response => response.json())
        .then(users => {
            console.log('Fetched users:', users); // Log all fetched users
            chatItems.innerHTML = ''; // Clear existing list items

            users.forEach(user => {
                console.log('Processing user:', user); // Log each user being processed
                const li = document.createElement('li');
                li.dataset.user = user.username;

                const timeDifference = timeAgo(user.last_message_timestamp);
                const avatarUrl = user.profile_picture ? `${user.profile_picture}` : '../uploads/default.png';

                // Add the `unread` class if the user has unread messages
                if (user.has_unread) {
                    li.classList.add('unread');
                    console.log(`Unread messages for user: ${user.username}`);
                }

                li.innerHTML = `
                    <img class="avatar" src="${avatarUrl}" alt="${user.username}'s avatar" />
                    ${user.username} <span class="time">${timeDifference}</span>
                `;
                li.addEventListener('click', () => loadConversation(user.username));
                chatItems.appendChild(li);
            });
        })
        .catch(error => {
            console.error('Error fetching chat history:', error);
        });
    }

    // On page load, fetch chat history and set an interval to refresh every 5 seconds
    window.addEventListener('DOMContentLoaded', () => {
        fetchChatHistory(); // Initial fetch

        // Set an interval to refresh the chat history every 5 seconds
        setInterval(() => {
            fetchChatHistory();
        }, 5000); // 5000 milliseconds = 5 seconds
    });

    const searchUserInput = document.getElementById('searchUser');
    const closeSearchButton = document.getElementById('closeSearch');

    // Event listener for the search input field
    searchUserInput.addEventListener('input', () => {
        const query = searchUserInput.value.trim();

        if (query === '') {
            closeSearchButton.style.display = 'none'; // Hide close button if input is empty
            fetchChatHistory(); // Reset to chat history
            return;
        }

        closeSearchButton.addEventListener('click', () => {
        searchUserInput.value = ''; // Clear the search input field
        closeSearchButton.style.display = 'none'; // Hide the close button
        fetchChatHistory(); // Reset the chat list to the original chat history
    });

        closeSearchButton.style.display = 'inline'; // Show close button if input is not empty

        // Fetch search results
        fetch(`search-user.php?query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(users => {
                chatItems.innerHTML = ''; // Clear current chat list

                // Populate search results
                users.forEach(user => {
                    const li = document.createElement('li');
                    li.dataset.user = user.username;

                    const avatarUrl = user.profile_picture;

                    li.innerHTML = `
                        <img class="avatar" src="${avatarUrl}" alt="${user.username}'s avatar" />
                        ${user.username} (${user.email})
                    `;
                    li.addEventListener('click', () => loadConversation(user.username));
                    chatItems.appendChild(li);
                });
            })
            .catch(error => {
                console.error('Error fetching search results:', error);
            });
    });


    function loadConversation(user) {
        chatUserName.textContent = user;

        // Mark messages as read in the backend
        fetch(`mark-as-read.php?user=${user}`)
            .then(response => response.text())
            .then(responseText => console.log(responseText))
            .then(() => {
                // Remove the `unread` class from the clicked user
                const chatItem = document.querySelector(`li[data-user="${user}"]`);
                if (chatItem) chatItem.classList.remove('unread');
            })
            .catch(error => console.error('Error marking messages as read:', error));

        // Fetch messages for the selected user
        fetch(`get-conversation.php?user=${user}`)
            .then(response => response.json())
            .then(messages => {
                renderMessages(messages);
            })
            .catch(error => console.error('Error fetching conversation:', error));
    }


    // Function to render messages in the message area
    function renderMessages(messages) {
        messageArea.innerHTML = ''; // Clear existing messages

        // Render each message
        messages.forEach(message => {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message', message.type);

            const textDiv = document.createElement('div');
            textDiv.classList.add('text');
            textDiv.innerText = message.text;

            const timeDiv = document.createElement('div');
            timeDiv.classList.add('time');
            timeDiv.innerText = message.time;

            messageDiv.appendChild(textDiv);
            messageDiv.appendChild(timeDiv);

            messageArea.appendChild(messageDiv);
        });
    }

    sendButton.addEventListener('click', () => {
    const messageText = chatInput.value;
    const receiverUser = chatUserName.textContent; // Get the selected user's username
    if (messageText.trim() === '') return; // Do nothing if the input is empty

    const currentTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    // Create a new message div (locally)
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message', 'sent');

    const textDiv = document.createElement('div');
    textDiv.classList.add('text');
    textDiv.innerText = messageText;

    const timeDiv = document.createElement('div');
    timeDiv.classList.add('time');
    timeDiv.innerText = currentTime;

    messageDiv.appendChild(textDiv);
    messageDiv.appendChild(timeDiv);

    // Append the message to the message area
    messageArea.appendChild(messageDiv);

    // Clear the input field
    chatInput.value = '';

    // Scroll to the bottom of the message area
    messageArea.scrollTop = messageArea.scrollHeight;

    // Send the message to the server using Fetch
    fetch('send-message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            message: messageText,
            receiver_user: receiverUser,
        }),
    })
    .then(response => response.text())
    .then(response => {
        console.log(response); // Log the response from the server
    })
    .catch(error => {
        console.error('Error sending message:', error);
    });
});
</script>

</body>
</html>
