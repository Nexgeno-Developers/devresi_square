<?php

namespace App\Http\Controllers\Backend;

use App\Models\Upload;
use App\Services\SecureUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class AizUploadController
{
    public function index(Request $request)
    {
        $user = current_user();

        $all_uploads = ($user->user_type == 'seller') ? Upload::where('user_id', $user->id) : Upload::query();
        $this->scopeUploadQuery($all_uploads);
        $search = null;
        $sort_by = null;

        if ($request->search != null) {
            $search = $request->search;
            $all_uploads->where('file_original_name', 'like', '%' . $request->search . '%');
        }

        $sort_by = $request->sort;
        switch ($request->sort) {
            case 'newest':
                $all_uploads->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $all_uploads->orderBy('created_at', 'asc');
                break;
            case 'smallest':
                $all_uploads->orderBy('file_size', 'asc');
                break;
            case 'largest':
                $all_uploads->orderBy('file_size', 'desc');
                break;
            default:
                $all_uploads->orderBy('created_at', 'desc');
                break;
        }

        $all_uploads = $all_uploads->paginate(60)->appends(request()->query());


        return ($user->user_type == 'seller')
            ? view('seller.uploads.index', compact('all_uploads', 'search', 'sort_by'))
            : view('backend.uploaded_files.index', compact('all_uploads', 'search', 'sort_by'));
    }

    public function create()
    {
        $user = current_user();
        return ($user->user_type == 'seller')
            ? view('seller.uploads.create')
            : view('backend.uploaded_files.create');
    }


    public function show_uploader(Request $request)
    {
        return view('uploader.aiz-uploader');
    }
    public function upload(Request $request)
    {
        if (! $request->hasFile('aiz_file')) {
            return '{}';
        }

        $file = $request->file('aiz_file');
        $service = app(SecureUploadService::class);

        try {
            $stored = $service->store($file);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Upload rejected.',
            ], 422);
        }

        if (env('DEMO_MODE') == 'On' && $stored['type'] === 'archive') {
            $service->delete($stored['path']);

            return '{}';
        }

        $size = $stored['size'];
        $relative = $service->resolveRelativePath($stored['path']);

        if ($stored['type'] === 'image' && ! $stored['private']) {
            try {
                $fullPath = Storage::disk('public')->path($relative);
                $img = Image::make($fullPath)->encode();
                $height = $img->height();
                $width = $img->width();
                if ($width > $height && $width > 1500) {
                    $img->resize(1500, null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                } elseif ($height > 1500) {
                    $img->resize(null, 800, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                }
                $img->save($fullPath);
                clearstatcache();
                $size = (int) filesize($fullPath);
            } catch (\Exception $e) {
                // Keep original stored image if resize fails.
            }
        }

        $original = pathinfo($stored['original_name'], PATHINFO_FILENAME);

        $upload = new Upload;
        $upload->account_id = current_account_id();
        $upload->file_original_name = $original !== '' ? $original : 'file';
        $upload->extension = $stored['extension'];
        $upload->file_name = $stored['path'];
        $upload->user_id = Auth::id();
        $upload->type = $stored['type'];
        $upload->file_size = $size;
        $upload->save();

        return '{}';
    }

    public function get_uploaded_files(Request $request)
    {
        $user = Auth::user();
        $uploads = ($user && $user->user_type != 'super_admin') ? Upload::where('user_id', $user->id) : Upload::query();
        $this->scopeUploadQuery($uploads);
        // $uploads = Upload::where('user_id', Auth::user()->id);
        if ($request->search != null) {
            $uploads->where('file_original_name', 'like', '%' . $request->search . '%');
        }
        if ($request->sort != null) {
            switch ($request->sort) {
                case 'newest':
                    $uploads->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $uploads->orderBy('created_at', 'asc');
                    break;
                case 'smallest':
                    $uploads->orderBy('file_size', 'asc');
                    break;
                case 'largest':
                    $uploads->orderBy('file_size', 'desc');
                    break;
                default:
                    $uploads->orderBy('created_at', 'desc');
                    break;
            }
        }
        return $uploads->paginate(60)->appends(request()->query());
    }

    /*public function destroy($id)
    {
        $upload = Upload::findOrFail($id);
        ensureModelBelongsToCurrentAccount($upload);
        $user = current_user();
        if ($user->user_type == 'seller' && $upload->user_id != $user->id) {
            flash("You don't have permission for deleting this!")->error();
            return back();
        }
        try {
            if (env('FILESYSTEM_DRIVER') == 's3') {
                Storage::disk('s3')->delete($upload->file_name);
                if (file_exists(public_path() . '/' . $upload->file_name)) {
                    unlink(public_path() . '/' . $upload->file_name);
                }
            } else {
                unlink(public_path() . '/' . $upload->file_name);
            }
            $upload->delete();
            flash('File deleted successfully')->success();
        } catch (\Exception $e) {
            $upload->delete();
            flash('File deleted successfully')->success();
        }
        return back();
    }*/

    public function destroy($id)
    {
        $upload = Upload::findOrFail($id);
        ensureModelBelongsToCurrentAccount($upload);

        $usesSoftDeletes = in_array(
            SoftDeletes::class,
            class_uses($upload)
        );
        try {
            if (! $usesSoftDeletes) {
                app(SecureUploadService::class)->delete((string) $upload->file_name);

                $directPublic = public_path($upload->file_name);
                if (is_string($upload->file_name) && ! str_starts_with($upload->file_name, 'private:') && file_exists($directPublic)) {
                    @unlink($directPublic);
                }
            }
            $upload->delete();
            $response = [
                'message' => 'File deleted successfully'
            ];
        } catch (\Exception $e) {
            $upload->delete();
            $response = [
                'message' => 'File deleted successfully'
            ];
        }
        return $response;
    }

    public function bulk_uploaded_files_delete(Request $request)
    {
        if ($request->id) {
            foreach ($request->id as $file_id) {
                $this->destroy($file_id);
            }
            return 1;
        } else {
            return 0;
        }
    }

    public function get_preview_files(Request $request)
    {
        $ids = explode(',', $request->ids);
        $filesQuery = Upload::whereIn('id', $ids);
        $this->scopeUploadQuery($filesQuery);
        $files = $filesQuery->get();
        // Reorder the files based on the original IDs array
        $files = $files->sortBy(function($file) use ($ids) {
            return array_search($file->id, $ids); // Find the index of the file id in the original ids array
        });
        $new_file_array = [];
        foreach ($files as $file) {
            if ($file->external_link) {
                $file['file_name'] = $file->external_link;
            } elseif (is_string($file->file_name) && str_starts_with($file->file_name, 'private:')) {
                $file['file_name'] = route('download_attachment', $file->id);
            } else {
                $file['file_name'] = my_asset($file->file_name);
            }
            $new_file_array[] = $file;
        }
        // dd($new_file_array);
        return $new_file_array;
        // return $files;
    }

    public function all_file()
    {
        abort_unless(auth()->user()?->hasRole('Super Admin'), 403);

        $service = app(SecureUploadService::class);
        $uploads = Upload::withTrashed()->get();
        foreach ($uploads as $upload) {
            try {
                $service->delete((string) $upload->file_name);
            } catch (\Exception $e) {
                // Continue wiping records even if a blob is already gone.
            }
            $upload->forceDelete();
        }

        Upload::query()->truncate();

        return back();
    }

    //Download project attachment
    public function attachment_download($id)
    {
        $project_attachment = Upload::find($id);
        abort_unless($project_attachment, 404);
        ensureModelBelongsToCurrentAccount($project_attachment);

        $service = app(SecureUploadService::class);
        $stored = (string) $project_attachment->file_name;
        $relative = $service->resolveRelativePath($stored);
        $disk = $service->resolveDisk($stored);

        try {
            if (Storage::disk($disk)->exists($relative)) {
                $downloadName = ($project_attachment->file_original_name ?: 'file')
                    .($project_attachment->extension ? '.'.$project_attachment->extension : '');

                return Storage::disk($disk)->download($relative, $downloadName);
            }

            // Legacy public-path uploads.
            $legacy = public_path($stored);
            abort_unless(is_file($legacy), 404);

            return Response::download($legacy);
        } catch (\Exception $e) {
            flash('File does not exist!')->error();
            return back();
        }
    }
    //Download project attachment
    public function file_info(Request $request)
    {
        $file = Upload::findOrFail($request['id']);
        ensureModelBelongsToCurrentAccount($file);
        $user = current_user();
        return ($user->user_type == 'seller')
            ? view('seller.uploads.info', compact('file'))
            : view('backend.uploaded_files.info', compact('file'));
    }

    private function scopeUploadQuery($query)
    {
        if (! auth()->user()?->hasRole('Super Admin')) {
            $query->forAccount(current_account_id());
        }

        return $query;
    }
}
