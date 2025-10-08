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

            const loggedIn = outsetaIsLoggedIn();
            if (!loggedIn) {
				console.log("not logged in, redirecting to login");
                window.location.href = "/planning-hub/signup";
				return;
            }

            const outsetaToken = Outseta.getAccessToken();
            const file = container.getAttribute("data-al_file");
            const data = {outsetaToken: outsetaToken, file: file};

            const requestParams = {
                method: 'POST',
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            };

            
            const url = "/wp-admin/admin-ajax.php?action=ALFRESCO_PH_DOWNLOAD";
            fetch(url, requestParams)
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`ajax call failed: ${response.status}`);
                    }
                    buttonTrigger.style.pointerEvents = "auto";
                    return response.blob();
                })
                .then((responseObject) => {
                    const fileObject = new Blob([responseObject], { type: "application/pdf" });
                    const fileUrl = URL.createObjectURL(fileObject);
                    let link = document.createElement('a');
                    link.href = fileUrl;
                    link.download = file;
                    link.click();
                })
                .catch((error) => {
                    console.error(error.message);
                    // show an error message
                    
                    buttonTrigger.style.pointerEvents = "auto";
                });
        });
    });
}

function outsetaIsLoggedIn() {
    let outsetaToken = Outseta.getAccessToken();
    if (outsetaToken === null) {
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