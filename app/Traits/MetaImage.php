<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

trait MetaImage
{
    /**
     * @param Request $request
     * @param Model $model
     */
    public function saveMetaImage(Request $request, Model $model): void
    {
        try {
            if ($request->hasFile('meta_image')) {
                $file = $request->file('meta_image');
                $file_name = $file->getClientOriginalName();
                $file_extension = $file->getClientOriginalExtension();
                $name = Str::slug(pathinfo($file_name, PATHINFO_FILENAME));
                $extension = Str::lower($file_extension);
                $url = $model->addMedia($file)
                    ->usingName($name)
                    ->usingFileName($name . '.' . $extension)
                    ->toMediaCollection('meta_image')
                    ->getFullUrl();
                // update user
                $model->update([
                    'meta_image' => $url
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }

    }
}
