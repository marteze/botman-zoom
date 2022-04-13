<?php

namespace App\Drivers;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Users\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Collection;
use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Interfaces\WebAccess;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use Symfony\Component\HttpFoundation\ParameterBag;
use Illuminate\Support\Facades\Cache;
use function GuzzleHttp\json_encode;

class ZoomDriver extends HttpDriver
{
    const DRIVER_NAME = 'Zoom';
    
    const ZOOM_OAUTH_TOKEN_URL = 'https://zoom.us/oauth/token';
    const ZOOM_CHAT_MESSAGE_URL = 'https://api.zoom.us/v2/im/chat/messages';
    const ZOOM_API_URL = 'https://api.zoom.us/v2/';
    
    /** @var int */
    protected $actionsLimitDisplay = null;
    
    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->config = Collection::make($this->config->get('zoom', []));
        
        $serverRequestData = (array) json_decode($request->getContent(), true);
        
        $receivedPayload = [];
        
        if (isset($serverRequestData['payload']) == true) {
            $receivedPayload = $serverRequestData['payload'];
        }
        
        try  {
            $params = [
                "driver" => "zoom",
                "authorization" => $request->headers->get('authorization'),
                "robotJid" => $receivedPayload['robotJid'],
                "toJid" => $receivedPayload['toJid'],
                "accountId" => $receivedPayload['accountId'],
                "userName" => $receivedPayload['userName'],
                "userId" => $receivedPayload['userId'],
                "sender" => $receivedPayload['userId'],
                "attachment" => null,
                "interactive" => false,
                "attachment_data" => null,
                "raw" => $serverRequestData
            ];
        } catch (\Exception $e) {
            $this->payload = Collection::make([]);
            $this->event = Collection::make([]);
            return;
        }
        
        if (isset($receivedPayload['cmd']) == true) {
            $params['message'] = $receivedPayload['cmd'];
            $this->payload = new ParameterBag($params);
        } elseif (isset($receivedPayload['actionItem']['value']) == true) {
            $params['message'] = $receivedPayload['actionItem']['value'];
            $params['interactive'] = true;
            $this->payload = new ParameterBag($params);
        } elseif (isset($receivedPayload['selectedItems'][0]['value']) == true) {
            $params['message'] = $receivedPayload['selectedItems'][0]['value'];
            $params['interactive'] = true;
            $this->payload = new ParameterBag($params);
        } elseif (isset($receivedPayload['fieldEditItem']['newValue']) == true) {
            $params['message'] = $receivedPayload['fieldEditItem']['newValue'];
            $params['interactive'] = true;
            $this->payload = new ParameterBag($params);
        }
        
