<?php

namespace App\OAuth\Bridge\Grant;

use App\Consts;
use DateInterval;
use League\OAuth2\Server\RequestEvent;
use App\OAuth\CustomRequestProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class CustomRequest extends AbstractGrant
{
    /**
     * The social user provider implementation.
     *
     * @var CustomRequestProviderInterface
    */
    protected $customRequestProvider;

    /**
     * Create a Social Grant instance.
     *
     * @param CustomRequestProviderInterface  $customRequestProvider
     * @param RefreshTokenRepositoryInterface  $refreshTokenRepository
     *
     * @return void
    */
    public function __construct(
        CustomRequestProviderInterface $customRequestProvider,
        RefreshTokenRepositoryInterface $refreshTokenRepository
    )
    {
        $this->customRequestProvider = $customRequestProvider;
        $this->setRefreshTokenRepository($refreshTokenRepository);
        $this->refreshTokenTTL = new \DateInterval('P1M');
    }

    /**
     * {@inheritdoc}
    */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ) {
        $client = $this->validateClient($request);

        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request, $this->defaultScope));

        $user = $this->validateUser($request);

        // Finalize the requested scopes
        $finalizedScopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $user->getIdentifier());

        // Issue and persist new tokens
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $user->getIdentifier(), $finalizedScopes);
        $refreshToken = $this->issueRefreshToken($accessToken);

        // Send events to emitter
        $this->getEmitter()->emit(new RequestEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request));
        $this->getEmitter()->emit(new RequestEvent(RequestEvent::REFRESH_TOKEN_ISSUED, $request));

        // Inject tokens into response
        $responseType->setAccessToken($accessToken);
        $responseType->setRefreshToken($refreshToken);

        return $responseType;
    }

    /**
     * Validate the user.
     *
     * @param ServerRequestInterface $request
     *
     * @throws OAuthServerException
     *
     * @return UserEntityInterface
     */
    protected function validateUser(ServerRequestInterface $request)
    {
        $username = $this->getRequestParameter('username', $request);

        if (is_null($username)) {
            throw OAuthServerException::invalidRequest('username');
        }

        // Get user from username
        $user = $this->customRequestProvider->getUserEntityUsername($username);

        if ($user instanceof UserEntityInterface === false) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidCredentials();
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return Consts::GRAND_TYPE_CUSTOM_REQUEST;
    }
}
