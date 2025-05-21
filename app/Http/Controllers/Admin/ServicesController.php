<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Services;
use App\Models\User;
use App\Models\subservices;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ServicesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Services::query();

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                if (request()->has('search')) {
                    $keyword = request()->get('search')['value'];

                    if (!empty($keyword)) {
                        $query->where(function ($sq) use ($keyword) {
                            $sq->where('services.name', 'like', "%" . $keyword . "%");
                        });
                    }
                }
            })
            ->addColumn('action', function ($data) {
                return '';
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    public function AllServices()
    {
        $services = Services::where('status', 1)->get();
        return response()->json([
            'services' => $services,
        ]);
    }

    public function AllServicesByLng(Request $request)
    {
        $services = Services::where('status', 1)->get();
        $result = [];
        foreach ($services as $service) {
            $res['name'] = ($request->lng == 'en') ? $service->name : $service->heb_name;
            $res['id']  = $service->id;
            $res['template'] = $service->template;
            $res['icon'] = $service->icon;
            array_push($result, $res);
        }

        return response()->json([
            'services' => $result,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $services = Services::query();
        $services = $services->where('status', 1)->orderBy('id', 'desc')->get();

        return response()->json([
            'services' => $services,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->input(), [
            'name' => 'required',
            'status' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        Services::create($request->input());
        return response()->json([
            'message' => 'Service has been created successfully'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Services  $services
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $service = Services::find($id);
        return response()->json([
            'service' => $service
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Services  $services
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->input(), [
            'name' => 'required',
            'status' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()]);
        }

        $service = Services::find($id);
        $service->update($request->input());

        return response()->json([
            'message' => 'Service has been updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Services  $services
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $service = Services::find($id);
        $service->delete();

        return response()->json([
            'message' => "Service has been deleted"
        ]);
    }

    public function getComments($id)
    {
        $comments = Comment::query()
            ->with('commenter', 'attachments')
            ->where('relation_type', Services::class)
            ->where('relation_id', $id)
            ->latest()
            ->get();

        $comments = $comments->map(function ($item, $key) {
            $commenter_name = NULL;
            if (get_class($item->commenter) == Admin::class) {
                $commenter_name = $item->commenter->name;
            } else if (get_class($item->commenter) == User::class) {
                $commenter_name = $item->commenter->firstname . ' ' . $item->commenter->lastname;
            } else if (get_class($item->commenter) == Client::class) {
                $commenter_name = $item->commenter->firstname . ' ' . $item->commenter->lastname;
            }
            $item->commenter_name = $commenter_name;
            return $item;
        });

        return response()->json([
            'comments' => $comments
        ]);
    }

    public function saveComment(Request $request, $id)
    {
        $service = Services::query()->find($id);

        if (!$service) {
            return response()->json([
                'message' => 'Service not found!',
            ], 404);
        }

        if (!$request->get('comment')) {
            return response()->json([
                'message' => 'Comment is required!',
            ], 404);
        }

        $comment = $service->comments()->create([
            'comment' => $request->get('comment'),
            'valid_till' => $request->get('valid_till')
        ]);

        $filesArr = $request->file('files');
        if ($request->hasFile('files') && count($filesArr) > 0) {
            if (!Storage::disk('public')->exists('uploads/attachments')) {
                Storage::disk('public')->makeDirectory('uploads/attachments');
            }
            $resultArr = [];
            foreach ($filesArr as $key => $file) {
                $original_name = $file->getClientOriginalName();
                $file_name = Str::uuid()->toString();
                $file_extension = $file->getClientOriginalExtension();
                $file_name = $file_name . '.' . $file_extension;

                if (Storage::disk('public')->putFileAs("uploads/attachments", $file, $file_name)) {
                    array_push($resultArr, [
                        'file_name' => $file_name,
                        'original_name' => $original_name
                    ]);
                }
            }
            $comment->attachments()->createMany($resultArr);
        }

        return response()->json([
            'message' => 'Comment is added successfully!',
        ]);
    }

    public function deleteComment($serviceID, $id)
    {
        $comment = Comment::query()
            ->whereHasMorph(
                'commenter',
                [Admin::class],
                function (Builder $query) {
                    $query->where('commenter_id', Auth::id());
                }
            )
            ->find($id);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found'
            ]);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment has been deleted successfully'
        ]);
    }

    public function addSubService(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_heb' => 'required|string|max:255',
            'apartment_size' => 'required|string|max:255',
            'price' => 'required|numeric',
        ]);

        try {
            $subService = subservices::create([
                'name_en' => $validatedData['name_en'],
                'name_heb' => $validatedData['name_heb'],
                'apartment_size' => $validatedData['apartment_size'],
                'price' => $validatedData['price'],
                'service_id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sub-service created successfully',
                'subService' => $subService
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create sub-service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSubServices($id)
    {
        $subServices = subservices::where('service_id', $id)->get();
        return response()->json([
            'success' => true,
            'subServices' => $subServices
        ]);
    }

    public function removeSubService($id)
    {
        try {
            $subService = subservices::findOrFail($id);
            if (!$subService) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sub-service not found'
                ]);
            }
            $subService->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sub-service removed successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove sub-service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function editSubService(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_heb' => 'required|string|max:255',
            'apartment_size' => 'required|string|max:255',
            'price' => 'required|numeric',
        ]);
        
        try {
            $subService = subservices::findOrFail($id);
            if (!$subService) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sub-service not found'
                ]);
            }
            $subService->update($validatedData);
    
            return response()->json([
                'success' => true,
                'message' => 'Sub-service updated successfully',
                'subService' => $subService
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sub-service',
                'error' => $th->getMessage() // Optionally return error message for debugging
            ], 500);
        }
    }
    
    
    
}
