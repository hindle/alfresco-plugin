document.addEventListener('DOMContentLoaded', function() {
    cpdUnitSetup();
}, false);

function cpdUnitSetup() {
  const videoButton = document.getElementById("cpd-content__video-button");
  const fileButton = document.getElementById("cpd-content__file-button");

  videoButton.addEventListener("click", (event) => {
    event.preventDefault();
    videoButton.style.pointerEvents = "none";

    const loggedIn = outsetaIsLoggedIn();
    if (!loggedIn) {
      console.log("not logged in, redirecting to login");
      window.location.href = "/planning-hub/signup";
      return;
    }

    const videoContainer = document.querySelector(".cpd-content__video-container");
    const videoId = videoContainer.getAttribute("data-video-id");
    const url = "/wp-json/alfresco/v1/video?video_id=" + videoId;

    const requestParams = {
      method: 'GET',
      headers: {
        "Content-Type": "application/json"
      },
    };

    fetch(url, requestParams)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`failed to get video source: ${response.status}`);
        }

        videoButton.style.pointerEvents = "auto";
        return response.json();
      })
      .then((data) => {
        const videoContainer = document.querySelector(".cpd-content__video-container");
        const imageContainer = document.querySelector(".cpd-content__image-container");

        const player = videojs.getPlayer('cpd-content__video');
        player.src(data.video_url);

        imageContainer.style.display = "none";
        videoContainer.style.display = "grid";

        player.play();
      })
      .catch((error) => {
        console.error("Error fetching video source:", error);
        videoButton.style.pointerEvents = "auto";

        const videoErrorDialog = document.getElementById("cpd-content__video-error");
        if (videoErrorDialog) {
          videoErrorDialog.showModal();
        }
      });
  });

  fileButton.addEventListener("click", (event) => {
    event.preventDefault();
    fileButton.style.pointerEvents = "none";

    const loggedIn = outsetaIsLoggedIn();
    if (!loggedIn) {
      console.log("not logged in, redirecting to login");
      window.location.href = "/planning-hub/signup";
      return;
    }

    const fileId = fileButton.closest("[data-file-id]").getAttribute("data-file-id");
    const url = "/wp-json/alfresco/v1/download?file=" + fileId;

    const requestParams = {
      method: 'GET',
      headers: {
        "Content-Type": "application/json"
      },
    };

    fetch(url, requestParams)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`failed to get video source: ${response.status}`);
        }

        fileButton.style.pointerEvents = "auto";
        return response.json();
      })
      .then((data) => {
        const fileUrl = atob(data.file);
        let link = document.createElement('a');
        link.href = fileUrl;
        link.download = fileId;
        link.click();

        gtag('event', 'file_download', {
          file_name: fileId,
        });
      })
      .catch((error) => {
        console.error("Error fetching file:", error);
        fileButton.style.pointerEvents = "auto";

        const fileErrorDialog = document.getElementById("cpd-content__file-error");
        if (fileErrorDialog) {
          fileErrorDialog.showModal();
        }
      });
  });
}

function outsetaIsLoggedIn() {
  let outsetaToken = Outseta.getAccessToken();
  if (!outsetaToken || outsetaToken === null) {
    return false;
  }

  let base64Components = outsetaToken.split(".")[1];
	let base64 = base64Components.replace(/-/g, '+').replace(/_/g, '/');
	let jsonPayload =  decodeURIComponent(atob(base64).split('').map(function(c) {
	  return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
	}).join(''));

	payload = JSON.parse(jsonPayload)

	const timeNow = Math.floor(Date.now() / 1000);
	const tokenExpiry = payload.exp;

	if (tokenExpiry <= timeNow) {
	  return false;
	}

	return true;
}
