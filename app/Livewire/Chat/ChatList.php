<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use App\Traits\AvatarTrait;
use App\Models\Conversation;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class ChatList extends Component
{
    use AvatarTrait;
    public $selectedConversation;
    public $query;

    public $conversations;

    protected $listeners = ['refresh' => '$refresh'];




    public function mount($query = null) {
        $this->query = request()->route('query');

        // Fetch the conversation object using the query parameter
        $this->selectedConversation = Conversation::find($this->query);

        // dd($this->selectedConversation->id);
        // Check if the conversation was found
        if (!$this->selectedConversation) {
            // Handle the case where the conversation is not found
            abort(404, 'Conversation not found');
        }

        $this->loadConversations();

    }

    #[On('refresh')]
    public function refreshChatList()
    {
        $this->loadConversations();
    }


    protected function loadConversations(){
        $user = auth()->user();

        // $this->conversations = Cache::remember('conversations_' . $user->id, 60, function () use ($user) {
        //      dd($this->conversations);
        //     // return $user->conversations()->latest('updated_at')->get();
        //     return Conversation::orderBy('updated_at', 'desc')->get();

        // });


        $this->conversations = Cache::remember('conversations_' . $user->id, 60, function () use ($user) {
            return $user->conversations()->where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id)
                ->orderBy('updated_at', 'desc')
                ->get();

            // return Conversation::where('sender_id', $user->id)
            //     ->orWhere('receiver_id', $user->id)
            //     ->orderBy('updated_at', 'desc')
            //     ->get();
        });
    }

    public function deleteByUser($id) {
        $userId = auth()->id();
        $conversation= Conversation::find(decrypt($id));

        $conversation->messages()->each(function($message) use($userId){

            if($message->sender_id===$userId){

                $message->update(['sender_deleted_at'=>now()]);
            }
            elseif($message->receiver_id===$userId){

                $message->update(['receiver_deleted_at'=>now()]);
            }


        } );


        $receiverAlsoDeleted =$conversation->messages()
                ->where(function ($query) use($userId){

                    $query->where('sender_id',$userId)
                          ->orWhere('receiver_id',$userId);

                })->where(function ($query) use($userId){

                    $query->whereNull('sender_deleted_at')
                            ->orWhereNull('receiver_deleted_at');

                })->doesntExist();



        if ($receiverAlsoDeleted) {

            $conversation->forceDelete();
        }



        return redirect(route('chat.index'));

    }





    public function render()
    {

        return view('livewire.chat.chat-list', [
            'conversations' => $this->conversations,
            'user' => auth()->user(),

        ]);
    }

}
