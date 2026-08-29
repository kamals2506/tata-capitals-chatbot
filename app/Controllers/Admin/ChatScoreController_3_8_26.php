<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\Database;

class ChatScoreController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function index()
{
    $builder = $this->db->table('live_chat_sessions')
        ->select('
            live_chat_sessions.id,
            live_chat_sessions.customer_name,
            live_chat_sessions.customer_mobile,
            live_chat_agents.agent_name,
            live_chat_scores.vocabulary,
            live_chat_scores.grammar,
            live_chat_scores.relevance,
            live_chat_scores.fluency,
            live_chat_scores.final_score,
            live_chat_scores.compliance as compliance_status,
            chat_disposition.disposition_name,
            live_chat_sessions.remarks,
            
        ')
        ->join('live_chat_agents',
            'live_chat_agents.id = live_chat_sessions.agent_id',
            'left')
        ->join('live_chat_scores',
            'live_chat_scores.chat_id = live_chat_sessions.id',
            'left')
            ->join('chat_disposition',
            'chat_disposition.id = live_chat_sessions.dispostion',
            'left');

    $data['rows'] = $builder->get()->getResult();

    return view('admin/chat_score/index', $data);
}
public function filter()
    {
        $from = $this->request->getGet('from');
        $to   = $this->request->getGet('to');

        $builder = $this->db->table('live_chat_sessions')
            ->select('
                live_chat_sessions.id,
                live_chat_sessions.customer_name,
                live_chat_sessions.customer_mobile,
                live_chat_agents.agent_name,
                live_chat_scores.vocabulary,
                live_chat_scores.grammar,
                live_chat_scores.relevance,
                live_chat_scores.fluency,
                live_chat_scores.final_score,
                live_chat_scores.compliance as compliance_status,
                chat_disposition.disposition_name,
                live_chat_sessions.remarks,
                live_chat_sessions.created_at
            ')
            ->join('live_chat_agents',
                'live_chat_agents.id = live_chat_sessions.agent_id',
                'left')
            ->join('live_chat_scores',
                'live_chat_scores.chat_id = live_chat_sessions.id',
                'left')
            ->join('chat_disposition',
                'chat_disposition.id = live_chat_sessions.dispostion',
                'left');

        // ✅ DATE FILTER YAHAN LAGAO
        if (!empty($from)) {
            $builder->where('DATE(live_chat_sessions.created_at) >=', $from);
        }

        if (!empty($to)) {
            $builder->where('DATE(live_chat_sessions.created_at) <=', $to);
        }

        $builder->orderBy('live_chat_sessions.created_at', 'DESC');

        $rows = $builder->get()->getResult();

        return $this->response->setJSON($rows);
    }

private function evaluateWithOllama($conversation)
{
    $prompt = <<<PROMPT
You are a Senior BPO Quality Analyst.

Below is a customer support conversation.

The conversation contains both CUSTOMER and AGENT messages.

Evaluate ONLY the AGENT'S communication.

Scoring Criteria (0-100):

1. Vocabulary
2. Grammar
3. Relevance
4. Fluency

Scoring Guidelines:

- Vocabulary:
  Richness of words, professionalism, politeness.

- Grammar:
  Correct grammar, spelling and punctuation.

- Relevance:
  Whether every reply answers the customer's question.

- Fluency:
  Natural, clear and professional communication.

Final score should be the average of all four scores.

Feedback should include:
- Strengths
- Weaknesses
- Recommendation

Conversation:

{$conversation}

Return ONLY valid JSON.

{
    "Vocabulary":0,
    "Grammar":0,
    "Relevance":0,
    "Fluency":0,
    "Final":0,
    "Feedback":""
}

Do not return markdown.
Do not return explanation.
Return JSON only.
PROMPT;

    $payload = [
        "model" => "llama3.2:3b",
        "prompt" => $prompt,
        "stream" => false,
        "format" => "json",
        "options" => [
            "temperature" => 0.2
        ]
    ];

    $ch = curl_init("http://59.144.28.139:11434/api/generate");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 300
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new \Exception(curl_error($ch));
    }

    curl_close($ch);

    // Log response
    log_message('info', 'Ollama Response: ' . $response);

    $result = json_decode($response, true);

    if (!$result || !isset($result['response'])) {
        throw new \Exception("Invalid response from Ollama.");
    }

    $score = json_decode($result['response'], true);

    if (!$score) {
        throw new \Exception("Unable to decode Ollama JSON.");
    }

    return $score;
}

    public function evaluate($chatId)
{
    $messages = $this->db->table('live_chat_messages')
        ->select('sender,message')
        ->where('chat_id', $chatId)
        ->orderBy('id', 'ASC')
        ->get()
        ->getResultArray();

    if (empty($messages)) {
        return redirect()->back()->with('error', 'Conversation not found.');
    }

    $conversation = "";

    foreach ($messages as $row) {

        $conversation .= strtoupper($row['sender']) . ": " . trim($row['message']) . "\n";

    }

    try {

        $score = $this->evaluateWithOllama($conversation);

    } catch (\Exception $e) {

        return redirect()->back()->with('error', $e->getMessage());

    }

    $save = [

        'chat_id'      => $chatId,
        'vocabulary'   => $score['Vocabulary'] ?? 0,
        'grammar'      => $score['Grammar'] ?? 0,
        'relevance'    => $score['Relevance'] ?? 0,
        'fluency'      => $score['Fluency'] ?? 0,
        'final_score'  => $score['Final'] ?? 0,
        'feedback'     => $score['Feedback'] ?? '',
        'updated_at'   => date('Y-m-d H:i:s')

    ];

    $exists = $this->db->table('live_chat_scores')
        ->where('chat_id', $chatId)
        ->countAllResults();

    if ($exists) {

        $this->db->table('live_chat_scores')
            ->where('chat_id', $chatId)
            ->update($save);

    } else {

        $save['created_at'] = date('Y-m-d H:i:s');

        $this->db->table('live_chat_scores')
            ->insert($save);

    }

    return redirect()->back()->with('success', 'Conversation evaluated successfully.');
}

public function details($chatId)
{
    $row = $this->db->table('live_chat_scores')
        ->where('chat_id', $chatId)
        ->get()
        ->getRow();

    if (!$row) {
        return $this->response
            ->setStatusCode(404)
            ->setJSON(['error' => 'No score found for this chat.']);
    }

    return $this->response->setJSON([
        'vocabulary'   => $row->vocabulary,
        'grammar'      => $row->grammar,
        'relevance'    => $row->relevance,
        'fluency'      => $row->fluency,
        'final_score'  => $row->final_score,
        'feedback'     => $row->feedback,
    ]);
}
public function updateCompliance()
{
    $id     = $this->request->getPost('id');
    $status = $this->request->getPost('status');
    
 // print_r($id); die;

    $db = \Config\Database::connect();

    $db->table('live_chat_scores')
        ->where('chat_id', $id)
        ->update(['compliance' => $status]);
        
        echo $db->affectedRows(); die;

    return $this->response->setJSON([
        'success' => true
    ]);
}

public function getDispositions()
{
    $db = \Config\Database::connect();

    $data = $db->table('chat_disposition')
        ->select('id, disposition_name, status')
        ->get()
        ->getResult();

    return $this->response->setJSON([
        'success' => true,
        'data' => $data
    ]);
}

}