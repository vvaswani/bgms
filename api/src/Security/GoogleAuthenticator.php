<?php
namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(
        ClientRegistry $clientRegistry,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $jwtManager
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->jwtManager = $jwtManager;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $client->getAccessToken();
        $googleUser = $client->fetchUserFromToken($accessToken);

        $email = $googleUser->getEmail();
        $fullName = $googleUser->getName();
        if (!$email) {
            throw new AuthenticationException('Google did not return an email address.');
        }

        return new SelfValidatingPassport(
            new UserBadge($email, function (string $userIdentifier) use ($fullName) {
                $user = $this->userRepository->findOneBy(['email' => $userIdentifier]);
                if (!$user) {
                    $user = new User();
                    $user->setEmail($userIdentifier);
                    $user->setName($fullName);
                    $user->setCreatedAt(new \DateTimeImmutable());
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                }
                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $user = $token->getUser();
        $jwt = $this->jwtManager->create($user);
        $frontendUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173';
        return new RedirectResponse($frontendUrl . '?token=' . $jwt);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse(['error' => $exception->getMessageKey()], 401);
    }
}
