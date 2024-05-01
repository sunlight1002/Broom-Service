<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\ClientPropertyAddress;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ClientPropertyAddressController extends Controller
{
    public function getComments($id)
    {
        $comments = Comment::query()
            ->with('commenter', 'attachments')
            ->where('relation_type', ClientPropertyAddress::class)
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
        $address = ClientPropertyAddress::query()->find($id);

        if (!$address) {
            return response()->json([
                'message' => 'Property address not found!',
            ], 404);
        }

        if (!$request->get('comment')) {
            return response()->json([
                'message' => 'Comment is required!',
            ], 404);
        }

        $comment = $address->comments()->create([
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
}
