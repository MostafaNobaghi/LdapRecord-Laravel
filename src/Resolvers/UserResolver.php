<?php

namespace Adldap\Laravel\Resolvers;

use Adldap\Models\User;
use Adldap\Laravel\Auth\DatabaseUserProvider;
use Adldap\Laravel\Auth\NoDatabaseUserProvider;
use Adldap\Connections\ProviderInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Auth\Authenticatable;

class UserResolver implements ResolverInterface
{
    /**
     * The LDAP connection provider.
     *
     * @var ProviderInterface
     */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    public function __construct(ProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function byId($identifier)
    {
        return $this->query()->findByGuid($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function byCredentials(array $credentials = [])
    {
        if (empty($credentials)) {
            return;
        }

        $field = $this->getLdapDiscoveryAttribute();

        switch ($this->getUserProvider()) {
            case NoDatabaseUserProvider::class:
                $username = $credentials[$this->getLdapDiscoveryAttribute()];
                break;
            default:
                $username = $credentials[$this->getEloquentUsernameAttribute()];
                break;
        }

        return $this->query()->whereEquals($field, $username)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function byModel(Authenticatable $model)
    {
        $field = $this->getLdapDiscoveryAttribute();

        $username = $model->{$this->getEloquentUsernameAttribute()};

        return $this->query()->whereEquals($field, $username)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(User $user, array $credentials = [])
    {
        $attribute = $user->getAttribute($this->getLdapAuthAttribute());

        $username = is_array($attribute) ? array_first($attribute) : $attribute;

        return $this->provider->auth()->attempt($username, $credentials['password']);
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        $query = $this->provider->search()->users();

        foreach ($this->getScopes() as $scope) {
            // Create the scope.
            $scope = app($scope);

            // Apply it to our query.
            $scope->apply($query);
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapDiscoveryAttribute()
    {
        return Config::get('adldap_auth.usernames.ldap.discover', 'userprincipalname');
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapAuthAttribute()
    {
        return Config::get('adldap_auth.usernames.ldap.authenticate', 'userprincipalname');
    }

    /**
     * {@inheritdoc}
     */
    public function getEloquentUsernameAttribute()
    {
        return Config::get('adldap_auth.usernames.eloquent', 'email');
    }

    /**
     * Returns the configured query scopes.
     *
     * @return array
     */
    protected function getScopes()
    {
        return Config::get('adldap_auth.scopes', []);
    }

    /**
     * Returns the configured LDAP user provider.
     *
     * @return string
     */
    protected function getUserProvider()
    {
        return Config::get('adldap_auth.provider', DatabaseUserProvider::class);
    }
}
