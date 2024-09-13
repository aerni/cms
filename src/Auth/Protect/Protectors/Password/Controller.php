<?php

namespace Statamic\Auth\Protect\Protectors\Password;

use Statamic\Facades\Site;
use Statamic\Http\Controllers\Controller as BaseController;
use Statamic\View\View;

class Controller extends BaseController
{
    protected $tokenData;
    protected $password;

    public function show()
    {
        if ($this->tokenData = session('statamic:protect:password.tokens.'.request('token'))) {
            $site = Site::findByUrl($this->getUrl());

            app()->setLocale($site->lang());
        }

        return View::make('statamic::auth.protect.password');
    }

    public function store()
    {
        $this->password = request('password');
        $this->tokenData = session('statamic:protect:password.tokens.'.request('token'));

        if (! $this->tokenData) {
            return back()->withErrors(['token' => __('statamic::messages.password_protect_token_invalid')], 'passwordProtect');
        }

        $guard = new Guard($this->getValidPasswords());

        if (! $guard->check($this->password)) {
            return back()->withErrors(['password' => __('statamic::messages.password_protect_incorrect_password')], 'passwordProtect');
        }

        return $this
            ->storePassword()
            ->expireToken()
            ->redirect();
    }

    protected function getScheme()
    {
        return $this->tokenData['scheme'];
    }

    protected function getUrl()
    {
        return $this->tokenData['url'];
    }

    protected function getId()
    {
        return $this->tokenData['id'];
    }

    protected function getValidPasswords()
    {
        return $this->tokenData['valid_passwords'];
    }

    protected function getLocalPassword()
    {
        return $this->tokenData['local_password'];
    }

    protected function storePassword()
    {
        $sessionKey = $this->password === $this->getLocalPassword()
            ? "statamic:protect:password.passwords.{$this->getId()}"
            : "statamic:protect:password.passwords.{$this->getScheme()}";

        session()->put($sessionKey, $this->password);

        return $this;
    }

    protected function expireToken()
    {
        $token = request('token');

        session()->forget("statamic:protect:password.tokens.$token");

        return $this;
    }

    protected function redirect()
    {
        return redirect($this->tokenData['url']);
    }
}
