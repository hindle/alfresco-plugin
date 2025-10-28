document.addEventListener('DOMContentLoaded', function() {
    phUnitSetup();
}, false);

function phUnitSetup() {
    const downloadButtonContainers = document.querySelectorAll("div[data-al_type='ph_download']");

    downloadButtonContainers.forEach(container => {
        const buttonTrigger = container.querySelector("a");
        buttonTrigger.addEventListener("click", (event) => {
            event.preventDefault();

            buttonTrigger.style.pointerEvents = "none";
            // It's not pretty, but it works most of the time.
            // Done this way because the error container has not had the id consistently applied
            // for each of the pages and rows.
            // Sometimes it pulls the document instead of the parent document.
            const parentContainer = event.target.parentElement.parentElement.parentElement.parentElement.parentElement;
			const errorContainer = parentContainer.children[2].children[0];
			errorContainer.innerHTML = '';

            const loggedIn = outsetaIsLoggedIn();
            if (!loggedIn) {
				console.log("not logged in, redirecting to login");
                window.location.href = "/planning-hub/signup";
				return;
            }

            const file = container.getAttribute("data-al_file");
            const url = "/wp-json/alfresco/v1/download?file=" + file;

            const requestParams = {
                method: 'GET',
                headers: {
                    "Content-Type": "application/json"
                },
            };

            fetch(url, requestParams)
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`ajax call failed: ${response.status}`);
                    }
                    buttonTrigger.style.pointerEvents = "auto";
                    return response.json();
                })
                .then((data) => {
                    const fileUrl = atob(data.file);
                    let link = document.createElement('a');
                    link.href = fileUrl;
                    link.download = file;
                    link.click();

                    gtag('event', 'file_download', {
					file_name: file,
				});

                })
                .catch((error) => {
                    console.error(error.message);
					errorContainer.innerHTML = '<p style="color: red; background: white; padding: 5px;">An error occurred, the Alfresco Hub team have been notified and will look into this.</p>';
                    buttonTrigger.style.pointerEvents = "auto";
                });
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