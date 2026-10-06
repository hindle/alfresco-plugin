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

      // Create a dialog for use in the event of an error
      const errorDialog = document.createElement('dialog');
      document.body.appendChild(errorDialog);
      errorDialog.style = "border: solid #F99584 5px;";
      errorDialog.innerHTML = '<button style="display: inline-block; padding: 12px 24px; background-color: #F99584; color: #FFF; text-decoration: none; font-family: Roboto; font-weight: 500; font-size: 13px; letter-spacing: 2.5px; line-height: 1; text-transform: uppercase; border: none;" class="close-btn">Close</button>';
      errorDialog.querySelector('.close-btn').addEventListener('click', () => {
        errorDialog.close();
      });

      const loggedIn = outsetaIsLoggedIn();
      if (!loggedIn) {
        console.log("not logged in, redirecting to login");
        window.location.href = "/planning-hub/signup";
        return;
      }

      class ForbiddenError extends Error {
        constructor(message) {
          super(message);
          this.name = 'ForbiddenError';
        }
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
          if (response.status === 403) {
            throw new ForbiddenError('Download limit exceeded.');
          } else if (!response.ok) {
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

          if (error instanceof ForbiddenError) {
            errorDialog.insertAdjacentHTML('afterbegin', '<p>You have reached your download limit as per our <a href="/terms-conditions/" style="color: #F99584; text-decoration: underline;">fair use policy</a>.</p><p>You will be able to download files again in 24 hours.</p><p>Repeated breaches of this policy may result in your subscription being cancelled.</p>');
            errorDialog.showModal();
          } else {
            errorDialog.insertAdjacentHTML('afterbegin', '<p>An error has occurred when downloading the file. Please try again.</p><p>If this issue persists, please <a href="/contact" style="color: #F99584; text-decoration: underline; hover: color: #F99584;">contact us</a>.</p>');
            errorDialog.showModal();
          }

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
