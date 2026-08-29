<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\AgentModel;

class AgentAuthController extends Controller
{
    public function index()
    {
        return view('agent_login');
    }

    public function login()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $agentModel = new AgentModel();

        // Support login by email OR by agent_name
        $agent = $agentModel->verifyCredentials($username, $password);

        // Fallback: try matching agent_name directly with plain password
        // (for demo accounts that don't use bcrypt yet)
       // Fallback: try matching agent_name (only if account is active)
if ($agent === null) {
    $found = $agentModel
        ->where('agent_name', $username)
        ->where('Active', 1)
        ->first();

    if ($found && ($found['password'] === $password || password_verify($password, $found['password']))) {
        $agent = $found;
    }
}

        if ($agent !== null) {

            session()->set([
                'user_id'   => $agent['id'],
                'name'      => $agent['agent_name'],
                'role'      => 'agent',
                'logged_in' => true,
            ]);



            // Mark agent online
            $agentModel->setOnline($agent['id'], 1);
if ($agent['id'] == 2) {
    return redirect()->to('/livechat/dashboard1');
} else {
    return redirect()->to('/agent/livechat');
}

      }

        return redirect()->back()->with('error', 'Invalid Username or Password');
    }

    public function logout()
    {
        // Task 4.2: mark the agent offline before the session is destroyed
        $userId = session()->get('user_id');

        if ($userId) {
            $agentModel = new AgentModel();
            $agentModel->setOnline($userId, 0);
        }

        session()->destroy();

        return redirect()->to('/login');
    }
}