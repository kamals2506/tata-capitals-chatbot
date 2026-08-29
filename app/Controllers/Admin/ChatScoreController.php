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

public function index__()
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
            live_chat_sessions.remarks
        ')
        ->join(
            'live_chat_agents',
            'live_chat_agents.id = live_chat_sessions.agent_id',
            'left'
        )
        ->join(
            'live_chat_scores',
            'live_chat_scores.chat_id = live_chat_sessions.id',
            'left'
        )
        ->join(
            'chat_disposition',
            'chat_disposition.id = live_chat_sessions.dispostion',
            'left'
        );

    $rows = $builder->get()->getResult();

    // ==========================
    // Calculate Average Reply Time
    // ==========================

    foreach ($rows as &$row) {

        $messages = $this->db->table('live_chat_messages')
            ->where('chat_id', $row->id)
            ->orderBy('created_at', 'ASC')
            ->get()
            ->getResult();

        $customerTime = null;
        $replyTimes   = [];

        foreach ($messages as $msg) {

            if (strtolower($msg->sender) == 'customer') {

                $customerTime = strtotime($msg->created_at);

            } elseif (strtolower($msg->sender) == 'agent' && $customerTime) {

                $replyTimes[] = strtotime($msg->created_at) - $customerTime;

                $customerTime = null;
            }
        }

        if (count($replyTimes) > 0) {

            $avg = round(array_sum($replyTimes) / count($replyTimes));

            $m = floor($avg / 60);
            $s = $avg % 60;

            $row->avg_reply_time = ($m > 0)
                ? "{$m}m {$s}s"
                : "{$s}s";

        } else {

            $row->avg_reply_time = '-';
        }
    }

    $data['rows'] = $rows;

    return view('admin/chat_score/index', $data);
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
            live_chat_sessions.remarks
        ')
        ->join(
            'live_chat_agents',
            'live_chat_agents.id = live_chat_sessions.agent_id',
            'left'
        )
        ->join(
            'live_chat_scores',
            'live_chat_scores.chat_id = live_chat_sessions.id',
            'left'
        )
        ->join(
            'chat_disposition',
            'chat_disposition.id = live_chat_sessions.dispostion',
            'left'
        )
        ->orderBy('live_chat_sessions.id', 'DESC'); 
        
    $rows = $builder->get()->getResult();

    // ==========================
    // Calculate Agent Reply Gap + Chat Duration (shared logic)
    // ==========================
    foreach ($rows as &$row) {
        $metrics = $this->calculateAgentMetrics($row->id);

        $row->avg_agent_gap             = $metrics['average_text'];
        $row->agent_first_message_time  = $metrics['agent_first_message_time'];
        $row->agent_last_message_time   = $metrics['agent_last_message_time'];
        $row->chat_duration             = $metrics['chat_duration_text'];
    }
    unset($row); // reference cleanup — important after foreach by reference

    $data['rows'] = $rows;
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

public function replyHistory($chatId)
{
    $messages = $this->db->table('live_chat_messages')
        ->select('*')
        ->where('chat_id', $chatId)
        ->orderBy('created_at', 'ASC')
        ->get()
        ->getResult();

    $lastAgentTime = null;
    $result = [];

    foreach ($messages as $msg) {
        $msg->reply_seconds = null;
        $msg->reply_text = '';

        if (trim(strtolower($msg->sender)) === 'agent') {
            if ($lastAgentTime) {
                $diff = strtotime($msg->created_at) - $lastAgentTime;
                $msg->reply_seconds = $diff;
                $msg->reply_text = $this->formatReplyTime($diff);
            }
            $lastAgentTime = strtotime($msg->created_at);
        }

        $result[] = $msg;
    }

    // Same shared calculation used in index() list
    $metrics = $this->calculateAgentMetrics($chatId, $messages);

    return $this->response->setJSON([
        'status' => true,
        'average_seconds' => $metrics['average_seconds'],
        'average_text' => $metrics['average_text'],
        'agent_first_message_time' => $metrics['agent_first_message_time'],
        'agent_last_message_time' => $metrics['agent_last_message_time'],
        'chat_duration_seconds' => $metrics['chat_duration_seconds'],
        'chat_duration_text' => $metrics['chat_duration_text'],
        'messages' => $result
    ]);
}
/**
 * Shared logic to calculate agent reply gap, first/last agent message time,
 * and total chat duration. Used by BOTH index() list and replyHistory() modal
 * so values always match exactly.
 */
private function calculateAgentMetrics($chatId, $messages = null)
{
    if ($messages === null) {
        $messages = $this->db->table('live_chat_messages')
            ->where('chat_id', $chatId)
            ->orderBy('created_at', 'ASC')
            ->get()
            ->getResult();
    }

    // ---- Total chat duration (first msg to last msg, overall) ----
    $chatDurationSeconds = 0;
    $chatDurationText = '-';
    if (count($messages) > 0) {
        $firstOverall = strtotime($messages[0]->created_at);
        $lastOverall  = strtotime(end($messages)->created_at);
        $chatDurationSeconds = $lastOverall - $firstOverall;
        $chatDurationText = $this->formatReplyTime($chatDurationSeconds);
    }

    // ---- Agent-only messages ----
    $agentMessages = array_values(array_filter($messages, function ($msg) {
        return trim(strtolower($msg->sender)) === 'agent';
    }));

    // ---- Agent first & last message time ----
    $agentFirstTime = count($agentMessages) > 0 ? $agentMessages[0]->created_at : '-';
    $agentLastTime  = count($agentMessages) > 0 ? end($agentMessages)->created_at : '-';

    // ---- Agent's consecutive message gaps ----
    $gaps = [];
    for ($i = 1; $i < count($agentMessages); $i++) {
        $prevTime = strtotime($agentMessages[$i - 1]->created_at);
        $currTime = strtotime($agentMessages[$i]->created_at);
        $gaps[] = $currTime - $prevTime;
    }

    $averageSeconds = 0;
    if (count($gaps) > 0) {
        $averageSeconds = round(array_sum($gaps) / count($gaps));
    }

    return [
        'average_seconds' => $averageSeconds,
        'average_text' => $this->formatReplyTime($averageSeconds),
        'agent_first_message_time' => $agentFirstTime,
        'agent_last_message_time' => $agentLastTime,
        'chat_duration_seconds' => $chatDurationSeconds,
        'chat_duration_text' => $chatDurationText,
    ];
}

private function formatReplyTime($seconds)
{
    if ($seconds <= 0) {
        return '-';
    }
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    if ($hours > 0) {
        return "{$hours}h {$minutes}m {$seconds}s";
    }
    if ($minutes > 0) {
        return "{$minutes}m {$seconds}s";
    }
    return "{$seconds}s";
}






}