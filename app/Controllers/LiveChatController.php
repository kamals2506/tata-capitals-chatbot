<?php

namespace App\Controllers;

use App\Models\AgentModel;
use App\Models\ChatSessionModel;
use App\Models\LiveChatMessageModel;
use App\Models\LiveChatSessionModel;
use CodeIgniter\HTTP\ResponseInterface;

class LiveChatController extends BaseController
{
    protected LiveChatSessionModel $chatModel;
    protected LiveChatMessageModel $messageModel;
    protected ChatSessionModel $sessionModel;

 protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }


    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ): void
    {
        parent::initController($request, $response, $logger);

        $this->chatModel    = new LiveChatSessionModel();
        $this->messageModel = new LiveChatMessageModel();
        $this->sessionModel = new ChatSessionModel();
    }

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER START LIVE CHAT
    |--------------------------------------------------------------------------
    | POST : chatbot/livechat/start
    |
    | Body
    | {
    |     session_id,
    |     customer_name,
    |     customer_mobile
    | }
    |--------------------------------------------------------------------------
    */

    public function start(): ResponseInterface
    {
        $body = $this->request->getJSON(true);

        if (empty($body)) {
            $body = $this->request->getPost();
        }

        $sessionId      = trim($body['session_id'] ?? '');
        $customerName   = trim($body['customer_name'] ?? '');
        $customerMobile = trim($body['customer_mobile'] ?? '');

        if ($sessionId == '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'message' => 'session_id is required.'
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate customer_name
        |--------------------------------------------------------------------------
        */

        if ($customerName === '') {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'errors'  => [
                        'customer_name' => 'Please enter your name.',
                    ],
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate customer_mobile
        | Strip spaces, hyphens, and leading +91 prefix, then check ^[6-9]\d{9}$
        |--------------------------------------------------------------------------
        */

        // Remove +91 or 91 prefix if present, then strip all non-digit characters
        $cleanMobile = preg_replace('/^\+?91/', '', $customerMobile);
        $cleanMobile = preg_replace('/\D/', '', $cleanMobile);

        if (!preg_match('/^[6-9]\d{9}$/', $cleanMobile)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'errors'  => [
                        'customer_mobile' => 'Please enter a valid 10-digit mobile number.',
                    ],
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Verify AI Session Exists
        |--------------------------------------------------------------------------
        */

        $chatbotSession = $this->sessionModel->getByUuid($sessionId);

        if (!$chatbotSession) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'success' => false,
                    'message' => 'Invalid chatbot session.'
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Waiting / Active Chat
        |--------------------------------------------------------------------------
        */

        $existing = $this->chatModel->findOpenBySessionId($sessionId);

        if ($existing) {

            $wsToken = $this->generateWsToken($existing['id']);

            return $this->response->setJSON([
                'success'  => true,
                'chat_id'  => $existing['id'],
                'status'   => $existing['status'],
                'agent_id' => $existing['agent_id'],
                'messages' => $this->messageModel->getMessages($existing['id']),
                'ws_token' => $wsToken,
            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | Create New Chat (store cleaned mobile digits only)
        |--------------------------------------------------------------------------
        */

        $chat = $this->chatModel->createChat(
            $sessionId,
            $customerName,
            $cleanMobile
        );

        /*
        |--------------------------------------------------------------------------
        | First System Message
        |--------------------------------------------------------------------------
        */

        $this->messageModel->addMessage(
            $chat['id'],
            'system',
            'Thank you for contacting us. You have been added to the live support queue. Please wait while an agent joins.'
        );

        /*
        |--------------------------------------------------------------------------
        | Generate WebSocket Token (HMAC-SHA256, 5-minute TTL)
        |--------------------------------------------------------------------------
        */

        $wsToken = $this->generateWsToken($chat['id']);

        return $this->response->setJSON([

            'success'  => true,

            'chat_id'  => $chat['id'],

            'status'   => 'waiting',

            'agent_id' => null,

            'messages' => $this->messageModel->getMessages($chat['id']),

            'ws_token' => $wsToken,

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE WS TOKEN
    |--------------------------------------------------------------------------
    | Builds a short-lived HMAC-SHA256 signed token for WebSocket handshake
    | authentication (Requirements: 7.3).
    |
    | Format: base64(json_payload) . "." . hmac_sha256(base64(json_payload))
    | TTL   : 300 seconds (5 minutes)
    |--------------------------------------------------------------------------
    */

    private function generateWsToken(int $chatId): string
    {
        $payload = base64_encode(json_encode([
            'role'    => 'customer',
            'chat_id' => $chatId,
            'exp'     => time() + 300,
        ]));

        $secret = getenv('APP_SECRET') ?: 'allcargo_ws_secret';
        $sig    = hash_hmac('sha256', $payload, $secret);

        return $payload . '.' . $sig;
    }

    // Remaining methods will be added in next parts:
    // sendMessage()
/*
|--------------------------------------------------------------------------
| CUSTOMER SEND MESSAGE
|--------------------------------------------------------------------------
| POST : chatbot/livechat/message
|
| Body
| {
|     "chat_id":1,
|     "message":"Hello"
| }
|--------------------------------------------------------------------------
*/

public function sendMessage(): ResponseInterface
{
    $body = $this->request->getJSON(true);

    if (empty($body)) {
        $body = $this->request->getPost();
    }

    $chatId  = (int)($body['chat_id'] ?? 0);
    $message = trim($body['message'] ?? '');

    if ($chatId <= 0 || $message == '') {
        return $this->response
            ->setStatusCode(400)
            ->setJSON([
                'success' => false,
                'message' => 'chat_id and message are required.'
            ]);
    }

    // Check chat exists
    $chat = $this->chatModel->find($chatId);

    if (!$chat) {
        return $this->response
            ->setStatusCode(404)
            ->setJSON([
                'success' => false,
                'message' => 'Chat not found.'
            ]);
    }

    // Chat already closed
    if ($chat['status'] == 'closed') {
        return $this->response
            ->setStatusCode(400)
            ->setJSON([
                'success' => false,
                'message' => 'This chat has already been closed.'
            ]);
    }

    // Save customer message
    $saved = $this->messageModel->addMessage(
        $chatId,
        'customer',
        $message
    );

    // Update session timestamp
    $this->chatModel->db->table('live_chat_sessions')
        ->where('id', $chatId)
        ->update(['updated_at' => date('Y-m-d H:i:s')]);

    return $this->response->setJSON([

        'success' => true,

        'message' => 'Message sent successfully.',

        'data' => $saved

    ]);
}

/*
|--------------------------------------------------------------------------
| CUSTOMER POLL
|--------------------------------------------------------------------------
| GET : chatbot/livechat/poll/{chat_id}?last_id=0
|
| Example:
| /chatbot/livechat/poll/5?last_id=10
|--------------------------------------------------------------------------
*/

public function poll($chatId = null): ResponseInterface
{
    $chatId = (int)$chatId;

    if ($chatId <= 0) {
        return $this->response
            ->setStatusCode(400)
            ->setJSON([
                'success' => false,
                'message' => 'Invalid chat id.'
            ]);
    }

    $chat = $this->chatModel->find($chatId);

    if (!$chat) {
        return $this->response
            ->setStatusCode(404)
            ->setJSON([
                'success' => false,
                'message' => 'Chat not found.'
            ]);
    }

    // Last received message id
    $lastId = (int)$this->request->getGet('last_id');

    if ($lastId > 0) {

        $messages = $this->messageModel
            ->where('chat_id', $chatId)
            ->where('id >', $lastId)
            ->orderBy('id', 'ASC')
            ->findAll();

    } else {

        $messages = $this->messageModel
            ->where('chat_id', $chatId)
            ->orderBy('id', 'ASC')
            ->findAll();

    }

    // Queue Position
    $queuePosition = null;

    if ($chat['status'] === 'waiting') {

        $waitingChats = $this->chatModel
            ->where('status', 'waiting')
            ->orderBy('created_at', 'ASC')
            ->findAll();

        foreach ($waitingChats as $index => $row) {

            if ($row['id'] == $chatId) {
                $queuePosition = $index + 1;
                break;
            }

        }

    }

    // Latest Message ID
    $latestMessageId = $lastId;

    if (!empty($messages)) {
        $latestMessageId = end($messages)['id'];
    }

    return $this->response->setJSON([

        'success' => true,

        'chat' => [

            'id' => $chat['id'],

            'status' => $chat['status'],

            'agent_id' => $chat['agent_id'],

            'queue_position' => $queuePosition

        ],

        'messages' => $messages,

        'last_id' => $latestMessageId,

        'server_time' => date('Y-m-d H:i:s')

    ]);
}

   /*
|--------------------------------------------------------------------------
| CLOSE CHAT
|--------------------------------------------------------------------------
| POST : chatbot/livechat/close
|
| Body:
| {
|     "chat_id": 1,
|     "closed_by":"customer"
| }
|
| closed_by:
| customer
| agent
|--------------------------------------------------------------------------
*/

public function close(): ResponseInterface
{
    $body = $this->request->getJSON(true);
    
    //print_r($body);die;
    
    if (empty($body)) {
        $body = $this->request->getPost();
    }

    $chatId   = (int)($body['chat_id'] ?? 0);
    $dispo  =$body['disposition_id'];
    $remarks  =$body['remarks'];
    $closedBy = trim($body['closed_by'] ?? 'customer');

    if ($chatId <= 0) {
        return $this->response
            ->setStatusCode(400)
            ->setJSON([
                'success' => false,
                'message' => 'Invalid chat id.'
            ]);
    }

    $chat = $this->chatModel->find($chatId);

    if (!$chat) {
        return $this->response
            ->setStatusCode(404)
            ->setJSON([
                'success' => false,
                'message' => 'Chat not found.'
            ]);
    }

    // Already closed
    if ($chat['status'] == 'closed') {

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Chat already closed.'
        ]);

    }

    // Close session
    $this->chatModel->update($chatId, [

        'status' => 'closed',
        'dispostion'=>$dispo,
        'remarks'=>$remarks,
        'updated_at' => date('Y-m-d H:i:s')

    ]);

    // Save system message
    $systemMessage = '';

    switch ($closedBy) {

        case 'agent':
            $systemMessage = 'Agent has ended the conversation.';
            break;

        case 'customer':
            $systemMessage = 'Customer has ended the conversation.';
            break;

        default:
            $systemMessage = 'Conversation closed.';
            break;

    }

    $this->messageModel->addMessage(
        $chatId,
        'system',
        $systemMessage
    );

    return $this->response->setJSON([

        'success' => true,

        'message' => 'Chat closed successfully.',

        'chat_id' => $chatId,

        'status' => 'closed'

    ]);
}

public function dashboard()
{
    if (!session()->has('user_id')) {
        return redirect()->to('/login');
    }

    return view('livechat/agent_dashboard');
}

/*
|--------------------------------------------------------------------------
| WAITING QUEUE
|--------------------------------------------------------------------------
| GET : /agent/livechat/queue
|--------------------------------------------------------------------------
*/

public function queue(): ResponseInterface
{
    if (!session()->has('user_id')) {

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Unauthorized'
        ]);

    }

    $waitingChats = $this->chatModel

        ->where('status', 'waiting')

        ->orderBy('created_at', 'ASC')

        ->findAll();

    foreach ($waitingChats as &$chat) {

        $chat['waiting_time'] =

            strtotime(date('Y-m-d H:i:s')) -

            strtotime($chat['created_at']);

        $chat['waiting_minutes'] =

            floor($chat['waiting_time'] / 60);

        $chat['message_count'] =

            $this->messageModel

                ->where('chat_id', $chat['id'])

                ->countAllResults();

    }

    return $this->response->setJSON([

        'success' => true,

        'total' => count($waitingChats),

        'queue' => $waitingChats

    ]);
}

/*
|--------------------------------------------------------------------------
| ACTIVE CHATS OF LOGGED IN AGENT
|--------------------------------------------------------------------------
| GET : /agent/livechat/active
|--------------------------------------------------------------------------
*/

public function activeChats(): ResponseInterface
{
    if (!session()->has('user_id')) {

        return $this->response->setJSON([
            'success' => false
        ]);

    }

    $agentId = session()->get('user_id');

    $activeChats = $this->chatModel
    ->where('status', 'active')
    ->where('agent_id', $agentId)
    ->where('updated_at >=', date('Y-m-d H:i:s', strtotime('-12 hours')))
    ->orderBy('updated_at', 'DESC')
    ->findAll();

    foreach ($activeChats as &$chat) {

        $lastMessage = $this->messageModel

            ->where('chat_id', $chat['id'])

            ->orderBy('id', 'DESC')

            ->first();

        $chat['last_message'] =

            $lastMessage['message'] ?? '';

        $chat['last_sender'] =

            $lastMessage['sender'] ?? '';

        $chat['last_message_time'] =

            $lastMessage['created_at'] ?? '';

        $chat['message_count'] =

            $this->messageModel

                ->where('chat_id', $chat['id'])

                ->countAllResults();

    }

    return $this->response->setJSON([

        'success' => true,

        'total' => count($activeChats),

        'chats' => $activeChats

    ]);
}

/*
|--------------------------------------------------------------------------
| AGENT ACCEPT CHAT
|--------------------------------------------------------------------------
| POST : /agent/livechat/claim
|--------------------------------------------------------------------------
*/

public function claim(): ResponseInterface
{
    if (!session()->has('user_id')) {

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Unauthorized'
        ]);

    }

    $body = $this->request->getJSON(true);

    if (empty($body)) {
        $body = $this->request->getPost();
    }

    $chatId = (int)($body['chat_id'] ?? 0);

    if ($chatId <= 0) {

        return $this->response
            ->setStatusCode(400)
            ->setJSON([
                'success' => false,
                'message' => 'Invalid Chat ID'
            ]);

    }

    $chat = $this->chatModel->find($chatId);

    if (!$chat) {

        return $this->response
            ->setStatusCode(404)
            ->setJSON([
                'success' => false,
                'message' => 'Chat not found'
            ]);

    }

    if ($chat['status'] != 'waiting') {

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Chat already claimed.'
        ]);

    }

    $agentId = session()->get('user_id');

    $this->chatModel->update($chatId, [

        'status' => 'active',

        'agent_id' => $agentId,

        'updated_at' => date('Y-m-d H:i:s')

    ]);

    $this->messageModel->addMessage(

        $chatId,

        'system',

        'A live support agent has joined the conversation.'

    );

    return $this->response->setJSON([

        'success' => true,

        'message' => 'Chat assigned successfully.',

        'chat_id' => $chatId,

        'agent_id' => $agentId

    ]);
}

/*
|--------------------------------------------------------------------------
| AGENT SEND MESSAGE
|--------------------------------------------------------------------------
| POST : /agent/livechat/reply
|--------------------------------------------------------------------------
*/

public function reply(): ResponseInterface
{
    if (!session()->has('user_id')) {

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Unauthorized'
        ]);

    }

    $body = $this->request->getJSON(true);

    if (empty($body)) {
        $body = $this->request->getPost();
    }

    $chatId = (int)($body['chat_id'] ?? 0);

    $message = trim($body['message'] ?? '');

    if ($chatId <= 0 || $message == '') {

        return $this->response
            ->setStatusCode(400)
            ->setJSON([
                'success' => false,
                'message' => 'Chat ID and message are required.'
            ]);

    }

    $chat = $this->chatModel->find($chatId);

    if (!$chat) {

        return $this->response
            ->setStatusCode(404)
            ->setJSON([
                'success' => false,
                'message' => 'Chat not found.'
            ]);

    }

    if ($chat['status'] != 'active') {

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Chat is not active.'
        ]);

    }

    if ($chat['agent_id'] != session()->get('user_id')) {

        return $this->response->setJSON([
            'success' => false,
            'message' => 'This chat belongs to another agent.'
        ]);

    }

    $saved = $this->messageModel->addMessage(

        $chatId,

        'agent',

        $message

    );

    // Touch updated_at via query builder — model's update() strips timestamp
    // fields when $useTimestamps = true, causing an empty-dataset exception.
    $this->chatModel->db->table('live_chat_sessions')
        ->where('id', $chatId)
        ->update(['updated_at' => date('Y-m-d H:i:s')]);

    return $this->response->setJSON([

        'success' => true,

        'message' => 'Reply sent successfully.',

        'data' => $saved

    ]);
}

