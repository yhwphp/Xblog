<?php

namespace App\Http\Controllers;

use App\Http\Repositories\ImageRepository;
use App\Http\Repositories\UserRepository;
use App\Http\Requests;
use App\User;
use Gate;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $imageRepository;

    protected $userRepository;

    /**
     * UserController constructor.
     * @param UserRepository $userRepository
     * @param ImageRepository $imageRepository
     */
    public function __construct(UserRepository $userRepository, ImageRepository $imageRepository)
    {
        $this->userRepository = $userRepository;
        $this->imageRepository = $imageRepository;
        $this->middleware('auth', ['except' => 'show']);
    }

    public function show($name)
    {
        $user = $this->userRepository->get($name);
        return view('user.show', compact('user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $this->checkPolicy('manager', $user);
        $this->validate($request, [
            'description' => 'max:66',
        ]);

        if ($this->userRepository->update($request, $user)) {
            return back()->with('success', '修改成功');
        }
        return back()->with('success', '修改失败');
    }


    public function uploadProfile(Request $request)
    {
        $user = auth()->user();
        $key = 'user/' . $user->name . '/profile.' . $request->file('image')->guessClientExtension();

        if ($url = $this->uploadImage($user, $request, $key)) {
            $user->profile_image = $url;
        }
        if ($user->save()) {
            $this->userRepository->clearCache();
            return back()->with('success', '修改成功');
        }
        return back()->with('success', '修改失败');
    }

    public function uploadAvatar(Request $request)
    {
        $user = auth()->user();
        $key = 'user/' . $user->name . '/avatar.' . $request->file('image')->guessClientExtension();

        if ($url = $this->uploadImage($user, $request, $key)) {
            $user->avatar = $url;
        }
        if ($user->save()) {
            $this->userRepository->clearCache();
            return back()->with('success', '修改成功');
        }
        return back()->with('success', '修改失败');
    }

    private function uploadImage(User $user, Request $request, $key, $fileName = 'image')
    {
        $this->checkPolicy('manager', $user);

        $this->validate($request, [
            $fileName => 'required|image'
        ]);
        $image = $request->file($fileName);
        return $this->imageRepository->uploadImage($image, $key);
    }

}
