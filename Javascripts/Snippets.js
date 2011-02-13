
function askForConfirmationAndRedirect(msgLine1, msgLine2, url) {
    var msg = msgLine1;

    if (msgLine2) {
        msg += "\n\n" + msgLine2;
    }

    if (confirm(msg)) {
        document.location.href = url;
    }
}