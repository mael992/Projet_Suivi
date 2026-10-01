<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Derrière le tunnel Cloudflare, l'adresse TCP vue par le serveur est celle du
 * conteneur cloudflared, pas celle du visiteur. L'adresse réelle est fournie par
 * Cloudflare dans l'en-tête « CF-Connecting-IP », que Cloudflare réécrit à chaque
 * requête : un visiteur ne peut donc pas l'usurper (au contraire de
 * « X-Forwarded-For », qui lui reste modifiable et n'est plus utilisé pour l'IP).
 *
 * On recopie cette IP dans REMOTE_ADDR pour que request()->ip() — et donc la
 * limite de tentatives de connexion et les journaux — repose sur une IP fiable.
 */
class SetCloudflareClientIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->headers->get('CF-Connecting-IP');

        if ($ip !== null && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
            $request->server->set('REMOTE_ADDR', $ip);
        }

        return $next($request);
    }
}