        $this->event = Collection::make($this->payload);
        
    }
    
    /**
     * @return string
     */
    protected function getChatbotToken()
    {
        if (Cache::has('zoom_chatbot_token') == true) {
            return Cache::get('zoom_chatbot_token')->access_token;
        }
        
        $headers = [
            'Authorization: Basic ' . base64_encode($this->config->get('zoom_bot_client_id') . ':' . $this->config->get('zoom_bot_client_secret')),
            'Content-Type: application/x-www-form-urlencoded'
        ];
        
        $response = $this->http->post(self::ZOOM_OAUTH_TOKEN_URL . '?grant_type=client_credentials', [], [], $headers);
        $responseData = json_decode($response->getContent());
        
        Cache::put('zoom_chatbot_token', $responseData, ($responseData->expires_in - 60) / 60);
        
        return $responseData->access_token;
    }
    
    /**
     * @param IncomingMessage $matchingMessage
     * @return \BotMan\BotMan\Users\User
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        $firstName = strtok($matchingMessage->getPayload()->get('userName'), " ");
        
        $userInfo = [
            'userName' => $matchingMessage->getPayload()->get('userName'),
            'toJid' => $matchingMessage->getPayload()->get('toJid'),
            'accountId' => $matchingMessage->getPayload()->get('accountId'),
            'userId' => $matchingMessage->getPayload()->get('userId'),
        ];
        
        return new User($matchingMessage->getSender(), $firstName, null, null, $userInfo);
    }
    
    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $matches = (strpos($this->event->get('toJid'), '@xmpp.zoom.us') !== false);
        
        if ($matches) {
            if ($this->event->get('authorization') != $this->config->get('zoom_bot_verification_token')) {
                throw new \Exception('Authorization header received not matches with zoom_bot_verification_token.');
            }
        }
        
        return ((strpos($this->event->get('toJid'), '@xmpp.zoom.us') !== false) && ($this->event->get('authorization') == $this->config->get('zoom_bot_verification_token')));
    }
    
    /**
     * @param  IncomingMessage $message
     * @return \BotMan\BotMan\Messages\Incoming\Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        $interactive = $this->event->get('interactive', false);
        
        if (is_string($interactive)) {
            $interactive = ($interactive !== 'false') && ($interactive !== '0');
        } else {
            $interactive = (bool) $interactive;
        }
        
        return Answer::create($message->getText())
            ->setValue($this->event->get('value', $message->getText()))
            ->setMessage($message)
            ->setInteractiveReply($interactive);
    }
    
    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        if (empty($this->messages)) {
            $message = $this->event->get('message');
            $userId = $this->event->get('userId');
            $sender = $this->event->get('sender', $userId);
            
            $incomingMessage = new IncomingMessage($message, $sender, $userId, $this->payload);
            
            $this->messages = [$incomingMessage];
        }
        
        return $this->messages;
    }
    
    /**
     *
     * @param string|Question|OutgoingMessage $message
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        
        if (($this->payload->get('toJid') == '') && ($this->payload->get('accountId') == '')) {
            $userInfo = $this->getFullUserInfo($matchingMessage);

            $retorno = [
                'robot_jid' => $this->config->get('zoom_bot_jid'),
                'to_jid' => $userInfo['jid'],
                'account_id' => $userInfo['account_id'],
                'content' => [],
                'is_markdown_support' => false
            ];
        } else {
            $retorno = [
                'robot_jid' => $this->config->get('zoom_bot_jid'),
                'to_jid' => $this->payload->get('toJid'),
                'account_id' => $this->payload->get('accountId'),
                'content' => [],
                'is_markdown_support' => false
            ];
        }
        
        if (is_array($message) == true) {
            if ((isset($message['is_markdown_support']) == true) && ($message['is_markdown_support'] == true)) {
                $retorno['is_markdown_support'] = true;
            }
            
            unset($message['is_markdown_support']);
            
            if (isset($message['text']) == true) {
                $retorno['content']['head'] = [
                    'text' => $message['text'] . ''
                ];
            } else {
                $retorno['content'] = $message;
            }
        }
        
        if ($message instanceof OutgoingMessage) {
            $retorno['content']['head'] = [
                'text' => $message->getText() . ''
            ];
        }
        
        if ($message instanceof Question) {
            $questionText = $message->getText();
            
            if (is_array($questionText) == true) {
                if ((isset($questionText['is_markdown_support']) == true) && ($questionText['is_markdown_support'] == true)) {
                    $retorno['is_markdown_support'] = true;
                    unset($questionText['is_markdown_support']);
                }
                
                if (isset($questionText['text']) == true) {
                    $retorno['content']['head'] = [
                        'text' => $questionText['text'] . ''
                    ];
                } else {
                    $retorno['content'] = $questionText;
                }
            } else {
                if ($questionText != '') {
                    $retorno['content']['head'] = [
                        'text' => $questionText . ''
                    ];
                }
            }
            
            if (count($message->getActions()) > 0) {
                $retorno['content']['body'] = [];
                
                // Selects
                foreach($message->getActions() as $action) {
                    if ($action['type'] == 'select') {
                        $retorno['content']['body'][] = [
                            'type' => 'select',
                            'text' => $action['text'],
                            'select_items' => $action['options'],
                        ];
                    }
                }

                // Fields
                $fieldsItems = [];
                
                foreach($message->getActions() as $action) {
                    if ($action['type'] == 'field') {
                        $fieldsItems[] = [
                            'key' => $action['key'],
                            'value' => $action['value'],
                            'editable' => $action['editable'],
                            //'short' => $action['short'],
                        ];
                    }
                }
                
                if (count($fieldsItems) > 0) {
                    $retorno['content']['body'][] = [
                        'type' => 'fields',
                        'items' => $fieldsItems
                    ];
                }
                
                // Buttons
                $actionsItems = [];
                
                foreach($message->getActions() as $action) {
                    if ($action['type'] == 'button') {
                        if (isset($action['additional']['style']) == false) {
                            $action['additional']['style'] = "Primary";
                        }
                        
                        $actionsItems[] = [
                            'text' => $action['text'],
                            'value' => $action['value'],
                            'style' => $action['additional']['style'],
                        ];
                    }
                }
                
                if (count($actionsItems) > 0) {
                    if ($this->actionsLimitDisplay == null) {
                        $this->actionsLimitDisplay = $this->config->get('zoom_actions_limit_display', 1000);
                    }
                    
                    $retorno['content']['body'][] = [
                        'type' => 'actions',
                        'items' => $actionsItems,
                        'limit' => $this->actionsLimitDisplay
                    ];
                }
            }
        }
        
        return $retorno;
    }
    
    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        // Envia a mensagem para o servidor do zoom
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->getChatbotToken()
        ];
        
        $this->writeDebug($payload);
        $this->writeDebug(json_encode($payload));
        
        $response = $this->http->post(self::ZOOM_CHAT_MESSAGE_URL, [], $payload, $headers, true);
        
        if ($response->getStatusCode() >= 400) {
            throw new \Exception('Zoom server returned status code ' . $response->getStatusCode() . '! Message: ' . $response->getContent());
        }
        
        return $response;
    }
    
    /**
     * @param $messages
     * @return array
     */
    protected function buildReply($messages)
    {
        $replyData = Collection::make($messages)->transform(function ($replyData) {
            $reply = [];
            $message = $replyData['message'];
            $additionalParameters = $replyData['additionalParameters'];
            
            if ($message instanceof WebAccess) {
                $reply = $message->toWebDriver();
            } elseif ($message instanceof OutgoingMessage) {
                $attachmentData = (is_null($message->getAttachment())) ? null : $message->getAttachment()->toWebDriver();
                $reply = [
                    'type' => 'text',
                    'text' => $message->getText(),
                    'attachment' => $attachmentData,
                ];
            }
            $reply['additionalParameters'] = $additionalParameters;
            
            return $reply;
        })->toArray();
        
        return $replyData;
    }
    
    /**
     * @return bool
     */
    public function isConfigured()
    {
        return $this->config->get('zoom_api_key')
            && $this->config->get('zoom_api_secret')
            && $this->config->get('zoom_api_verification_token')
            && $this->config->get('zoom_bot_client_id')
            && $this->config->get('zoom_bot_client_secret')
            && $this->config->get('zoom_bot_jid')
            && $this->config->get('zoom_bot_verification_token');
    }
    
    /**
     * @return string
     */
    protected function getApiToken()
    {
        if (Cache::has('zoom_api_token') == true) {
            return Cache::get('zoom_api_token');
        }
        
        $payload = [
            'iss' => $this->config->get('zoom_api_key'),
            'exp' => time() - date('Z') + (6 * 60)
        ];
        
        $apiToken = JWT::encode($payload, $this->config->get('zoom_api_secret'), 'HS256');
        
        Cache::put('zoom_api_token', $apiToken, 5);
        
        return $apiToken;
    }
    
    /**
     * Get full user information from Zoom Server.
     * 
     * @param IncomingMessage $matchingMessage
     * @return array
     */
    public function getFullUserInfo(IncomingMessage $matchingMessage) {
        $userId = $matchingMessage->getSender();
        
        if (Cache::has('zoom_user_info_' . $userId) == true) {
            return Cache::get('zoom_user_info_' . $userId);
        }
        
        $response = $this->sendRequest("users/" . $userId, [], $matchingMessage);
        
        $userInfo = json_decode($response->getContent(), true);
        
        Cache::put('zoom_user_info_' . $userInfo['email'], $userInfo, config('botman.config.user_cache_time'));
        Cache::put('zoom_user_info_' . $userId, $userInfo, config('botman.config.user_cache_time'));
        
        return $userInfo;
    }
    
    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @return Response
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        $apiTokenBearer = $this->getApiToken();
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiTokenBearer
        ];
        
        $url = self::ZOOM_API_URL . $endpoint;
        
        if (count($parameters) == 0) {
            return $this->http->get($url, [], $headers, true);
        } else {
            return $this->http->post($url, [], $parameters, $headers, true);
        }
    }
    
    public function setActionsLimitDisplay($limit) {
        $this->actionsLimitDisplay = $limit;
    }
    
    private function writeDebug($value) {
        if (is_scalar($value) == true) {
            file_put_contents('/tmp/debug.txt', $value . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            file_put_contents('/tmp/debug.txt', print_r($value, true), FILE_APPEND | LOCK_EX);
        }
    }
}