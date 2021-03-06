# botman-zoom
A project ready-to-use of chatbot BotMan with implemented Zoom Driver running inside a Docker container.

## How to use

To create a chatbot to Zoom we must provide an URL accessible to the Zoom Server in the Internet. Here we will use Ngrok to make your localhost exposed to Internet but if you have a hosting service you can use this in replace Ngrok.

### Install and run Ngrok

1. Install Ngrok and configure to your authtoken's account. To do this go to <https://ngrok.com/download><br>
2. Open a new terminal and run `ngrok http 80`. You must see anything like this:
![image](https://user-images.githubusercontent.com/8368009/163388373-118b3eb1-a56f-417a-8116-0bb252a8ff35.png)<br>
Take note of https address. Leave this terminal open.

### Create and configure the chatbot in Zoom Marketplace

1. Access the Zoom Marketplace at <https://marketplace.zoom.us/> and sing in.
2. Goto to Develop -> Build App<br>.
![image](https://user-images.githubusercontent.com/8368009/163389785-04f96f87-6281-433a-a7ce-34b900b34abf.png)
3. Create a JWT App if you dont have yet. This is necessary to BotMan find user's data.<br>
![image](https://user-images.githubusercontent.com/8368009/163390503-49159f16-dfe7-4f75-96d8-1edb00cdf98d.png)<br>
Define a name to the JWT App:<br>
![image](https://user-images.githubusercontent.com/8368009/163390836-dc3d83a4-51d2-43ea-8182-34c17b5c63c6.png)<br>
Define all obligatory fields:<br>
![image](https://user-images.githubusercontent.com/8368009/163391319-44641c25-5694-46fa-9fa5-abfa2547d25b.png)<br>
In the App Credentials page you'll find `ZOOM_API_KEY` and `ZOOM_API_SECRET` values to put in docker-compose.yml ahead<br>
![image](https://user-images.githubusercontent.com/8368009/163391908-efc36b74-7fdb-48d6-a507-c6d19ee66d50.png)<br>
In the Feature page you'll finda `ZOOM_API_VERIFICATION_TOKEN` value to put in docker-compose.yml ahead<br>
![image](https://user-images.githubusercontent.com/8368009/163392978-4afc3b06-2906-45a7-ac46-238efec68ae4.png)
4. Create a chatbot:<br>
![image](https://user-images.githubusercontent.com/8368009/163394001-56c9b2b7-227c-4309-b429-ba4de6377ebd.png)<br>
Define a name to your chatbot:<br>
![image](https://user-images.githubusercontent.com/8368009/163396566-f8a70d72-a12c-4183-a3ac-8858f83218c5.png)<br>
In App Credentials fill the Redirect URL for OAuth with your Ngrok https address + "/botman/authorize-chatbot":<br>
![image](https://user-images.githubusercontent.com/8368009/163422585-f028ebea-a782-40fc-b744-b07c0a51f2cf.png)<br>
Define too the OAuth allow list like this:<br>
![image](https://user-images.githubusercontent.com/8368009/163406506-fe7f82bc-1903-4cd3-bebe-c9999572ed8e.png)<br>
In the App Credentials page you'll find `ZOOM_BOT_CLIENT_ID` and `ZOOM_BOT_CLIENT_SECRET` values to put in docker-compose.yml ahead<br>
![image](https://user-images.githubusercontent.com/8368009/163399135-5b0eb54a-9be5-4a8b-a9dd-cc90dbc4b1f8.png)<br>
In the Information page define Short description, Long description, Company Name, Category, Developer Contact Name, Developer Contact E-mail, :<br>
![image](https://user-images.githubusercontent.com/8368009/163399650-d3813a98-279b-4f8b-81b2-f4975bba7eb2.png)<br>
You must define this fields too:<br>
![image](https://user-images.githubusercontent.com/8368009/163405532-6b391e20-afc7-4eb5-bf3f-04f0d4a32ab7.png)<br>
In the Feature Page, in Chat Subscription you'll find `ZOOM_BOT_VERIFICATION_TOKEN` value to put in docker-compose.yml ahead. Define a Slash Command and Bot endpoint URL with your Ngrok https address + "/botman" like this and click Save button:<br>
![image](https://user-images.githubusercontent.com/8368009/163401512-215f203d-035c-4ee3-981e-71766c186b96.png)<br>
After save you will see the `ZOOM_BOT_JID` value to put in docker-compose.yml ahead:<br>
![image](https://user-images.githubusercontent.com/8368009/163403221-da84aad9-78e5-4ca9-a411-6bbb8e1fa02d.png)<br>
**Remember**: If you restart the Ngrok service you'll need update all fields with the new URL address.

### Create, configure and run BotMan container

1. Install Docker. To do this go to <https://www.docker.com/get-started/><br>
2. Make a clone of this repository in your computer or download this project in zip format and extract in your computer.<br>
3. Edit the docker-compose.yml file and define the environment variables appointed above.<br>
4. Inside the clone's directory run these commands to create the container and start him:<br>
`docker-compose build`<br>
`docker-compose up -d`<br>
4. Run this command to access the container's shell:<br>
`docker exec -it botman-zoom bash`<br>
5. Inside the container's shell run these commands to create .env file, generate a key to the Laravel application and download/update composer's packages:<br>
`cd /app/`<br>
`cp .env.example .env`<br>
`php artisan key:generate`<br>
`composer update`<br>

From now you can install your brand new chatbot in your Zoom Client. Back to Zoom Marketplace, inside chatbot admin pages in Local Test page click Add button.<br>
![image](https://user-images.githubusercontent.com/8368009/163424514-ce7cebea-c8fd-4fea-aeb4-2845e88bcf0e.png)<br>
And allow the app:<br>
![image](https://user-images.githubusercontent.com/8368009/163423581-5eab96f3-3e65-4127-b20c-df0f5603bd0f.png)<br>
After this the chatbot must appear in the your client like this:<br>
![image](https://user-images.githubusercontent.com/8368009/163423832-380266e0-5cba-4cb8-b683-d605d53c3ccb.png)<br>
Send "hi" to the chatbot and he must reply. Send "start conversation" to a interactive conversation.
![image](https://user-images.githubusercontent.com/8368009/163424044-e5bfface-29b8-49e6-882d-8c298b96e83f.png)

## Programming the chatbot

All modifications made into the code inside the `app_botman` directory in the host computer will reflect immediately in the running application inside container.

For more information about programming the chatbot access the BotMan's documentation in <https://botman.io/2.0/welcome>.

### How to use the Zoom Driver to send messages

You can reply/send messages in three formats:
1. Simple text without markdown:<br>
![image](https://user-images.githubusercontent.com/8368009/163426676-787666c8-928e-4df6-ac52-ff3ba68d2d82.png)<br>
2. Simple text with markdown:<br>
![image](https://user-images.githubusercontent.com/8368009/163426963-49ac9802-abc9-466b-b651-5be8e6f879c7.png)<br>
3. Full custom message structured like the Zoom's documentation (more information in <https://marketplace.zoom.us/docs/guides/chatbots/customizing-messages>):
![image](https://user-images.githubusercontent.com/8368009/163427908-8327588e-7c01-4567-9b36-01c7db578191.png)<br>
You can make your container send messages programmatically (without any user send message before). The URL to this is `http://localhost/botman/send-message`. Like this (in Postman):<br>
![image](https://user-images.githubusercontent.com/8368009/163429878-48f11694-7b51-4796-8bd6-f11dfffe80bf.png)<br>
The message value accepts the three formats described above.<br>
We highly recommend to define the `SEND_MESSAGE_SECRET` in your docker-compose.yml file to protect this endpoint from non-authorized calls. If you define this variable you must pass the value in Authorization header, like this:<br>
![image](https://user-images.githubusercontent.com/8368009/163430587-1a083f65-8d86-46b1-80d8-b3779314718f.png)

