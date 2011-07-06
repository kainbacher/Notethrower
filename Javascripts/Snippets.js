
function askForConfirmationAndRedirect(msgLine1, msgLine2, url) {
    var msg = msgLine1;

    if (msgLine2) {
        msg += "\n\n" + msgLine2;
    }

    if (confirm(msg)) {
        document.location.href = url;
    }
}

function askForConfirmationAndExecute(msgLine1, msgLine2, url, responseHandler, asynchronous) {
    var msg = msgLine1;

    if (msgLine2) {
        msg += "\n\n" + msgLine2;
    }

    if (confirm(msg)) {
        // call url via ajax
        $.ajax({
            type: "GET",
            url: url,
            cache: false,
            dataType: "json",
            success: function (data) {
                if (responseHandler) {
                    eval(responseHandler + '(true, data)');
                }
            },
            error: function(xmlHttpRequest, textStatus, errorThrown) {
                //alert('ERROR: ' + textStatus + ' - ' + errorThrown);
                eval(responseHandler + '(false, textStatus + " - " + errorThrown)');
            },
            async: asynchronous
        });
    }
}