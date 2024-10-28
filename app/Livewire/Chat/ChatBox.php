<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Notifications\MessageRead;
use App\Notifications\MessageSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ChatBox extends Component
{

    public $query;
    public $selectedConversation;
    public $body = '';

    public $loadedMessages;

    public $paginate_var = 10;

    protected $listeners = [
        'loadMore',
    ];

    public function getListeners()
    {
        $auth_id = auth()->user()->id;

        return [
            'loadMore',
            "echo-private:users.{$auth_id},.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated" => 'broadcastedNotifications',
        ];
    }

    public function broadcastedNotifications($event)
    {
        if ($event['type'] == MessageSent::class) {

            if ($event['conversation_id'] == $this->selectedConversation->id) {

                $this->dispatch('scroll-bottom');

                $newMessage = Message::find($event['message_id']);

                // push message
                $this->loadedMessages->push($newMessage);

                // mark message as read
                $newMessage->read_at = now();
                $newMessage->save();

                // broadcast
                $this->selectedConversation->getReceiver()
                    ->notify(new MessageRead($this->selectedConversation->id));

            }
        }

    }
    public function mount()
    {
        $this->query = request()->route('query');

        // Fetch the conversation object using the query parameter
        $this->selectedConversation = Conversation::find($this->query);

        // Check if the conversation was found
        if (!$this->selectedConversation) {
            // Handle the case where the conversation is not found
            abort(404, 'Conversation not found');
        }
        $this->loadMessages();

        // update the chat height

        // $this->dispatchBrowserEvent('update-chat-height');
        $this->dispatch('update-chat-height');

    }

    public function loadMore()
    {
        // dd('detected');

        // increment
        $this->paginate_var += 10;

        // call loadMessages()
        $this->loadMessages();
    }

    public function loadMessages()
    {
        // get count
        $count = Message::where('conversation_id', $this->selectedConversation->id)->where(function ($query) {
            $query->where('sender_id', auth()->id())->orWhere('receiver_id', auth()->id());
        })->count();

        $this->loadedMessages = Message::where('conversation_id', $this->selectedConversation->id)->where(function ($query) {
            $query->where('sender_id', auth()->id())->orWhere('receiver_id', auth()->id());
        })->skip($count - $this->paginate_var)
            ->take($this->paginate_var)
            ->get();

        return $this->loadedMessages;
    }

    // public function resetBody()
    // {
    //     // $this->body = ''; // Reset the value
    //     $this->reset($this->body);
    // }

    public function sendMessage()
    {


        // $this->validate(['body' => 'required|string']);

        $validator = Validator::make(['body' => $this->body], [
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Retrieve the validated input...
        $validated = $validator->validated();

        $createdMessage = Message::create([
            'conversation_id' => $this->selectedConversation->id,
            'sender_id' => auth()->id(),
            'receiver_id' => $this->selectedConversation->getReceiver()->id,
            'body' => $this->body,
        ]);

        // reset body
        $this->body = '';

        // scroll to bottom
        $this->dispatch('scroll-bottom');

        // push the message
        $this->loadedMessages->push($createdMessage);

        // update conversation model
        $this->selectedConversation->updated_at = now();

        $this->selectedConversation->save();
        // $conversations = Conversation::orderBy('updated_at', 'desc')->get();

        // refresh chatlist
        // Livewire::emitTo('chat.chat-list','refresh');
        // $this->dispatch('chat.chat-list','refresh');
        $this->dispatch('refresh');

        // broadcast

        $this->selectedConversation->getReceiver()
            ->notify(new MessageSent(
                Auth()->User(),
                $createdMessage,
                $this->selectedConversation,
                $this->selectedConversation->getReceiver()->id
            ));

        // reset body
        // return $this->resetBody;

    }



    public function render()
    {

        return view('livewire.chat.chat-box', [
            'selectedConversation' => $this->selectedConversation,
        ]);
    }
}
