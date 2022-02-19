var conn;
var identifier;
var forceNoUpdate = false;
var isUpdating = false;

$(document).ready(function () {
    identifier = $('.chat-app').data('identifier');
    enterChat();
    resizeChat();
    $('.room').on('click', function () {
        enterRoom($(this))
    });

    $(document).on('keydown', function (e) {
        if (e.keyCode == 13) {
            sendMessage();
            return false;
        }
    });
})

function enterRoom(elem) {
    $('.room.active').removeClass('active');
    elem.find('.badge').text('');
    elem.addClass('active');
    isUpdating = false;
    forceNoUpdate = false;
    var roomId = elem.data('id');
    $('.chat-app').data('room', roomId);
    var page = elem.data('page');
    $.ajax({
        url: '/chat/room',
        data: {
            id: roomId,
            identifier: identifier,
            page: page
        },
        success: function (data) {
            data = jQuery.parseJSON(data)
            if ('undefined' != data) {
                var html = prepareRoomHeader(data.room) +
                    prepareRoomChat(data.messages) +
                    prepareRoomSendMessage();
                var chat = $('.chat');
                chat.html('');
                chat.prepend(html)
                $('#roomName').text(data.room.title)
                setMessagesData(data.messages)
            }
            scrollDown(false)
            $('#myMessage').focus()
        }
    });
}

function goPrivate(elem) {
    var command = '{"command":"go-to-private-room", "params":{"email":"' + elem.data('email') + '"}}';
    conn.send(command);
}

function signOut() {
    conn.close;
    redirectToLogin();
}

function updateChat(elem) {
    if (elem.scrollTop() <= 700 && !isUpdating && !forceNoUpdate) {
        isUpdating = true;
        var activeRoom = $('.room.active');
        var roomId = activeRoom.data('id');
        $('.chat-app').data('room', roomId);
        var page = activeRoom.data('page');
        page++;
        $.ajax({
            url: '/chat/update-room',
            data: {
                id: roomId,
                identifier: identifier,
                page: page
            },
            success: function (data) {
                if ('undefined' != data && '' != data) {
                    data = jQuery.parseJSON(data)
                    if (0 < data.messages.length) {
                        var html = '';
                        $.each(data.messages, function (i, item) {
                            html += prepareSingleMessage(item);
                        });
                        $('.chat-history>ul').prepend(html);
                        $('#roomName').text(data.room.title);
                        $('.room.active').data('page', page);

                        setMessagesData(data.messages, true);
                        isUpdating = false;
                    } else {
                        forceNoUpdate = true;
                    }
                } else {
                    forceNoUpdate = true;
                }
            }
        });
    }
}

function updateRoomList(elem) {
    if (elem.scrollTop() >= 400 && !isUpdating && !forceNoUpdate) {
        isUpdating = true;
        var page = elem.data('page');
        page++;
        $.ajax({
            url: '/chat/update-room-list',
            data: {
                identifier: identifier,
                page: page
            },
            success: function (data) {
                if ('undefined' != data && '' != data) {
                    data = jQuery.parseJSON(data)
                    if (0 < data.rooms.length) {
                        elem.data('page', page);
                        $.each(data.rooms, function (i, item) {
                            appendNewRoom(item);
                        });
                        isUpdating = false;
                    } else {
                        forceNoUpdate = true;
                    }
                } else {
                    forceNoUpdate = true;
                }
            }
        });
    }
}

function setMessagesData(data, setToOpened = false) {
    $.each(data, function (i, item) {
        var messageLi = $('.single-message[data-messageId="' + item.id + '"]');
        if (setToOpened) {
            messageLi.removeClass('not-opened');
            messageLi.addClass('opened');
        }
        if (1 != item.isCommand) {
            messageLi.find('.sendAt').text(item.sendAt + ', ' + item.email + '  ').append(getCreateNewRoomBnt(item));
            messageLi.find('.message-text').text(item.message);
        } else {
            messageLi.find('.client-command i').text(item.message);
        }
    })
}

function getCreateNewRoomBnt(item)
{
    return identifier != item.identifier ?
        '<button type="button" class="btn btn-primary btn-xs btn-go-private" data-email="' + item.email + '" onclick="goPrivate($(this));">Go Private</button>' :
        '';
}

function sendMessage() {
    if (conn.readyState !== WebSocket.OPEN) {
        console.log('Connection is not open...');
        return;
    }
    var roomId = $('.chat-app').data('room');
    var textInput = $('#myMessage');
    var value = textInput.val();
    conn.send('{"message":"' + value + '", "room":"' + roomId + '"}');
    textInput.val('');
}

function updateRoomsStatus() {
    if (conn.readyState !== WebSocket.OPEN) {
        return;
    }
    conn.send('{"command":"get-rooms-online"}');
}

function updateRoomsUnreadMessages() {
    conn.send('{"command":"get-unread-messages"}');
}