/*
|--------------------------------------------------------------------------
| AGENT POLL CHAT
|--------------------------------------------------------------------------
| GET : /agent/livechat/poll/{chat_id}?last_id=0
|--------------------------------------------------------------------------
*/

public function agentPoll($chatId = null): ResponseInterface
{
    if (!session()->has('user_id')) {

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Unauthorized'
        ]);

    }

    $agentId = session()->get('user_id');

    $chatId = (int)$chatId;

    if ($chatId <= 0) {

        return $this->response
            ->setStatusCode(400)
            ->setJSON([
                'success' => false,
                'message' => 'Invalid Chat ID'
            ]);

    }

    $chat = $this->chatModel->find($chatId);

    if (!$chat) {

        return $this->response
            ->setStatusCode(404)
            ->setJSON([
                'success' => false,
                'message' => 'Chat not found'
            ]);

    }

    if ($chat['agent_id'] != $agentId) {

        return $this->response
            ->setStatusCode(403)
            ->setJSON([
                'success' => false,
                'message' => 'Access denied'
            ]);

    }

    $lastId = (int)$this->request->getGet('last_id');

    if ($lastId > 0) {

        $messages = $this->messageModel

            ->where('chat_id', $chatId)

            ->where('id >', $lastId)

            ->orderBy('id', 'ASC')

            ->findAll();

    } else {

        $messages = $this->messageModel

            ->where('chat_id', $chatId)

            ->orderBy('id', 'ASC')

            ->findAll();

    }

    $latestId = $lastId;

    if (!empty($messages)) {

        $latestId = end($messages)['id'];

    }

    return $this->response->setJSON([

        'success' => true,

        'chat_status' => $chat['status'],

        'messages' => $messages,

        'last_id' => $latestId

    ]);

}

