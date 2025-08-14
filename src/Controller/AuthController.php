<?php

namespace Messenger\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Messenger\Service\AuthService;

class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        
        if (!isset($data['username']) || !isset($data['password'])) {
            $result = ['success' => false, 'message' => 'Username and password are required'];
        } else {
            $result = $this->authService->register($data['username'], $data['password']);
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function login(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        
        if (!isset($data['username']) || !isset($data['password'])) {
            $result = ['success' => false, 'message' => 'Username and password are required'];
        } else {
            $result = $this->authService->login($data['username'], $data['password']);
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

