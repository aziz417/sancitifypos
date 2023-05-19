<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

trait ProfileImage
{
    /**
     * @param Request $request
     * @param Model $model
     */
    public function saveAvatar(Request $request, Model $model): void
    {
        try {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $file_name = $file->getClientOriginalName();
                $file_extension = $file->getClientOriginalExtension();
                $name = Str::slug(pathinfo($file_name, PATHINFO_FILENAME));
                $extension = Str::lower($file_extension);
                $url = $model->addMedia($file)
                    ->usingName($name)
                    ->usingFileName($name . '.' . $extension)
                    ->toMediaCollection('avatar')
                    ->getFullUrl();
                // update user
                $model->update([
                    'avatar' => $url
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }

    }
}
