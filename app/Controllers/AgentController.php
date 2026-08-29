<?php
namespace App\Controllers;

use App\Models\LiveChatAgentModel;

class AgentController extends BaseController
{
    protected $agentModel;

    public function __construct()
    {
        $this->agentModel = new LiveChatAgentModel();
    }

public function index()
{
    if (! session()->get('logged_in')) {
        return redirect()->to(site_url('login'));
    }

    $data['agents'] = $this->agentModel->orderBy('created_at', 'DESC')->findAll();
    return view('agents/index', $data);
}


    public function create()
    {
        return view('agents/create');
    }

    public function store()
    {
        $rules = [
            'agent_name' => 'required|min_length[3]|max_length[100]',
            'email'      => 'required|valid_email',
            'password'   => 'required|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($this->agentModel->emailExists($this->request->getPost('email'))) {
            return redirect()->back()->withInput()->with('error', 'Email already exists.');
        }

        $this->agentModel->insert([
            'agent_name' => $this->request->getPost('agent_name'),
            'email'      => $this->request->getPost('email'),
            'password'   => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'is_online'  => $this->request->getPost('is_online') ? 1 : 0,
        ]);

        return redirect()->to('/agents')->with('success', 'Agent created successfully.');
    }

    public function edit($id)
    {
        $data['agent'] = $this->agentModel->find($id);

        if (! $data['agent']) {
            return redirect()->to('/agents')->with('error', 'Agent not found.');
        }

        return view('agents/edit', $data);
    }

    public function update($id)
    {
        $agent = $this->agentModel->find($id);
        if (! $agent) {
            return redirect()->to('/agents')->with('error', 'Agent not found.');
        }

        $rules = [
            'agent_name' => 'required|min_length[3]|max_length[100]',
            'email'      => 'required|valid_email',
        ];

        if ($this->request->getPost('password')) {
            $rules['password'] = 'min_length[6]';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($this->agentModel->emailExists($this->request->getPost('email'), $id)) {
            return redirect()->back()->withInput()->with('error', 'Email already exists.');
        }

        $updateData = [
            'agent_name' => $this->request->getPost('agent_name'),
            'email'      => $this->request->getPost('email'),
            'is_online'  => $this->request->getPost('is_online') ? 1 : 0,
        ];

        if ($this->request->getPost('password')) {
            $updateData['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
        }

        $this->agentModel->update($id, $updateData);

        return redirect()->to('/agents')->with('success', 'Agent updated successfully.');
    }

    // Toggle Active/Inactive status (is_online used as status flag)
public function toggleStatus($id)
{
    $agent = $this->agentModel->find($id);

    if (! $agent) {
        return redirect()->to('/agents')
            ->with('error', 'Agent not found.');
    }

    $newStatus = ($agent['Active'] == 1) ? 0 : 1;

    $this->agentModel->update($id, [
        'Active' => $newStatus,
        'is_online' => $newStatus ? $agent['is_online'] : 0
    ]);

    return redirect()->to('/agents')
        ->with('success', 'Status updated successfully.');
}

public function delete($id)
{
    $agent = $this->agentModel->find($id);

    if (! $agent) {
        return redirect()->to('/agents')
            ->with('error', 'Agent not found.');
    }

    $this->agentModel->update($id, [
        'Active'    => 0,
        'is_online' => 0, // Optional: mark offline as well
    ]);

    return redirect()->to('/agents')
        ->with('success', 'Agent deactivated successfully.');
}

}