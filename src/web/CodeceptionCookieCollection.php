<?php

class CodeceptionCookieCollection extends CCookieCollection
{
    protected function addCookie($cookie)
    {
        $_COOKIE[$cookie->name] = $cookie->value;
    }

    protected function removeCookie($cookie)
    {
        unset($_COOKIE[$cookie->name]);
    }
}
