<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Conversations\ExampleConversation;
use Illuminate\Http\Response;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Http\Curl;
use App\Drivers\ZoomDriver;
use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Storages\Drivers\FileStorage;
use BotMan\BotMan\BotManFactory;

class BotManController extends Controller
{
    /**
     * Place your BotMan logic here.
     */
    public function handle()
    {
        $botman = app('botman');
        
        $botman->listen();
    }
    
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tinker()
    {
        return view('tinker');
    }
    
    /**
     * Loaded through routes/botman.php
     * @param  BotMan $bot
     */
    public function startConversation(BotMan $bot)
    {
        $bot->startConversation(new ExampleConversation());
    }
    
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function authorizeChatbot()
    {
        return Redirect::to('https://zoom.us/launch/chat?jid=robot_'. config('botman.zoom.zoom_bot_jid'));
    }
    
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function support()
    {
        return view('support');
    }
    
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function privacy()
    {
        return view('privacy');
    }
    
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function terms()
    {
        return view('terms');
    }
    
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function documentation()
    {
        return view('documentation');
    }
    
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function deauthorize(Request $request)
    {
        $response = new Response();
        
        if ($request->header('authorization') != config('botman.zoom.zoom_bot_verification_token')) {
            $response->setStatusCode(401, 'Not authorized!');
            return $response;
        }
        
        $conteudo = json_decode($request->getContent(), true);
        
        if ((isset($conteudo['payload']['client_id']) == false) || (isset($conteudo['payload']['user_id']) == false) || (isset($conteudo['payload']['account_id']) == false)) {
            $response->setStatusCode(400);
            return $response;
        }
        
        try {
            $headers = [
                'Authorization: Basic ' . base64_encode($this->config->get('zoom_bot_client_id') . ':' . $this->config->get('zoom_bot_client_secret')),
                'Content-Type: application/x-www-form-urlencoded'
            ];
            
            $dados = [
                'client_id' => $conteudo['payload']['client_id'],
                'user_id' => $conteudo['payload']['user_id'],
                'account_id' => $conteudo['payload']['account_id'],
                'deauthorization_event_received' => $conteudo['payload'],
                'compliance_completed' => true
            ];
            
            $http = new Curl();
            
            $responseZoomServer = $http->post('https://api.zoom.us/oauth/data/compliance', [], $dados, $headers, true);
            
            $response->setContent($responseZoomServer->getContent());
            
            return $response;
        } catch (\Exception $error) {
            $response->setStatusCode(500);
            
            return $response;
        }
    }
    
    /**
     * Send a message
     * @param  BotMan $bot
     */
    public function sendMessage(Request $request)
    {
        $response = new Response();
        
        if (config('botman.zoom.send_message_secret') != "") {
            if ($request->header("authorization") != config('botman.zoom.send_message_secret')) {
                $response->setStatusCode("401");
                return $response;
            }
        }
        
        $content = json_decode($request->getContent(), true);
        $botman = resolve('botman');
        $botman->say($content['message'], $content['account'], 'App\Drivers\ZoomDriver');
        
        $response->header('Content-Type', 'application/json');
        $response->setContent(json_encode('ok'));
        
        return $response;
    }
}