function enterChat() {
    var port = $('.chat-app').data('port');
    conn = new WebSocket('ws://localhost:' + port + '?identifier=' + identifier);

    conn.onopen = function (e) {
        updateRoomsUnreadMessages()
        updateRoomsStatus()
        setInterval(function() {updateRoomsStatus()}, 5000)
        console.log("Connection established!");
    };

    conn.onerror = function(e) {
        console.log("Something went wrong...");
    }

    conn.onmessage = function (e) {
        var data = jQuery.parseJSON(e.data)
        if (undefined != data.systemResponse) {
            processSystemResponse(data.systemResponse)
        } else if (data[0].roomId == $('.chat-about').data('id')){
            var message = prepareSingleMessage(data[0]);
            $('.chat-history > ul').append(message);
            setMessagesData(data, true);
            scrollDown();
            conn.send(
                '{"command":"set-last-visit", "params":{"roomId":"' +
                data[0].roomId +
                '"}}'
            );
        } else {
            console.log(data);
            setUnreadMessages(data[0].roomId)
        }
    };
}

function setUnreadMessages(roomId, currentUnread = -1) {
    var spanUnread = $('.room[data-id="' + roomId + '"] .badge');
    if (-1 == currentUnread) {
        currentUnread = spanUnread.text();
        currentUnread++;
    }
    if (currentUnread > 0) {
        spanUnread.text(currentUnread);
    }
}

function showRoomSettings(roomId, currentDaysLimit, currentMessagesLimit) {
    bootbox.dialog({
        title: "Room Settings",
        message: '<p>Please select a room history options below:</p>',
        size: 'large',
        backdrop: true,
        buttons: {
            noclose: {
                label: "Set history limits by days",
                className: 'btn-primary float-left',
                callback: function(){
                    bootbox.prompt({
                        title: "By days limit",
                        message: '<p>Please select an option below:</p>',
                        inputType: 'radio',
                        value: currentDaysLimit,
                        backdrop: true,
                        inputOptions: [
                            {
                                text: '1 Day',
                                value: 1,
                            },
                            {
                                text: '7 Days',
                                value: 7,
                            },
                            {
                                text: '30 Days',
                                value: 30,
                            }
                        ],
                        callback: function (result) {
                            if (result > 0) {
                                commandSetRoomSettings(roomId, 'days', result);
                                bootbox.alert({
                                    message : "Room settings were successfully updated!",
                                    backdrop: true,
                                });
                            }
                        }
                    });
                }
            },
            ok: {
                label: "Set history limits by messages count",
                className: 'btn-info',
                callback: function(){
                    bootbox.prompt({
                        title: "By messages count limit",
                        message: '<p>Please select an option below:</p>',
                        inputType: 'radio',
                        value: currentMessagesLimit,
                        backdrop: true,
                        inputOptions: [
                            {
                                text: '500',
                                value: 500,
                            },
                            {
                                text: '1000',
                                value: 1000,
                            },
                            {
                                text: '5000',
                                value: 5000,
                            }
                        ],
                        callback: function (result) {
                            if (result > 0) {
                                commandSetRoomSettings(roomId, 'messages', result);
                                bootbox.alert({
                                    message : "Room settings were successfully updated!",
                                    backdrop: true,
                                });
                            }
                        }
                    });
                }
            }
        }
    });
}

function commandSetRoomSettings(roomId, key, value) {
    var command = '{"command":"set-room-settings", "params":{"key":"' + key + '","value":"' + value + '", "roomId":"' + roomId + '"}}';
    conn.send(command)
}

function processRoomOnline(systemResponse) {
    var roomsOnline = systemResponse;
    $('.room').each(function () {
        var roomId = $(this).data('id');
        var roomStatus = $(this).find('.status>i');
        roomStatus.removeClass('offline');
        roomStatus.removeClass('online');
        if (0 <= jQuery.inArray(roomId, roomsOnline)) {
            roomStatus.addClass('online')
        } else {
            roomStatus.addClass('offline')
        }
    })
    var chatAbout = $('.chat-about');
    var chatAboutStatus = chatAbout.find('i.fa-circle');
    var currentRoomId = chatAbout.data('id');
    if (undefined != currentRoomId) {
        chatAboutStatus.removeClass('offline');
        chatAboutStatus.removeClass('online');
        if (0 <= jQuery.inArray(currentRoomId, roomsOnline)) {
            chatAboutStatus.addClass('online')
        } else {
            chatAboutStatus.addClass('offline')
        }
    }
}

function processUnreadMessages(systemResponse) {
    $.each(systemResponse, function(roomId, count) {
        setUnreadMessages(roomId, count)
    });
}

function appendNewRoom(item) {
    var roomLi = '<li class="clearfix room" data-id="' + item.id + '" data-page="0" onclick="enterRoom($(this))">' +
        '<div class="about">' +
        '<div class="name">' + item.title +
        '<span class="badge badge-pill badge-primary pull-right"></span>' +
        '</div>' +
        '<div class="status">' +
        '<i class="fa fa-circle ' + ((undefined !== item.isOnline && 1 == item.isOnline) ? 'online' : 'offline') +'"></i>' +
        '</div>' +
        '</div>' +
        '</li>';

    $('.chat-list').append(roomLi);
}

