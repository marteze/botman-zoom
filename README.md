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
2. Goto to Develop -> Build App<br>
![image](https://user-images.githubusercontent.com/8368009/163389785-04f96f87-6281-433a-a7ce-34b900b34abf.png)
3. Create a JWT App if you dont have yet. This is necessary to find user's data.<br>
![image](https://user-images.githubusercontent.com/8368009/163390503-49159f16-dfe7-4f75-96d8-1edb00cdf98d.png)<br>
Define a name to the JWT App<br>
![image](https://user-images.githubusercontent.com/8368009/163390836-dc3d83a4-51d2-43ea-8182-34c17b5c63c6.png)<br>
Define all obrigatory fields<br>
![image](https://user-images.githubusercontent.com/8368009/163391319-44641c25-5694-46fa-9fa5-abfa2547d25b.png)<br>
In the App Credentials you'll find `ZOOM_API_KEY` and `ZOOM_API_SECRET` values to put in docker-compose.yml ahead<br>
![image](https://user-images.githubusercontent.com/8368009/163391908-efc36b74-7fdb-48d6-a507-c6d19ee66d50.png)<br>
In the Feature you'll finda `ZOOM_API_VERIFICATION_TOKEN` value to put in docker-compose.yml ahead<br>
![image](https://user-images.githubusercontent.com/8368009/163392978-4afc3b06-2906-45a7-ac46-238efec68ae4.png)


### Create, configure and run BotMan container

1. Install Docker. To do this go to <https://www.docker.com/get-started/><br>
2. Make a clone of this repository in your computer or download this project in zip format and extract in your computer.<br>
3. Inside the clone's directory run these commands to create the container and start him:<br>
`docker-compose build`<br>
`docker-compose up -d`<br>
4. Run this command to access the container's shell:<br>
`docker exec -it botman-starter bash`<br>
5. Inside the container's shell run these commands to create .env file, generate a key to the Laravel application and download/update composer's packages:<br>
`cd /app/`<br>
`cp .env.example .env`<br>
`php artisan key:generate`<br>
`composer update`<br>

From now you can access <http://localhost/botman/tinker> to verify if the application is running correctly.<br>
Send "hi" to the chatbot and he must reply. Send "start conversation" to a interactive conversation.

## Programming the chatbot

All modifications made into the code inside the `app_botman` directory in the host computer will reflect immediately in the running application inside container.

For more information about programming the chatbot access the BotMan's documentation in <https://botman.io/2.0/welcome>.