/*
|--------------------------------------------------------------------------
| CHAT HISTORY
|--------------------------------------------------------------------------
| GET : /agent/livechat/history/{chat_id}
|--------------------------------------------------------------------------
*/

public function history($chatId = null): ResponseInterface
{
    if (!session()->has('user_id')) {

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Unauthorized'
        ]);

    }

    $agentId = session()->get('user_id');

    $chatId = (int)$chatId;

    if ($chatId <= 0) {

        return $this->response
            ->setStatusCode(400)
            ->setJSON([
                'success' => false,
                'message' => 'Invalid Chat ID'
            ]);

    }

    $chat = $this->chatModel->find($chatId);

    if (!$chat) {

        return $this->response
            ->setStatusCode(404)
            ->setJSON([
                'success' => false,
                'message' => 'Chat not found'
            ]);

    }

    if (

        $chat['agent_id'] != $agentId

        &&

        $chat['status'] == 'active'

    ) {

        return $this->response
            ->setStatusCode(403)
            ->setJSON([
                'success' => false,
                'message' => 'Access denied'
            ]);

    }

    $messages = $this->messageModel

        ->where('chat_id', $chatId)

        ->orderBy('id', 'ASC')

        ->findAll();

    return $this->response->setJSON([

        'success' => true,

        'chat' => $chat,

        'messages' => $messages

    ]);

}