function processSystemResponse(systemResponse) {
    switch (systemResponse.key) {
        case 'roomsOnline':
            processRoomOnline(systemResponse.value)
            break;
        case 'unreadMessages':
            processUnreadMessages(systemResponse.value)
            break;
        case 'createAndGoPrivateRoom':
            appendNewRoom(systemResponse.value)
            triggerGoToRoom(systemResponse.value.id)
            break;
        case 'goToPrivateRoom':
            triggerGoToRoom(systemResponse.value.id)
            break;
        case 'appendPrivateRoom':
            appendNewRoom(systemResponse.value)
            break;
        case 'connectionLimitReached':
            console.log(systemResponse.value);
            conn.close();
            break;
        case 'newConnection':
            console.log(systemResponse.value);
            conn.close;
            break;
        case 'clientCommandDate':
            showDate(systemResponse.value);
            break;
        case 'clientCommandShowmembers':
            showMembers(systemResponse.value);
            break;
    }
}

function showDate(date) {
    bootbox.alert({
        message : "Current date: " + date,
        backdrop: true,
    });
}

function showMembers(members) {
    bootbox.alert({
        message : "Members in room: " + members,
        backdrop: true,
    });
}

function redirectToLogin() {
    window.location.href = "/";
}

function triggerGoToRoom(roomId) {
    $('.room[data-id="' + roomId + '"]').trigger('click');
}

function prepareRoomHeader(room) {
    var roomStatus = $('.room[data-id="' + room.id + '"]').find('i.fa-circle');
    var isOnline = roomStatus.hasClass('online');
    return '<div class="chat-header clearfix">\n' +
        '        <div class="row"><div class="col-lg-6">\n' +
        '                <a href="javascript:void(0);" data-toggle="modal" data-target="#view_info">\n' +
        '                </a>\n' +
        '                <div class="chat-about" data-id="' + room.id + '">\n' +
        '                    <h6 class="m-b-0" id="roomName"></h6>\n' +
        '                    <i class="fa fa-circle ' + (isOnline ? 'online' : 'offline') + '"></i>\n' +
        '                </div>\n' +
        '            </div>\n' +
        '            <div class="col-lg-12 hidden-sm text-right">\n' +
        '                <a href="javascript:void(0);" class="btn btn-outline-info" onclick="showRoomSettings(' + room.id + ', ' + room.historyDaysLimit + ', ' + room.historyMessagesLimit + ')"><i class="fa fa-cogs"></i></a>\n' +
        '            </div></div></div>';
}

function prepareRoomChat(data) {
    var message = '<div class="chat-history" id="chat-history" onscroll="updateChat($(this))"><ul class="m-b-0">';
    $.each(data, function (i, item) {
        message += prepareSingleMessage(item);
    });

    message += '</ul></div>';
    return message;
}

function prepareSingleMessage(item)
{
    var message = '';
    message += '<li class="clearfix single-message ' + (1 == item.isOpened ? 'opened' : 'not-opened') + '" data-messageId="' + item.id + '">';
    if (1 != item.isCommand) {
        if (item.identifier == identifier) {
            message += '<div class="message-data text-right">';
        } else {
            message += '<div class="message-data">';
        }

        message += '<span class="message-data-time sendAt"></span></div>';

        if (item.identifier == identifier) {
            message += '<div class="message other-message float-right message-text">';
        } else {
            message += '<div class="message other-message message-text">';
        }

        message += '</div>'
    } else {
        message += '<div class="client-command text-center"><i></i></div>';
    }

    message += '</li>';

    return message;
}

function prepareRoomSendMessage() {
    return '<div class="chat-message clearfix"><div class="input-group mb-0">\n' +
        '            <input type="text" class="form-control" id="myMessage" placeholder="Enter text here...">\n' +
        '        <button type="button" class="bnt btn-primary" onclick=sendMessage()><i class="fa fa-send"></i></button>' +
        '</div></div>';
}

function resizeChat() {
    $('.chat').css("height", ($(document).height()/1.2));
}

function scrollDown(animated = true, scrollChat = true) {
    var chatHistory = $(".chat-history");
    var unreadMessage = $('.not-opened:first');
    if (scrollChat) {
        if (0 < unreadMessage.length) {
            if (animated) {
                chatHistory.animate({
                    scrollTop: unreadMessage.position().top - chatHistory.offset().top
                }, 1000);
            } else {
                chatHistory.scrollTop(unreadMessage.position().top - chatHistory.offset().top);
            }
        } else {
            if (animated) {
                chatHistory.animate({scrollTop: chatHistory.prop("scrollHeight")}, 1000);
            } else {
                chatHistory.scrollTop(chatHistory.prop("scrollHeight"));
            }
        }
    }
}