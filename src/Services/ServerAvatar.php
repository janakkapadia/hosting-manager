<?php

namespace JanakKapadia\HostingManager\Services;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JanakKapadia\HostingManager\Enums\ServerAvatar\CreateSiteEnum;
use JanakKapadia\HostingManager\Traits\ClientRequest;

class ServerAvatar
{
    use ClientRequest;
    public PendingRequest $http;
    protected string $baseUrl = "https://api.serveravatar.com";
    protected array $headers;
    protected mixed $organizations;
    protected array $organization;

    protected array $routes = [];

    /**
     * @throws Exception
     */
    public function __construct(public string $token, public $organizationId = null)
    {
        $routes = Config::get('route.server_avatar');
        dd($routes);
        $this->headers = [
            'Authorization' => "Bearer $token"
        ];
    }

    /**
     * @throws Exception
     */
    public function getOrganizations(): void
    {
        try {
            $getOrganization = $this->get('organization.list');

            if ($getOrganization->failed()) {
                $errResponse = $getOrganization->json();
                throw new Exception($errResponse['message'], $getOrganization->status());
            }

            if (!$this->organizations->count()) {
                throw new Exception('No organization found!');
            }

        } catch (Exception $e) {
            Log::error('--server avatar get organizations--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getServers(int $paginate = 1): array
    {
        try {
            $getServersResponse = $this->http->get("$this->baseUrl/organizations/{$this->organization['id']}/servers?pagination=$paginate");
            $response = [];

            if ($getServersResponse->failed()) {
                $errResponse = $getServersResponse->json();
                throw new Exception($errResponse['message'], $getServersResponse->status());
            }

            $getServersResponse = $getServersResponse->collect('servers');
            $response['servers'] = collect($getServersResponse['data']);
            unset($getServersResponse['data']);
            $response['pagination'] = $getServersResponse;

            return $response;
        } catch (Exception $e) {
            Log::error('--server avatar get servers--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    /**
     * @param array $server
     * @param int $paginate
     * @return array
     * @throws Exception
     */
    public function getSites(array $server, int $paginate): array
    {
        try {
            $getSitesResponse = $this->http->get("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/applications?pagination=$paginate");

            if ($getSitesResponse->failed()) {
                $errResponse = $getSitesResponse->json();
                throw new Exception($errResponse['message'], $getSitesResponse->status());
            }
            $response = [];

            $getSitesResponse = $getSitesResponse->collect('applications');
            $response['servers'] = collect($getSitesResponse['data']);
            unset($getSitesResponse['data']);
            $response['pagination'] = $getSitesResponse;

            return $response;
        } catch (Exception $e) {
            Log::error('--server avatar get sites--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    /**
     * @param array $server
     * @param int $paginate
     * @return array
     * @throws Exception
     */
    public function getSite(array $server, $site): array
    {
        try {
            $getSiteResponse = $this->http->get("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/applications/{$site['id']}");

            if ($getSiteResponse->failed()) {
                $errResponse = $getSiteResponse->json();
                throw new Exception($errResponse['message'], $getSiteResponse->status());
            }

            return $getSiteResponse->json('application');
        } catch (Exception $e) {
            Log::error('--server avatar get sites--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    /**
     * @param array $server
     * @param string $method [custom, one_click, git]
     * @param string $framework [custom, bitbucket, github, gitlab, wordpress, mautic, moodle, joomla, prestashop]
     * @param array $siteParams
     * @param mixed $php_version
     * @return array
     * @throws Exception
     */
    public function createSite(array $server, string $method, string $framework, array $siteParams, mixed $php_version = '8.1'): array
    {
        try {
            if (!$method || !in_array($method, CreateSiteEnum::methods())) {
                throw new Exception('Method not available', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (!$framework || !in_array($framework, CreateSiteEnum::frameworks($method))) {
                throw new Exception('Framework not match with method', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $siteParams = [
                ...$siteParams,
                'method' => $method,
                'framework' => $framework,
                'php_version' => $siteParams['php_version'] ?? $php_version,
            ];

            $createSiteResponse = $this->http->post("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/applications", $siteParams);

            if ($createSiteResponse->failed()) {
                $errResponse = $createSiteResponse->json();
                throw new Exception($errResponse['message'], $createSiteResponse->status());
            }

            return $createSiteResponse->json('application');
        } catch (Exception $e) {
            Log::error('--server avatar create site--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    public function deleteSite($server, $site)
    {
        try {
            $deleteSiteResponse = $this->http->delete("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/applications/{$site['id']}");

            if ($deleteSiteResponse->failed()) {
                $errResponse = $deleteSiteResponse->json();
                throw new Exception($errResponse['message'], $deleteSiteResponse->status());
            }

            return $deleteSiteResponse->json();
        } catch (Exception $e) {
            Log::error('--error--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            throw $e;
        }
    }

    public function getSystemUsers($server, $paginate = 1): array
    {
        try {
            $getSystemUsersResponse = $this->http->get("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/system-users?pagination=$paginate");

            if ($getSystemUsersResponse->failed()) {
                $errResponse = $getSystemUsersResponse->json();
                throw new Exception($errResponse['message'], $getSystemUsersResponse->status());
            }
            $response = [];

            $getSystemUsers = $getSystemUsersResponse->collect('systemUsers');
            $response['servers'] = collect($getSystemUsers['data']);
            unset($getSystemUsers['data']);
            $response['pagination'] = $getSystemUsers;

            return $response;
        } catch (Exception $e) {
            Log::error('--server avatar get system users--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    public function getSystemUser($server, $system_user, $paginate = 1)
    {
        try {
            $getSystemUserResponse = $this->http->get("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/system-users/{$system_user['id']}?pagination=$paginate");

            if ($getSystemUserResponse->failed()) {
                $errResponse = $getSystemUserResponse->json();
                throw new Exception($errResponse['message'], $getSystemUserResponse->status());
            }

            return $getSystemUserResponse->json('systemUser');
        } catch (Exception $e) {
            Log::error('--server avatar get system user--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    public function createSystemUser($server, string $username, string $password, string $public_key = null)
    {
        try {
            $createSystemUserBody = [
                'username' => $username,
                'password' => $password,
            ];

            if ($public_key) {
                $createSystemUserBody['public_key'] = $public_key;
            }

            $createSystemUserResponse = $this->http->post("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/system-users", $createSystemUserBody);

            if ($createSystemUserResponse->failed()) {
                $errResponse = $createSystemUserResponse->json();
                throw new Exception($errResponse['message'], $createSystemUserResponse->status());
            }

            return $createSystemUserResponse->json('systemUser');
        } catch (Exception $e) {
            Log::error('--server avatar create system users--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    public function updateSystemUser($server, $system_user, string $username, string $password = null, string $public_key = null)
    {
        try {
            $updateSystemUserBody = [];

            if ($password) {
                $updateSystemUserBody['password'] = $password;
            }

            if ($public_key) {
                $updateSystemUserBody['public_key'] = $public_key;
            }

            $updateSystemUserResponse = $this->http->patch("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/system-users/{$system_user['id']}", $updateSystemUserBody);

            if ($updateSystemUserResponse->failed()) {
                $errResponse = $updateSystemUserResponse->json();
                throw new Exception($errResponse['message'], $updateSystemUserResponse->status());
            }

            return $updateSystemUserResponse->json('systemUser');
        } catch (Exception $e) {
            Log::error('--server avatar updateSystemUser--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    public function toggleSsh($server, $system_user)
    {
        try {
            $toggleSshResponse = $this->http->get("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/system-users/{$system_user['id']}/ssh-access");

            if ($toggleSshResponse->failed()) {
                $errResponse = $toggleSshResponse->json();
                throw new Exception($errResponse['message'], $toggleSshResponse->status());
            }

            return $toggleSshResponse->json();
        } catch (Exception $e) {
            Log::error('--server avatar toggle ssh--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    public function removeSshKey($server, $system_user)
    {
        try {
            $removeSshKeyResponse = $this->http->get("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/system-users/{$system_user['id']}/ssh-key-remove");

            if ($removeSshKeyResponse->failed()) {
                $errResponse = $removeSshKeyResponse->json();
                throw new Exception($errResponse['message'], $removeSshKeyResponse->status());
            }

            return $removeSshKeyResponse->json();
        } catch (Exception $e) {
            Log::error('--server avatar remove ssh key--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    public function installSsl($server, $site, $ssl_type = 'automatic', $force_https = true, $ssl_certificate = null, $private_key = null, $chain_file = null)
    {
        try {
            $installSslParams = [
                'ssl_type' => $ssl_type,
                'force_https' => $force_https
            ];

            if ($ssl_type === 'custom') {
                $ssl_certificate = str_replace("----- ", "-----\n", $ssl_certificate);
                $ssl_certificate = str_replace(" -----", "\n-----", $ssl_certificate);

                $private_key = str_replace("----- ", "-----\n", $private_key);
                $private_key = str_replace(" -----", "\n-----", $private_key);

                $installSslParams['ssl_certificate'] = $ssl_certificate;
                $installSslParams['private_key'] = $private_key;
            }

            if ($chain_file) {
                $chain_file = str_replace("----- ", "-----\n", $chain_file);
                $chain_file = str_replace(" -----", "\n-----", $chain_file);
                $installSslParams['chain_file'] = $chain_file;
            }

            $installSslResponse = $this->http->post("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/applications/{$site['id']}/ssl", $installSslParams);

            if ($installSslResponse->failed()) {
                $errResponse = $installSslResponse->json();
                throw new Exception($errResponse['message'], $installSslResponse->status());
            }

            return $installSslResponse->json();
        } catch (Exception $e) {
            Log::error('--server avatar install ssl--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    public function getSiteDomains($server, $site, $paginate = 1): array
    {
        try {
            $getSiteDomainResponse = $this->http->get("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/applications/{$site['id']}/application-domains?page=$paginate");

            if ($getSiteDomainResponse->failed()) {
                $errResponse = $getSiteDomainResponse->json();
                throw new Exception($errResponse['message'], $getSiteDomainResponse->status());
            }

            $response = [];

            $getSiteDomain = $getSiteDomainResponse->collect('applicationDomains');
            $response['servers'] = collect($getSiteDomain['data']);
            unset($getSiteDomain['data']);
            $response['pagination'] = $getSiteDomain;

            return $response;

        } catch (Exception $e) {
            Log::error('--server avatar getSiteDomains--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    public function createSiteDomain($server, $site, $domain)
    {
        try {
            $createSiteDomainResponse = $this->http->post("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/applications/{$site['id']}/application-domains", ['domain' => $domain]);

            if ($createSiteDomainResponse->failed()) {
                $errResponse = $createSiteDomainResponse->json();
                throw new Exception($errResponse['message'], $createSiteDomainResponse->status());
            }

            return $createSiteDomainResponse->json('application_domain');
        } catch (Exception $e) {
            Log::error('--server avatar createSiteDomain--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    public function deleteSiteDomain($server, $site)
    {
        try {
            $deleteSiteDomainResponse = $this->http->delete("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/applications/{$site['id']}/application-domains");

            if ($deleteSiteDomainResponse->failed()) {
                $errResponse = $deleteSiteDomainResponse->json();
                throw new Exception($errResponse['message'], $deleteSiteDomainResponse->status());
            }

            return $deleteSiteDomainResponse->json();
        } catch (Exception $e) {
            Log::error('--server avatar deleteSiteDomain--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }

    public function setPrimaryDomain($server, $site, $domain, $params = [])
    {
        try {
            $setPrimarySiteDomainResponse = $this->http->patch("$this->baseUrl/organizations/{$this->organization['id']}/servers/{$server['id']}/applications/{$site['id']}/application-domains/{$domain['id']}", $params);

            if ($setPrimarySiteDomainResponse->failed()) {
                $errResponse = $setPrimarySiteDomainResponse->json();
                throw new Exception($errResponse['message'], $setPrimarySiteDomainResponse->status());
            }

            return $setPrimarySiteDomainResponse->json();
        } catch (Exception $e) {
            Log::error('--server avatar setPrimaryDomain--', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }
}