/*
|--------------------------------------------------------------------------
| AGENT STATUS (public — no auth required)
|--------------------------------------------------------------------------
| GET : /agent/livechat/agents/status
|
| Returns the number of agents currently online so the customer chatbot UI
| can decide whether to show the "Connect to Agent" button.
|
| Response:
| {
|     "success": true,
|     "available": true,
|     "online_count": 2
| }
|--------------------------------------------------------------------------
*/

public function agentStatus(): ResponseInterface
{
    $agentModel  = new AgentModel();
    $onlineCount = $agentModel->getOnlineCount();

    return $this->response->setJSON([
        'success'      => true,
        'available'    => $onlineCount > 0,
        'online_count' => $onlineCount,
    ]);
}

/*
|--------------------------------------------------------------------------
| AGENT WS TOKEN
|--------------------------------------------------------------------------
| GET : /agent/livechat/ws-token
|
| Returns a short-lived HMAC-SHA256 signed WebSocket token for the
| agent dashboard. Protected by agentAuth filter.
|--------------------------------------------------------------------------
*/

public function wsToken(): ResponseInterface
{
    $agentId = (int) session()->get('user_id');

    $payload = base64_encode(json_encode([
        'role'     => 'agent',
        'agent_id' => $agentId,
        'exp'      => time() + 300,
    ]));

    $secret = getenv('APP_SECRET') ?: 'allcargo_ws_secret';
    $sig    = hash_hmac('sha256', $payload, $secret);

    return $this->response->setJSON([
        'success'  => true,
        'ws_token' => $payload . '.' . $sig,
    ]);
}



    public function dashboard1()
    {

        // Summary
        $data['totalChats'] = $this->db->table('live_chat_sessions')->countAll();

        $data['activeChats'] = $this->db->table('live_chat_sessions')
            ->where('status','Active')
            ->countAllResults();

        $data['closedChats'] = $this->db->table('live_chat_sessions')
            ->where('status','Closed')
            ->countAllResults();

        $data['waitingChats'] = $this->db->table('live_chat_sessions')
            ->where('status','Waiting')
            ->countAllResults();

        // Agents
        $data['onlineAgents'] = $this->db->table('live_chat_agents')
            ->where('is_online',1)
            ->countAllResults();

        $data['offlineAgents'] = $this->db->table('live_chat_agents')
            ->where('is_online',0)
            ->countAllResults();

        // Today's Messages
        $data['todayMessages'] = $this->db->table('live_chat_messages')
            ->where('DATE(created_at)', date('Y-m-d'))
            ->countAllResults();

        // Recent Chats

   $builder = $this->db->table('live_chat_sessions s');

$builder->select("
    s.*,
    a.agent_name,
    COUNT(m.id) AS total_messages,
    MAX(m.created_at) AS last_message,
    sc.vocabulary,
    sc.grammar,
    sc.relevance,
    sc.fluency,
    sc.final_score
");

$builder->join('live_chat_agents a', 'a.id = s.agent_id', 'left');
$builder->join('live_chat_messages m', 'm.chat_id = s.id', 'left');
$builder->join('live_chat_scores sc', 'sc.chat_id = s.id', 'left');

$builder->groupBy('
    s.id,
    sc.vocabulary,
    sc.grammar,
    sc.relevance,
    sc.fluency,
    sc.final_score
');

$builder->orderBy('last_message', 'DESC');
$builder->limit(1000);

$data['recentChats'] = $builder->get()->getResult();

        // Agent Performance

        $builder=$this->db->table('live_chat_agents a');

        $builder->select("
            a.agent_name,
            a.is_online,
            COUNT(s.id) total_chats
        ");

        $builder->join('live_chat_sessions s','s.agent_id=a.id','left');

        $builder->groupBy('a.id');

        $data['agentPerformance']=$builder->get()->getResult();

        // Chart

        $chart=$this->db->query("
            SELECT
            DATE(created_at) d,
            COUNT(*) total
            FROM live_chat_messages
            GROUP BY DATE(created_at)
            ORDER BY d ASC
            LIMIT 15
        ")->getResult();

        $labels=[];
        $values=[];

        foreach($chart as $row){

            $labels[]=$row->d;
            $values[]=$row->total;

        }

        $data['chartLabels']=json_encode($labels);
        $data['chartValues']=json_encode($values);

        return view('livechat/dashboard',$data);

    }

public function details($chatId)
{
    $row = $this->db->table('live_chat_scores')
        ->where('chat_id',$chatId)
        ->get()
        ->getRow();

    return $this->response->setJSON($row);
}

public function history1($session)
{
    $messages=$this->db->table('live_chat_messages')
        ->where('chat_id',$session)
        ->orderBy('created_at','ASC')
        ->get()
        ->getResult();

    return $this->response->setJSON($messages);
}

}