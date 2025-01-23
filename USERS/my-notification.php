<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Journaly - My Notifications</title>
  <link rel="stylesheet" href="Ustyle.css" />
  <link href="https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="main-content">
      <div class="notification-panel">
        <div class="nav">
          <h2>My Notifications <span id="unread-count"></span></h2>
          <button id="maar">Mark all as read</button>
        </div>
        <!-- Dynamic notifications will be appended here -->
        <div id="notificationContainer"></div>
      </div>
    </main>

    <script>
document.addEventListener("DOMContentLoaded", function () {
  const notificationContainer = document.getElementById("notificationContainer");
  const unreadCountElement = document.getElementById("unread-count");
  const displayedNotificationIds = new Set(); // To track displayed notifications

  // Fetch notifications for the user
  function fetchNotifications() {
  fetch('fetch-notification.php')
    .then((response) => response.json())
    .then((data) => {
      if (data.length === 0) {
        notificationContainer.innerHTML = `<p>No new notifications</p>`;
        unreadCountElement.textContent = "0";
        return;
      }

      // Sort notifications by descending timestamp
      data.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

      notificationContainer.innerHTML = "";
      let unreadCount = 0;

      // Render notifications
      data.forEach((notification) => {
        const isUnread = parseInt(notification.is_read) === 0;
        if (isUnread) unreadCount++;

        const notificationHTML = `
          <div class="notification ${isUnread ? "unread" : ""}">
            <div class="not">
              <a href="#" class="notification-link" 
                 data-notification-id="${notification.id}" 
                 data-submission-id="${notification.submission_id}" 
                 data-message="${notification.message}">
                <h2>${notification.message} ${isUnread ? '<span class="reddot"></span>' : ''}</h2>
              </a>
              <p>${new Date(notification.timestamp).toLocaleString()}</p>
            </div>
          </div>
        `;

        notificationContainer.innerHTML += notificationHTML;
      });

      // Update the unread count
      unreadCountElement.textContent = unreadCount;

      // Add event listeners to notification links
      attachNotificationClickListeners();
    })
    .catch((error) => console.error("Error fetching notifications:", error));
}


      // Add event listener to mark notification as read and redirect
      function attachNotificationClickListeners() {
      const notificationLinks = document.querySelectorAll('.notification-link');
      notificationLinks.forEach(link => {
        link.addEventListener('click', function (event) {
          event.preventDefault();  // Prevent default action of the link
          const notificationId = this.dataset.notificationId;
          const submissionId = this.dataset.submissionId;
          const message = this.dataset.message; // Get the notification message

          if (!submissionId) {
            console.error("Submission ID is missing");
            return;
          }

          // Determine the redirect URL based on the notification message
          let redirectUrl;
          if (message.includes("assigned to be the reviewer")) {
            redirectUrl = `create-review.php?id=${submissionId}`; // Redirect to create-review.php
          } else {
            redirectUrl = `view-submission.php?id=${submissionId}`; // Default redirect
          }

          console.log("Redirecting to:", redirectUrl); // Log the URL

          // Mark the notification as read and then redirect
          markNotificationAsRead(notificationId, redirectUrl);
        });
      });
    }

  // Mark a specific notification as read
  function markNotificationAsRead(notificationId, redirectUrl) {
    fetch('mark-notification-read.php', {
      method: "POST",
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(() => {
      console.log("Redirecting to:", redirectUrl);  // Log the URL before redirect
      window.location.href = redirectUrl;  // Redirect to the determined page
    })
    .catch(error => console.error("Error marking notification as read:", error));
  }

  // Mark all notifications as read
  document.getElementById("maar").addEventListener("click", function () {
    fetch('mark-notification-read.php', {
      method: "POST",
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ mark_all: true })
    })
      .then(response => response.json())
      .then(() => {
        location.reload(); // Reload the page after marking as read
      })
      .catch((error) => console.error("Error marking notifications as read:", error));
  });

  fetchNotifications();

  setInterval(fetchNotifications, 5000);
});

</script>

</body>
</html>
