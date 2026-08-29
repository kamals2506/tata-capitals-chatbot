<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

/**
 * AgentAuthFilter — Guards agent routes by verifying an active agent session.
 *
 * - AJAX / JSON requests receive a 401 JSON response when unauthenticated.
 * - HTML (browser) requests are redirected to /login.
 */
class AgentAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->has('user_id') || session()->get('role') !== 'agent') {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON(['success' => false, 'message' => 'Unauthorized']);
            }

            return redirect()->to('/login');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }
}
