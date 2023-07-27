<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Client;
use App\Models\LeadComment;
use App\Models\LeadStatus;
use App\Models\Offer;
use App\Models\Schedule;
use App\Models\WebhookResponse;
use App\Models\WhatsappLastReply;
use Illuminate\Support\Facades\Hash;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $q = $request->q;
        $c = $request->condition;

        $result = Client::with('meetings','offers')->with('lead_status');

        if (!is_null($q) &&  ($q !== 1 && $q !== 0 && $q != 'all') && $c != 'filter') {

            $result->where(function ($query) use ($q) {
                $query->where('email',       'like', '%' . $q . '%')
                     ->orWhere('firstname',       'like', '%' . $q . '%')
                    ->orWhere('phone',       'like', '%' . $q . '%');
            });
        }


        if (!is_null($q) &&  ($q == 1 || $q == 0)) {

            $result->where('status', $q);

        } else if( is_null($q) ) {

            $result->where('status', '0')->orWhere('status', '1');
        }

        if( $q == 'pending'){

            $result = $result->WhereHas('lead_status', function ($q) {
                $q->where(function ($q) {
                  $q->where('lead_status', 'Pending');
                });
              })->orWhereDoesntHave('lead_status');
        }

        if( $q == 'set'){

            $result = $result->WhereHas('lead_status', function ($q) {
                $q->where(function ($q) {
                  $q->where('lead_status', 'Meeting Set');
                });
              });
        }

        if( $q == 'offersend'){

            $result = $result->WhereHas('lead_status', function ($q) {
                $q->where(function ($q) {
                  $q->where('lead_status', 'Offer Sent');
                });
              });
        }

        if( $q == 'offerdecline'){

            $result = $result->WhereHas('lead_status', function ($q) {
                $q->where(function ($q) {
                  $q->where('lead_status', 'Offer Rejected');
                });
              });
        }

        $result = $result->where('status','!=',2);
        
        if( $q == 'uninterested'){
            
            $result = $result->WhereHas('lead_status', function ($q) {
                $q->where(function ($q) {
                  $q->where('lead_status', 'Uninterested');
                });
              });


        } 
            $result = $result->orderBy('id', 'desc')->paginate(20);

        return response()->json([
            'leads'       => $result,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone'     => ['required'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:clients'],
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }
        $lead                = new Client;
        $lead->firstname     = $request->name;
        $lead->phone         = $request->phone;
        $lead->email         = $request->email;
        $lead->geo_address   = $request->address;
        $lead->status        = 0;
        $lead->password      = Hash::make($request->phone);
        $lead->extra         = $request->meta;
        $lead->save();

        LeadStatus::UpdateOrCreate(
            [
              'client_id' => $lead->id
            ],
            [
              'client_id' => $lead->id,
              'lead_status' => 'Pending'
            ]
          );

        return response()->json([
            'message'       => 'Lead created successfully',            
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,$id)
    {
        $lead                = Client::find($id);
        $lead->lead_status   =$request->lead_status;
        $lead->save();
        return response()->json([
            'message'        => 'status updated',            
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $lead                = Client::with('offers','meetings','lead_status')->find($id);

        if( !empty($lead) ){

            $offer = Offer::where('client_id', $id)->get()->last();
            $lead->latest_offer = $offer;

            $meeting = Schedule::where('client_id', $id)->get()->last();
            $lead->latest_meeting = $meeting;

            $reply  = ($lead->phone != NULL && $lead->phone != '' && $lead->phone != 0) ?
                        WhatsappLastReply::where('phone','like','%'.$lead->phone.'%')
                                                    
                                ->get()->first() : null;

            if( !empty($reply) )
            $reply->msg = WebhookResponse::getWhatsappMessage('message_'.$reply->message,'en');
            $lead->reply = $reply;

        }
        return response()->json([
            'lead'        => $lead,            
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone'     => ['required'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:clients,email,' . $id],
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }
        $lead                = Client::find($id);
        $lead->firstname     = $request->name;
        $lead->phone         = $request->phone;
        $lead->email         = $request->email;
        $lead->geo_address   = $request->address;
        $lead->status        = 0;
        $lead->password      = Hash::make($request->phone);
        $lead->extra         = $request->meta;
        $lead->save();

        return response()->json([
            'message'       => 'Lead updated successfully',            
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
         Client::find($id)->delete();
        return response()->json([
            'message'     => "Lead has been deleted"         
        ], 200);
    }
     public function updateStatus(Request $request,$id)
    {
        
        return response()->json([
            'message'        => 'status updated',            
        ], 200);
    }
    public function addComment(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'comment'     => 'required',
            'lead_id'  => 'required',
            'team_id'  => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }
        LeadComment::create([
            'comment'   => $request->comment,
            'lead_id' => $request->lead_id,
            'team_id' => $request->team_id
        ]);
        return response()->json(['message' => 'comment added']);
    }

    public function getComments(Request $request)
    {
        $comments = LeadComment::where('lead_id',$request->id)->with('team')->get();
        return response()->json(['comments' => $comments]);
    }

    public function deleteComment(Request $request)
    {
        LeadComment::where(['id' => $request->id])->delete();
        return response()->json(['message' => 'comment deleted']);
    }

    public function uninterested( $id ){

       LeadStatus::UpdateOrCreate(
         [
            'client_id' => $id
         ],[
            'client_id'   => $id,
            'lead_status' => 'Uninterested'
        ]
       );
       return response()->json(['message' => 'Marked Uninterested']);
    }
}