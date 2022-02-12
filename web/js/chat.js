var conn = new WebSocket('ws://localhost:' + chatPort);
conn.onopen = function (e) {
    console.log("Connection established!");
};

conn.onmessage = function (e) {
    var message = '<div class="row message-bubble">' +
        '<p class="text-muted">Someone</p>' +
        '<p>' + e.data + '</p>'+
        '</div>';
    $('#chat-body > .container').append(message);
};

function sendMessage() {
    var textInput = $('#textHere');
    var value = textInput.val();
    var message = '<div class="row message-bubble">' +
        '<p class="text-muted">You</p>' +
        '<p>' + value + '</p>'+
        '</div>';

    $('#chat-body > .container').append(message);

    conn.send(value);

    textInput.val('');
}

