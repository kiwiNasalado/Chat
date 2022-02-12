<?php

namespace app\models;

class Client
{
    private string $email;
    private string $color;
    private string $identifier;

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->identifier = $this->generateIdentifier();
    }

    private function generateIdentifier(): string
    {
        return sha1($this->email . time());
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }


}
