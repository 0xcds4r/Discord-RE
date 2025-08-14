<?php

namespace Messenger\Service;

use Messenger\Entity\User;
use Doctrine\ORM\EntityManager;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    private EntityManager $entityManager;
    private string $jwtSecret;
    private string $jwtAlgorithm;

    public function __construct(EntityManager $entityManager, string $jwtSecret, string $jwtAlgorithm = 'HS256')
    {
        $this->entityManager = $entityManager;
        $this->jwtSecret = $jwtSecret;
        $this->jwtAlgorithm = $jwtAlgorithm;
    }

    public function register(string $username, string $password): array
    {
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser) {
            return ['success' => false, 'message' => 'Username already exists'];
        }

        $user = new User();
        $user->setUsername($username);
        $user->setPasswordHash(password_hash($password, PASSWORD_DEFAULT));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->generateToken($user);

        return [
            'success' => true,
            'message' => 'User registered successfully',
            'user_id' => $user->getId(),
            'token' => $token
        ];
    }

    public function login(string $username, string $password): array
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        
        if (!$user || !password_verify($password, $user->getPasswordHash())) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        $user->setLastActiveAt(new \DateTime());
        $this->entityManager->flush();

        $token = $this->generateToken($user);

        return [
            'success' => true,
            'message' => 'Login successful',
            'user_id' => $user->getId(),
            'token' => $token
        ];
    }

    public function validateToken(string $token): ?User
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, $this->jwtAlgorithm));
            $userId = $decoded->user_id;
            
            $user = $this->entityManager->find(User::class, $userId);
            return $user;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateToken(User $user): string
    {
        $payload = [
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60)
        ];

        return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
    }
}

