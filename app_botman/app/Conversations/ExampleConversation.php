<?php

namespace App\Conversations;

use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;

class ExampleConversation extends Conversation
{
    /**
     * First question
     */
    public function askReason()
    {
        $question = Question::create(['text' => "*Huh - you woke me up.* What do you need?", 'is_markdown_support' => true])
            ->fallback('Unable to ask question')
            ->callbackId('ask_reason')
            ->addButtons([
                Button::create('Tell a joke')->value('joke'),
                Button::create('Give me a fancy quote')->value('quote'),
            ]);

        return $this->ask($question, function (Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() === 'joke') {
                    $joke = json_decode(file_get_contents('http://api.icndb.com/jokes/random'));
                    
                    $formattedMessage = [
                        "is_markdown_support" => false,
                        "head" => [
                            "text" => "Joke",
                            "style" => [
                                "color" => "#8338EC",
                                "bold" => true,
                                "italic" => false
                            ],
                            "sub_head" => [
                                "text" => "I am a styled sub header",
                                "style" => [
                                    "color" => "#13C4A3",
                                    "bold" => false,
                                    "italic" => true
                                ]
                            ]
                        ],
                        "body" => [
                            [
                                "type" => "message",
                                "text" => $joke->value->joke,
                                "style" => [
                                    "color" => "#0099ff",
                                    "bold" => false,
                                    "italic" => false
                                ]
                            ],
                            [
                                "type" => "message",
                                "text" => "More jokes here",
                                "link" => "http://www.icndb.com/"
                            ],
                            [
                                "type" => "attachments",
                                "resource_url" => "https://zoom.us",
                                "img_url" => "https://d24cgw3uvb9a9h.cloudfront.net/static/93516/image/new/ZoomLogo.png",
                                "information" => [
                                    "title" => [
                                        "text" => "I am an attachment title"
                                    ],
                                    "description" => [
                                        "text" => "I am an attachment description"
                                    ]
                                ]
                            ],
                            [
                                "type" => "section",
                                "sidebar_color" => "#F56416",
                                "sections" => [
                                    [
                                        "type" => "message",
                                        "text" => "I am a message with text"
                                    ]
                                ],
                                "footer" => "I am a footer",
                                "footer_icon" => "https://upload.wikimedia.org/wikipedia/en/thumb/5/58/San_Francisco_Giants_Logo.svg/1200px-San_Francisco_Giants_Logo.svg.png",
                                "ts" => 1560446471819
                            ]
                        ]
                    ];
                    
                    $this->say($formattedMessage);
                } else {
                    $this->say(Inspiring::quote());
                }
            }
        });
    }

    /**
     * Start the conversation
     */
    public function run()
    {
        $this->askReason();
    }
}
