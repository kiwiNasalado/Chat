Chat [main info]
-------------------
The chat application that based on websockets and does not require registration. With two pre-filled common rooms and
the ability to create private rooms between participants.

FUNCTIONALITY
-------------
<ul style="list-style: none;">
   <li>Simple docker support</li>
   <li>No registration entering (only by email)</li>
   <li>Fast messaging via websockets</li>
   <li>Public rooms</li>
   <li>Private rooms</li>
   <li>Up to 1k connections</li>
   <li>
       Room history limits settings:
       <ul style="list-style: none;">
           <li>
Days history limit:
               <ul style="list-style: none;">
                    <li>Up to 1 day</li>
                    <li>Up to 7 days</li>
                    <li>Up to 30 days</li>
               </ul>
           </li>
           <li> 
Messages history limit:
               <ul style="list-style: none;">
                   <li>Up to 500 messages</li>
                   <li>Up to 1000 messages</li>
                   <li>Up to 5000 messages</li>
               </ul>
           </li>
       </ul>
</li>
   <li>Rooms pagination by scroll</li>
   <li>Messages in rooms pagination by scroll</li>
   <li>After offline period, once the user opens the room, the history is shown from the oldest unread message</li>
   <li>Unread messages counter for each room (either you online or not)</li>
   <li>Rooms' online indicator (in case you're alone on the room - it'll be shown as 'offline')</li>
   <li>Rooms' online indicator autoupdate</li>
   <li>Console log of actions in rooms</li>
   <li>Simple user commands [/me, /date, /showmembers]</li>
   <li>Chat history cleaner command (with console log displaying)</li>
</ul>

INSTALLATION
------------
Download repository to your **/var/www/** folder

    git clone https://github.com/kiwiNasalado/Chat.git /var/www/your_project_name

Go to **/var/www/your_project_name/docker** and run docker build

    docker-compose build

Start the container inside the **/var/www/your_project_name/docker**

    docker-compose up -d

Start the ChatServer from the **/var/www/your_project_name**

    ./yii chat/start

ADDITIONAL
-------------
To run the ChatHistoryCleaner go to the **/var/www/your_project_name** and run

    ./yii history-cleaner/clean

To go inside each docker container - simply run next commands from the **/var/www/your_project_name**

    docker-compose exec php-chat bash
    docker-compose exec db-chat bash
    docker-compose exec web-chat bash