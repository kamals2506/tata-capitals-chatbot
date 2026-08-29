<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// TataPlay AI Chatbot routes
$routes->get('/chatbot',            'ChatController::index');
$routes->post('/chatbot/session',   'ChatController::createSession');
$routes->post('/chatbot/message',   'ChatController::sendMessage');
$routes->post('/chatbot/escalate',  'ChatController::escalate');
$routes->post('/chatbot/complaint', 'ChatController::logComplaint');
// app/Config/Routes.php

$routes->get('chat.html', 'Chat::index');


/*
|--------------------------------------------------------------------------
| Customer Live Chat
|--------------------------------------------------------------------------
*/

$routes->post('chatbot/livechat/start','LiveChatController::start');

$routes->post('chatbot/livechat/message','LiveChatController::sendMessage');

$routes->get('chatbot/livechat/poll/(:num)','LiveChatController::poll/$1');

$routes->post('chatbot/livechat/close','LiveChatController::close');


/*
|--------------------------------------------------------------------------
| Agent (protected by agentAuth filter)
|--------------------------------------------------------------------------
*/

$routes->group('agent/livechat', ['filter' => 'agentAuth'], function ($routes) {
    $routes->get('/',                'LiveChatController::dashboard');
    $routes->get('queue',            'LiveChatController::queue');
    $routes->get('active',           'LiveChatController::activeChats');
    $routes->post('claim',           'LiveChatController::claim');
    $routes->post('reply',           'LiveChatController::reply');
    $routes->get('poll/(:num)',      'LiveChatController::agentPoll/$1');
    $routes->post('close',           'LiveChatController::close');
    $routes->get('history/(:num)',   'LiveChatController::history/$1');
    $routes->get('ws-token',         'LiveChatController::wsToken');
});

$routes->group('admin/chat-score', ['namespace' => 'App\Controllers\Admin'], function ($routes) {
    
    
     $routes->post('update-compliance', 'ChatScoreController::updateCompliance');

    $routes->get('/', 'ChatScoreController::index');

    $routes->get('evaluate/(:num)', 'ChatScoreController::evaluate/$1');

    $routes->get('details/(:num)', 'ChatScoreController::details/$1');
    $routes->get('getDispositions', 'ChatScoreController::getDispositions');
    
    $routes->get('filter', 'ChatScoreController::filter'); 
    
    $routes->get('reply-history/(:num)', 'ChatScoreController::replyHistory/$1');
    
    
    

});



// Public — customers check agent availability before entering queue
$routes->get('agent/livechat/agents/status', 'LiveChatController::agentStatus');

$routes->get('login', 'AgentAuthController::index');
$routes->post('login', 'AgentAuthController::login');
$routes->get('logout', 'AgentAuthController::logout');

$routes->group('', ['filter' => 'agentAuth'], function ($routes) {

    $routes->get('livechat/dashboard1', 'LiveChatController::dashboard1');
    $routes->get('livechat/history1/(:any)', 'LiveChatController::history1/$1');

 $routes->group('agents', [
    'namespace' => 'App\Controllers',
    'filter'    => 'agentAuth'
], function ($routes) {
    $routes->get('/', 'AgentController::index');
    $routes->get('create', 'AgentController::create');
    $routes->post('store', 'AgentController::store');
    $routes->get('edit/(:num)', 'AgentController::edit/$1');
    $routes->post('update/(:num)', 'AgentController::update/$1');
    $routes->get('toggle/(:num)', 'AgentController::toggleStatus/$1');
    $routes->get('delete/(:num)', 'AgentController::delete/$1');
});

});

$routes->get('chatbot', 'ChatController::index');

$routes->post(
    'chatbot/session',
    'ChatController::createSession'
);

$routes->post(
    'chatbot/message',
    'ChatController::sendMessage'
);

$routes->post(
    'chatbot/complaint',
    'ChatController::logComplaint'
);
