<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\PaymentDetails;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{



    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users',
            'password' => 'required|string',
            'c_password' => 'required|same:password'
        ]);


        $user  =  User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'c_password' => bcrypt($request->c_password)

        ]);


        if ($user) {
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->plainTextToken;

            return response()->json([
                'message' => 'Successfully created user!',
                'accessToken' => $token,
            ], 201);
        } else {
            return response()->json(['error' => 'Provide proper details']);
        }
    }


    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt(request(['email', 'password']))) {

            $success['status'] = 401;
            $success['message'] = 'Unauthorized';

            return response()->json(['success' => $success], $success['status']);
        }

        $user = $request->user();

        $token = $user->createToken('Personal Access Token')->plainTextToken;


        $success['status'] = 200;
        $success['message'] = 'User login successfully.';
        $success['data'] = $user;
        $success['accessToken'] = $token;
        return response()->json(['success' => $success], $success['status']);
    }



    public function  getusers()
    {

        $users = User::all();

        $success['status'] = 200;
        $success['message'] = 'User get successfully.';
        $success['data'] = $users;


        return response()->json(['success' => $success], $success['status']);
    }





    public function store_chat(Request $request)
    {
        $request->validate([
            'sender_id' => 'required',
            'receiver_id' => 'required',
        ]);

        $input = $request->all();

        $mediaPaths = [];

        if ($request->hasFile('media')) {

            foreach ($request->file('media') as $file) {
                $uploads_files = new \stdClass;
                $extension = $file->extension();

                if (in_array($extension, ['mp4', 'mpg', 'mp2', 'mpeg', 'mpe'])) {

                    $uploads_files->type = 'video';
                } else if (in_array($extension, ['jpg', 'png', 'gif', 'svg', 'webp'])) {
                    $uploads_files->type = 'images';
                } else {
                    $uploads_files->type = 'others';
                }

                $fileName = rand(1000, 9999) . '.' . $file->extension();
                $path = $file->storeAs('media', $fileName, 'public');
                $uploads_files->url = '/storage' . $path;
                $mediaPaths[] = $uploads_files;
            }

            $input['media'] = json_encode($mediaPaths);
        }

        $chat = Chat::create($input);


        $success['status'] = 200;
        $success['message'] = 'Chat stored successfully';
        $success['data'] = $chat;

        return response()->json(['success' => $success]);
    }




    public function get_chats($id, $reciverid)
    {
        $messages = Chat::where(function ($query) use ($reciverid, $id) {
            $query->where('sender_id', $id)
                ->where('receiver_id', $reciverid);
        })->orWhere(function ($query) use ($reciverid, $id) {
            $query->where('sender_id', $reciverid)
                ->where('receiver_id', $id);
        })->orderBy('created_at', 'desc')->get();

        foreach ($messages as $value) {

            $senderid = $value->sender_id;
            $receiverid = $value->receiver_id;


            // Initialize sender and receiver names
            $sendername = null;
            $receivername = null;

            // Get sender and receiver names
            $sender = User::where('id', $senderid)->first();
            $receiver = User::where('id', $receiverid)->first();


            if ($sender && $receiver) {

                if ($senderid == $id) {
                    $sendername = $sender->name;
                    $receivername = $receiver->name;
                } else {
                    $sendername = $receiver->name;
                    $receivername = $sender->name;
                }
            } else {
                // Handle the case where user(s) with the given ID(s) are not found
                // You can set default names or handle it according to your requirements
                $sendername = 'Sender Not Found';
                $receivername = 'Receiver Not Found';
            }

            // Set sender and receiver names
            $value->sender_name = $sendername;
            $value->receiver_name = $receivername;

            // Set message type
            if ($value->message != null) {
                $value->message_type = 'text';
            }

            if ($value->media != null) {

                $media_file = json_decode($value->media);
                foreach ($media_file as $media_type) {

                    $value->message_type = $media_type->type;
                    $value->media = $media_file;
                }
            }
            if ($value->audio != null) {
                $value->message_type = 'audio';
            }
        }

        $success['status'] = 200;
        $success['message'] = 'messages';
        $success['data'] = $messages;

        return response()->json(['success' => $success]);
    }




    public function inbox($id)
    {
        $latestMessages = Chat::whereIn(
            'id',
            function ($query) use ($id) {

                $query->select(Chat::raw('MAX(id)'))->from('chats')->where(function ($query) use ($id) {
                    $query->where('sender_id', $id)->orWhere('receiver_id', $id);
                })
                    ->groupBy(Chat::raw('CASE WHEN sender_id = ' . $id . ' THEN receiver_id ELSE sender_id END'));
            }
        )
            ->orderBy('created_at', 'desc')
            ->get();

        $data = [];
        $count = 0;

        foreach ($latestMessages as $message) {
            // Determine the other user's ID
            $otherUserId = ($message->sender_id == $id) ? $message->receiver_id : $message->sender_id;

            // Retrieve user based on the sender type
            $user = User::find($otherUserId);

            if ($user) {
                // Populate messageData with user information
                $messageData = [
                    'name' => $user->name,
                    'user_id' => $user->id,

                ];
            }

            // Count unread messages
            if ($message->status == 'unread') {
                $count++;
            }

            // Add additional message data to the array
            $data[] = array_merge($message->toArray(), $messageData);
        }

        // Prepare the response
        $success = [
            'lastMessages' => $data,
            'unreadCount' => $count,
        ];

        // Return the response as JSON
        $success['status'] = 200;
        return response()->json(['success' => $success]);
    }







    public function storeCard(Request $request)
    {

        $validatedData = $request->validate([
            'card_number' => 'required|string',
            'cvv' => 'required|string',
            'expiration_month' => 'required|string',
            'expiration_year' => 'required|string',

        ]);


        $paymentDetails =  PaymentDetails::create([
            'card_number' => encrypt($validatedData['card_number']),
            'cvv' => encrypt($validatedData['cvv']),
            'expiration_month' => $validatedData['expiration_month'],
            'expiration_year' => $validatedData['expiration_year'],
            'user_id' => $request->user_id,
            'name' => $request->name,
            'email' => $request->email,
        ]);


        $success['status'] = 200;
        $success['message'] = 'Card information added successfully.';
        $success['data'] = $paymentDetails;

        return response()->json(['success' => $success], $success['status']);
    }



    public function getdecript(Request $request)
    {
        $validatedData = $request->validate([
            'card_number' => 'required|string',
            'cvv' => 'required|string',
            'expiration_month' => 'required|string',
            'expiration_year' => 'required|string',
        ]);

        // Encrypt the card number
        $ecrypt = [
            '1' => '143@',
            '2' => '144@',
            '3' => '145@',
            '4' => '146@',
            '5' => '147@',
            '6' => '148@',
            '7' => '149@',
            '8' => '150@',
            '9' => '151@',
            '0' => '152@',
        ];

        $encrypted_card_number = '';
        foreach (str_split($request->card_number) as $digit) {
            if (isset($ecrypt[$digit])) {
                $encrypted_card_number .= $ecrypt[$digit];
            } else {
                $encrypted_card_number .= $digit;
            }
        }

        


        $success['status'] = 200;
        $success['message'] = 'Card information added successfully.';
        $success['data'] = $encrypted_card_number; 

        return response()->json(['success' => $success], $success['status']);
    }




    public function getCards(Request $request){

        $paymentDetails =  PaymentDetails::where('user_id', $request->user_id)->get();

        $success['status'] = 200;
        $success['message'] = 'Card information added successfully.';
        $success['data'] = $paymentDetails;

        return response()->json(['success' => $success], $success['status']);
    }



    public function deleteCard(Request $request){
            
    }





}
