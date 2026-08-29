<?php

namespace App\Controllers;

use App\Libraries\ComplaintService;
use App\Libraries\HindiTtsService;
use App\Libraries\IntentService;
use App\Libraries\KnowledgeBaseService;
use App\Libraries\OllamaService;
use App\Models\ChatMessageModel;
use App\Models\ChatSessionModel;
use CodeIgniter\HTTP\ResponseInterface;
use Ramsey\Uuid\Uuid;

class ChatController extends BaseController
{
    // English fallback constants
    public const FALLBACK_AI    = 'Sorry, the service is temporarily unavailable. Please call our helpline at 1860 267 6060.';
    public const FALLBACK_DB    = 'Your complaint could not be registered at this time. Please call our helpline at 1860 267 6060.';
    public const FALLBACK_ESC   = 'You will be connected to a specialist. Helpline: 1860 267 6060';

    // Hindi fallback constants
    public const FALLBACK_AI_HI  = 'क्षमा करें, अभी सेवा उपलब्ध नहीं है। कृपया 1860 267 60600-208-6633 पर संपर्क करें।';
    public const FALLBACK_DB_HI  = 'शिकायत दर्ज नहीं हो सकी। कृपया   हेल्प 1860 267 6060-6633 पर संपर्क करें।';
    public const FALLBACK_ESC_HI = 'आपकी बात एक विशेषज्ञ से कराई जाएगी। हेल्पलाइन: 1860 267 6060-6633';

    private ChatSessionModel    $sessionModel;
    private ChatMessageModel    $messageModel;
    private OllamaService       $ollama;
    private HindiTtsService     $tts;
    private IntentService       $intent;
    private KnowledgeBaseService $kb;
    private ComplaintService    $complaint;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);

        $this->sessionModel = new ChatSessionModel();
        $this->messageModel = new ChatMessageModel();
        $this->ollama       = new OllamaService();
        $this->tts          = new HindiTtsService();
        $this->intent       = new IntentService();
        $this->kb           = new KnowledgeBaseService();
        $this->complaint    = new ComplaintService();
    }

    // ─────────────────────────────────────────────
    // GET /chatbot — Render chat UI
    // ─────────────────────────────────────────────
    public function index(): string
    {
        return view('chatbot/chat');
    }

    // ─────────────────────────────────────────────
    // POST /chatbot/session — Create new session
    // ─────────────────────────────────────────────
    public function createSession(): ResponseInterface
    {
        try {
            $body     = $this->request->getJSON(true) ?? $this->request->getPost();
            $language = (isset($body['language']) && $body['language'] === 'hi') ? 'hi' : 'en';

            $maxAttempts = 3;
            for ($i = 0; $i < $maxAttempts; $i++) {
                try {
                    $uuid    = Uuid::uuid4()->toString();
                    $session = $this->sessionModel->createSession($uuid, $language);

                    return $this->response->setJSON([
                        'success'    => true,
                        'session_id' => $session['session_uuid'],
                        'language'   => $session['language'],
                    ]);
                } catch (\Exception $e) {
                    log_message('error', '[ChatController::createSession] ' . $e->getMessage());
                    if ($i === $maxAttempts - 1) {
                        return $this->response->setStatusCode(500)->setJSON([
                            'success' => false,
                            'message' => self::FALLBACK_AI,
                        ]);
                    }
                }
            }

            return $this->response->setStatusCode(500)->setJSON(['success' => false]);
        } catch (\Throwable $e) {
            log_message('error', 'Chatbot Session Error: ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());

            return $this->response->setJSON([
                'success' => false,
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine()
            ])->setStatusCode(500);
        }
    }

    // ─────────────────────────────────────────────
    // POST /chatbot/message — Handle user message
    // ─────────────────────────────────────────────
    public function sendMessage(): ResponseInterface
    {
        $body        = $this->request->getJSON(true) ?? $this->request->getPost();
        $sessionUuid = trim($body['session_id'] ?? '');
        $userText    = trim($body['message'] ?? '');
        $voiceParam  = $body['voice_enabled'] ?? null;

        if ($sessionUuid === '' || $userText === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'session_id and message are required.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | SESSION VALIDATION
        |--------------------------------------------------------------------------
        */
        $newSession = false;

        $session = $this->sessionModel->getByUuid($sessionUuid);

        if (!$session || $this->sessionModel->isExpired($session)) {
            if ($session) {
                $this->sessionModel->markExpired((int) $session['id']);
            }

            $uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $session = $this->sessionModel->createSession($uuid);
            $newSession = true;
        }

        $sessionId = (int) $session['id'];
        $language  = $session['language'] ?? 'en';

        /*
        |--------------------------------------------------------------------------
        | LANGUAGE FALLBACK
        |--------------------------------------------------------------------------
        */
        $fallbackAI = ($language === 'hi')
            ? self::FALLBACK_AI_HI
            : self::FALLBACK_AI;

        /*
        |--------------------------------------------------------------------------
        | VOICE PREFERENCE
        |--------------------------------------------------------------------------
        */
        if ($voiceParam !== null) {
            $this->sessionModel->setVoiceEnabled(
                $sessionId,
                (bool) $voiceParam
            );
            $session['voice_enabled'] = $voiceParam ? 1 : 0;
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE USER MESSAGE
        |--------------------------------------------------------------------------
        */
        $this->messageModel->appendMessage(
            $sessionId,
            'user',
            $userText
        );

        /*
        |--------------------------------------------------------------------------
        | LOAD HISTORY
        |--------------------------------------------------------------------------
        */
        $rawHistory = $this->messageModel->getHistory(
            $sessionId,
            20
        );

        $history = [];

        foreach ($rawHistory as $m) {
            // Current message ko history se remove karo
            if ($m['content'] === $userText && $m['role'] === 'user') {
                continue;
            }

            $history[] = [
                'role'    => $m['role'],
                'content' => $m['content'],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | ================================================================
        | DATABASE CHATBOT FLOW ENGINE
        | ================================================================
        |--------------------------------------------------------------------------
        |
        | Pehle DB flow ko try karenge.
        |
        | Agar DB se response mil gaya:
        |     -> Ollama call nahi hoga
        |
        | Agar DB flow mein match nahi mila:
        |     -> Neeche Ollama fallback chalega
        |
        |--------------------------------------------------------------------------
        */

        $flowResponse = $this->processDatabaseFlow(
            $sessionId,
            $userText,
            $language
        );

        /*
        |--------------------------------------------------------------------------
        | DB FLOW RESPONSE FOUND
        |--------------------------------------------------------------------------
        */
        if ($flowResponse !== null) {

            /*
            |--------------------------------------------------------------------------
            | DATABASE RESPONSE -> OLLAMA SENTENCE REWRITER
            |--------------------------------------------------------------------------
            | IMPORTANT:
            | DB is the source of truth.
            | Ollama is ONLY allowed to make the DB text conversational.
            | It must NOT add/change facts, amounts, rates, tenure or options.
            |--------------------------------------------------------------------------
            */

            $dbMessage = trim((string) ($flowResponse['message'] ?? ''));
            $dbOptions = $flowResponse['options'] ?? [];

            $aiText = $this->rewriteDatabaseResponse(
                $dbMessage,
                $dbOptions,
                $language
            );

            if (trim($aiText) === '') {
                $aiText = $dbMessage;
            }

            /*
            |--------------------------------------------------------------------------
            | SAVE ASSISTANT MESSAGE
            |--------------------------------------------------------------------------
            */
            $ttsUrl = null;

            if (
                !empty($session['voice_enabled']) &&
                (int) $session['voice_enabled'] === 1 &&
                $aiText !== ''
            ) {
                $ttsResult = $this->tts->synthesize(
                    $aiText,
                    $language
                );
                $ttsUrl = $ttsResult['url'] ?? null;
            }

            if ($aiText !== '') {
                $this->messageModel->appendMessage(
                    $sessionId,
                    'assistant',
                    $aiText,
                    $ttsUrl
                );
            }

            return $this->response->setJSON([
                'success'       => true,
                'session_id'    => $session['session_uuid'],
                'language'      => $language,
                'message'       => $aiText,
                'tts_url'       => $ttsUrl,
                'ticket_number' => $flowResponse['ticket_number'] ?? null,
                'complaint_id'  => $flowResponse['complaint_id'] ?? null,
                'escalation'    => false,
                'new_session'   => $newSession,
                'source'        => 'database_flow',
                'flow_id'       => $flowResponse['flow_id'] ?? null,
                'step_id'       => $flowResponse['step_id'] ?? null,
                'step_key'      => $flowResponse['step_key'] ?? null,
                'input_type'    => $flowResponse['input_type'] ?? null,
                'options'       => $flowResponse['options'] ?? [],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ================================================================
        | DATABASE FLOW NOT FOUND
        | ================================================================
        |
        | Ab Ollama fallback chalega.
        |
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | DATABASE FLOW NOT FOUND
        |--------------------------------------------------------------------------
        | IMPORTANT:
        | We do NOT allow the normal Ollama prompt to answer freely here.
        |
        | First we build only Tata Capital related knowledge context.
        | Then Ollama is strictly instructed:
        |
        |   1. Tata Capital question -> answer only from supplied context.
        |   2. No supplied/verified information -> say information unavailable.
        |   3. Non-Tata-Capital question -> fixed Tata Capital-only response.
        |   4. Never invent facts.
        |--------------------------------------------------------------------------
        */

        $detectedIntent = $this->intent->classify(
            $userText,
            $history
        );

        $kbContextStr = $this->kb->buildContext(
            $detectedIntent,
            $userText
        );

        $kbContext = [];

        if (trim((string) $kbContextStr) !== '') {
            $kbContext[] = $kbContextStr;
        }

        try {

            $strictPrompt = $this->buildStrictFallbackPrompt(
                $userText,
                $history,
                $kbContextStr,
                $language
            );

            $aiText = $this->ollama->chat(
                [],
                $strictPrompt,
                [],
                $language
            );

        } catch (\Throwable $e) {

            log_message(
                'error',
                'Strict Ollama fallback error: ' . $e->getMessage()
            );

            $aiText = $this->getOutOfScopeMessage($language);
        }

        /*
        |--------------------------------------------------------------------------
        | EMPTY AI RESPONSE FALLBACK
        |--------------------------------------------------------------------------
        */
        if (trim($aiText) === '') {
            $aiText = $fallbackAI;
        }

        /*
        |--------------------------------------------------------------------------
        | TTS
        |--------------------------------------------------------------------------
        */
        $ttsUrl = null;

        if (
            !empty($session['voice_enabled']) &&
            (int) $session['voice_enabled'] === 1
        ) {
            $ttsResult = $this->tts->synthesize(
                $aiText,
                $language
            );
            $ttsUrl = $ttsResult['url'] ?? null;
        }

        /*
        |--------------------------------------------------------------------------
        | SAVE OLLAMA RESPONSE
        |--------------------------------------------------------------------------
        */
        $this->messageModel->appendMessage(
            $sessionId,
            'assistant',
            $aiText,
            $ttsUrl
        );

        /*
        |--------------------------------------------------------------------------
        | FINAL OLLAMA RESPONSE
        |--------------------------------------------------------------------------
        */
        return $this->response->setJSON([
            'success'       => true,
            'session_id'    => $session['session_uuid'],
            'language'      => $language,
            'message'       => $aiText,
            'tts_url'       => $ttsUrl,
            'ticket_number' => null,
            'escalation'    => false,
            'new_session'   => $newSession,
            'source'        => 'ollama_fallback',
            'flow_id'       => null,
            'step_id'       => null,
            'step_key'      => null,
            'input_type'    => null,
            'options'       => [],
        ]);
    }

   /**
 * --------------------------------------------------------------------------
 * PROCESS DATABASE FLOW
 * --------------------------------------------------------------------------
 */
private function processDatabaseFlow(
    int $sessionId,
    string $userText,
    string $language = 'en'
): ?array {

    $db = \Config\Database::connect();

    $userText = trim((string) $userText);

    /*
    |--------------------------------------------------------------------------
    | POST-COMPLAINT HANDLING
    |--------------------------------------------------------------------------
    | After a complaint is successfully registered, do not repeat the
    | previous ticket message when the user says "okay", "thanks", etc.
    |--------------------------------------------------------------------------
    */

    $complaintCompleted = $this->getStepValue(
        $sessionId,
        'complaint_completed'
    );

    if ($complaintCompleted === '1') {

        $normalizedPostComplaint = strtolower($userText);

        $normalizedPostComplaint = str_replace(
            ['_', '-'],
            ' ',
            $normalizedPostComplaint
        );

        $normalizedPostComplaint = preg_replace(
            '/[^\p{L}\p{N}\s]+/u',
            ' ',
            $normalizedPostComplaint
        );

        $normalizedPostComplaint = trim(
            (string) preg_replace(
                '/\s+/u',
                ' ',
                $normalizedPostComplaint
            )
        );

        $thanksMessages = [
            'ok',
            'okay',
            'thanks',
            'thank you',
            'thankyou',
            'ok thanks',
            'okay thanks',
            'thanks a lot',
            'thank you so much',
            'great thanks',
            'got it',
            'alright',
            'all right',
            'fine'
        ];

        if (in_array($normalizedPostComplaint, $thanksMessages, true)) {

            return [
                'message' => $language === 'hi'
                    ? 'आपका स्वागत है। क्या मैं आपकी किसी और चीज़ में मदद कर सकता हूँ?'
                    : 'You’re welcome! Is there anything else I can help you with?',

                'flow_id'    => 0,
                'step_id'    => 0,
                'step_key'   => 'post_complaint',
                'input_type' => 'text',

                'options' => [
                    [
                        'id'            => 0,
                        'label'         => '💰 Loan Enquiry',
                        'value'         => 'loan enquiry',
                        'next_step_id' => 0,
                        'sort_order'    => 1
                    ],
                    [
                        'id'            => 0,
                        'label'         => '📋 Raise Complaint',
                        'value'         => 'raise complaint',
                        'next_step_id' => 0,
                        'sort_order'    => 2
                    ],
                    [
                        'id'            => 0,
                        'label'         => '👤 Human Agent',
                        'value'         => 'human agent',
                        'next_step_id' => 0,
                        'sort_order'    => 3
                    ]
                ]
            ];
        }

        /*
        | User has moved on to another request.
        | Clear the completion marker so the old complaint response
        | cannot interfere with the next interaction.
        */
        $this->saveStepValue(
            $sessionId,
            'complaint_completed',
            '0'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 1. CURRENT STEP
    |--------------------------------------------------------------------------
    */
    $currentStepId = $this->getCurrentChatbotStep($sessionId);

    // If a Home Loan application is already in progress, keep the user inside
    // that journey instead of falling back to the generic chatbot/Ollama flow.
    $applicationResponse = $this->handleHomeLoanApplicationFlow(
        $sessionId,
        $userText,
        $language
    );

    if ($applicationResponse !== null) {
        return $applicationResponse;
    }

    /*
    |--------------------------------------------------------------------------
    | 2. NEW CONVERSATION
    |--------------------------------------------------------------------------
    */
    if (!$currentStepId) {

        return $this->startDatabaseFlow(
            $sessionId,
            $userText,
            $language
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 3. LOAD CURRENT STEP
    |--------------------------------------------------------------------------
    */
    $step = $db->table('chatbot_steps')
        ->where('id', (int) $currentStepId)
        ->where('status', 'active')
        ->get()
        ->getRowArray();

    if (!$step) {

        $this->clearCurrentChatbotStep($sessionId);

        return $this->startDatabaseFlow(
            $sessionId,
            $userText,
            $language
        );
    }

    $stepKey = strtolower(
        trim((string) ($step['step_key'] ?? ''))
    );

    $inputType = strtolower(
        trim((string) ($step['input_type'] ?? ''))
    );

    /*
    |--------------------------------------------------------------------------
    | 4. LOAD CURRENT STEP OPTIONS
    |--------------------------------------------------------------------------
    */
    $options = $db->table('chatbot_options')
        ->where('step_id', (int) $step['id'])
        ->where('status', 'active')
        ->orderBy('sort_order', 'ASC')
        ->get()
        ->getResultArray();

    // ===== COMPLAINT FLOW FIX: Flow 4 (4 -> 60 -> 61 -> 62 -> 63) =====
    if ((int) ($step['flow_id'] ?? 0) === 4) {
        $complaintResponse = $this->processComplaintFlow(
            $sessionId,
            $step,
            $userText,
            $language,
            $options
        );

        if ($complaintResponse !== null) {
            return $complaintResponse;
        }
    }



    /*
    |--------------------------------------------------------------------------
    | 5. HOME LOAN MENU NAVIGATION
    |--------------------------------------------------------------------------
    |
    | After loan amount + tenure are completed, the chatbot must remain inside
    | the Home Loan flow. The user can then ask/select:
    |
    |   Eligibility
    |   Interest Rate
    |   Documents Required
    |   Apply Now
    |
    | These options are stored in chatbot_options for step 9
    | (home_loan_info). We therefore resolve these keywords directly from DB
    | even when the current step is 14/15/23/18 or another Home Loan step.
    | This prevents the request from falling through to Ollama.
    |
    |--------------------------------------------------------------------------
    */
    $homeLoanMenuStep = $db->table('chatbot_steps')
        ->where('step_key', 'home_loan_info')
        ->where('status', 'active')
        ->orderBy('id', 'ASC')
        ->get()
        ->getRowArray();

    if ($homeLoanMenuStep) {

        $homeLoanMenuOptions = $db->table('chatbot_options')
            ->where('step_id', (int) $homeLoanMenuStep['id'])
            ->where('status', 'active')
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $homeLoanOption = $this->matchHomeLoanInfoOption(
            $userText,
            $homeLoanMenuOptions
        );

        if ($homeLoanOption) {

            $homeLoanNextStepId = (int) (
                $homeLoanOption['next_step_id'] ?? 0
            );

            if ($homeLoanNextStepId > 0) {

                $homeLoanNextStep = $db->table('chatbot_steps')
                    ->where('id', $homeLoanNextStepId)
                    ->where('status', 'active')
                    ->get()
                    ->getRowArray();

                if ($homeLoanNextStep) {

                    $this->saveStepValue(
                        $sessionId,
                        'home_loan_info',
                        (string) $homeLoanOption['value']
                    );

                    $this->setCurrentChatbotStep(
                        $sessionId,
                        (int) $homeLoanNextStep['id']
                    );

                    return $this->buildStepResponse(
                        $sessionId,
                        $homeLoanNextStep
                    );
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 6. NORMALIZE USER TEXT
    |--------------------------------------------------------------------------
    */
    $normalizedText = strtolower($userText);

    $normalizedText = preg_replace(
        '/\s+/u',
        ' ',
        trim($normalizedText)
    );


    /*
    |--------------------------------------------------------------------------
    | 6. HOME LOAN INFO
    |--------------------------------------------------------------------------
    |
    | If user is currently on home_loan_info and enters amount directly:
    |
    | 100000
    | 200000
    | 5 lakh
    | 10 lakh
    | 10L
    | 1 crore
    |
    | Then immediately process amount.
    |
    |--------------------------------------------------------------------------
    */

    if ($stepKey === 'home_loan_info') {

        $directAmount = $this->parseIndianLoanAmount(
            $userText
        );

        if (
            $directAmount !== null &&
            $this->looksLikeLoanAmount($userText)
        ) {

            $loanAmountStep = $db->table('chatbot_steps')
                ->where('step_key', 'loan_amount')
                ->where('status', 'active')
                ->orderBy('id', 'ASC')
                ->get()
                ->getRowArray();

            if ($loanAmountStep) {

                $amountOptions = $db->table('chatbot_options')
                    ->where(
                        'step_id',
                        (int) $loanAmountStep['id']
                    )
                    ->where('status', 'active')
                    ->orderBy('sort_order', 'ASC')
                    ->get()
                    ->getResultArray();


                /*
                |--------------------------------------------------------------------------
                | MATCH USER AMOUNT WITH DATABASE RANGE
                |--------------------------------------------------------------------------
                */

                $matchedAmountOption =
                    $this->matchLoanAmountRange(
                        $directAmount,
                        $amountOptions
                    );


                if ($matchedAmountOption) {

                    /*
                    |--------------------------------------------------------------------------
                    | SAVE DATABASE VALUE
                    |--------------------------------------------------------------------------
                    */

                    $this->saveStepValue(
                        $sessionId,
                        $loanAmountStep['step_key'],
                        (string) $matchedAmountOption['value']
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | GO TO NEXT STEP
                    |--------------------------------------------------------------------------
                    */

                    $nextStep =
                        $this->getInputStepNextStep(
                            'loan_amount'
                        );


                    if ($nextStep) {

                        $this->setCurrentChatbotStep(
                            $sessionId,
                            (int) $nextStep['id']
                        );

                        return $this->buildStepResponse(
                            $sessionId,
                            $nextStep
                        );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | AMOUNT OUTSIDE DB RANGE
                |--------------------------------------------------------------------------
                |
                | Never send to Ollama.
                |
                */

                $this->setCurrentChatbotStep(
                    $sessionId,
                    (int) $loanAmountStep['id']
                );

                return $this->buildStepResponse(
                    $sessionId,
                    $loanAmountStep
                );
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | 7. LOAN AMOUNT STEP
    |--------------------------------------------------------------------------
    |
    | This is the MOST IMPORTANT part.
    |
    | DB:
    |
    | 13 = loan_amount
    |
    | Options:
    |
    | ₹1 Lakh – ₹5 Lakh
    | ₹5 Lakh – ₹10 Lakh
    | ₹10 Lakh – ₹25 Lakh
    | ₹25 Lakh – ₹50 Lakh
    | ₹50 Lakh+
    |
    |--------------------------------------------------------------------------
    */

    if ($stepKey === 'loan_amount') {

        /*
        |--------------------------------------------------------------------------
        | 7A. USER SELECTED A LOAN-AMOUNT RANGE OPTION
        |--------------------------------------------------------------------------
        | Example:
        | User clicks/types:
        |   ₹1 Lakh – ₹5 Lakh
        |   ₹5 Lakh – ₹10 Lakh
        |   ₹10 Lakh – ₹25 Lakh
        |
        | These are valid DB options, not numeric amounts.
        | Match them FIRST and move to the next input step (tenure).
        |--------------------------------------------------------------------------
        */

        $matchedRangeOption = $this->matchChatbotOption(
            $userText,
            $options
        );

        if ($matchedRangeOption) {

            $this->saveStepValue(
                $sessionId,
                $step['step_key'],
                (string) $matchedRangeOption['value']
            );

            // loan_amount -> tenure is an input-step transition.
            $nextStep = $this->getInputStepNextStep('loan_amount');

            if ($nextStep) {

                $this->setCurrentChatbotStep(
                    $sessionId,
                    (int) $nextStep['id']
                );

                return $this->buildStepResponse(
                    $sessionId,
                    $nextStep
                );
            }

            // Fallback if DB option itself has next_step_id.
            $nextStepId = (int) (
                $matchedRangeOption['next_step_id'] ?? 0
            );

            if ($nextStepId > 0) {

                $nextStep = $db->table('chatbot_steps')
                    ->where('id', $nextStepId)
                    ->where('status', 'active')
                    ->get()
                    ->getRowArray();

                if ($nextStep) {

                    $this->setCurrentChatbotStep(
                        $sessionId,
                        (int) $nextStep['id']
                    );

                    return $this->buildStepResponse(
                        $sessionId,
                        $nextStep
                    );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 7B. USER SAYS "LOAN AMOUNT"
        |--------------------------------------------------------------------------
        |
        | Examples:
        |
        | loan amount
        | loan amt
        | amount
        | home loan amount
        | loan amount chahiye
        | amount chahiye
        |
        | These are NOT amounts.
        |
        | Therefore:
        | DO NOT SEND TO OLLAMA.
        |
        |--------------------------------------------------------------------------
        */

        $loanAmountKeywords = [
            'loan amount',
            'loan amt',
            'loan amnt',
            'home loan amount',
            'home loan amt',
            'amount',
            'loan amount chahiye',
            'loan amt chahiye',
            'amount chahiye',
            'loan amount batao',
            'loan amount bataye',
            'loan amount dekhna hai',
            'how much loan',
            'how much amount',
            'loan kitna',
            'kitna loan',
            'kitna amount'
        ];


        $isLoanAmountQuestion = false;


        foreach ($loanAmountKeywords as $keyword) {

            if (
                $normalizedText === $keyword ||
                str_contains(
                    $normalizedText,
                    $keyword
                )
            ) {

                $isLoanAmountQuestion = true;

                break;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | USER ASKED FOR LOAN AMOUNT
        |--------------------------------------------------------------------------
        */

        if ($isLoanAmountQuestion) {

            /*
            |--------------------------------------------------------------------------
            | STAY ON CURRENT LOAN AMOUNT STEP
            |--------------------------------------------------------------------------
            */

            $this->setCurrentChatbotStep(
                $sessionId,
                (int) $step['id']
            );


            /*
            |--------------------------------------------------------------------------
            | SHOW DB MESSAGE + DB OPTIONS
            |--------------------------------------------------------------------------
            */

            return $this->buildStepResponse(
                $sessionId,
                $step
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 7C. PARSE ACTUAL LOAN AMOUNT
        |--------------------------------------------------------------------------
        |
        | Examples:
        |
        | 100000
        | 200000
        | 500000
        | 1000000
        | 10 lakh
        | 10L
        | ₹10,00,000
        | 1 crore
        |
        |--------------------------------------------------------------------------
        */

        $amount = $this->parseIndianLoanAmount(
            $userText
        );


        /*
        |--------------------------------------------------------------------------
        | 7D. AMOUNT FOUND
        |--------------------------------------------------------------------------
        */

        if ($amount !== null) {

            /*
            |--------------------------------------------------------------------------
            | MATCH AGAINST DB OPTIONS
            |--------------------------------------------------------------------------
            */

            $matchedAmountOption =
                $this->matchLoanAmountRange(
                    $amount,
                    $options
                );


            /*
            |--------------------------------------------------------------------------
            | RANGE MATCHED
            |--------------------------------------------------------------------------
            */

            if ($matchedAmountOption) {

                /*
                |--------------------------------------------------------------------------
                | SAVE CANONICAL DATABASE VALUE
                |--------------------------------------------------------------------------
                |
                | Example:
                |
                | User = 1000000
                |
                | Saved value:
                |
                | 10_25_lakh
                |
                |--------------------------------------------------------------------------
                */

                $this->saveStepValue(
                    $sessionId,
                    $step['step_key'],
                    (string) $matchedAmountOption['value']
                );


                /*
                |--------------------------------------------------------------------------
                | GET NEXT STEP
                |--------------------------------------------------------------------------
                |
                | Your DB has:
                |
                | loan_amount -> next_step_id = 16
                |
                | And step 16 = tenure
                |
                |--------------------------------------------------------------------------
                */

                $nextStep =
                    $this->getInputStepNextStep(
                        'loan_amount'
                    );


                /*
                |--------------------------------------------------------------------------
                | NEXT STEP FOUND
                |--------------------------------------------------------------------------
                */

                if ($nextStep) {

                    $this->setCurrentChatbotStep(
                        $sessionId,
                        (int) $nextStep['id']
                    );


                    return $this->buildStepResponse(
                        $sessionId,
                        $nextStep
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | FALLBACK: USE OPTION NEXT STEP
                |--------------------------------------------------------------------------
                */

                $nextStepId = (int) (
                    $matchedAmountOption['next_step_id']
                    ?? 0
                );


                if ($nextStepId > 0) {

                    $nextStep = $db->table('chatbot_steps')
                        ->where(
                            'id',
                            $nextStepId
                        )
                        ->where(
                            'status',
                            'active'
                        )
                        ->get()
                        ->getRowArray();


                    if ($nextStep) {

                        $this->setCurrentChatbotStep(
                            $sessionId,
                            (int) $nextStep['id']
                        );


                        return $this->buildStepResponse(
                            $sessionId,
                            $nextStep
                        );
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | AMOUNT UNDERSTOOD BUT NO DB RANGE MATCH
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | Never return NULL here.
            |
            | NULL means controller can go to Ollama.
            |
            |--------------------------------------------------------------------------
            */

            $this->setCurrentChatbotStep(
                $sessionId,
                (int) $step['id']
            );


            return $this->buildStepResponse(
                $sessionId,
                $step
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 7E. AMOUNT-LIKE TEXT BUT PARSER FAILED
        |--------------------------------------------------------------------------
        |
        | Examples:
        |
        | 10lakh
        | 10 lacs
        | 5lac
        | etc.
        |
        | Keep inside DB flow.
        |--------------------------------------------------------------------------
        */

        if (
            $this->looksLikeLoanAmount(
                $userText
            )
        ) {

            $this->setCurrentChatbotStep(
                $sessionId,
                (int) $step['id']
            );


            return $this->buildStepResponse(
                $sessionId,
                $step
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | 8. MATCH NORMAL DATABASE OPTION
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | User: Home Loan
    |
    | DB:
    | home_loan
    |
    |--------------------------------------------------------------------------
    */

    $matchedOption = $this->matchChatbotOption(
        $userText,
        $options
    );


    /*
    |--------------------------------------------------------------------------
    | 9. OPTION MATCHED
    |--------------------------------------------------------------------------
    */

    if ($matchedOption) {

        /*
        |--------------------------------------------------------------------------
        | SAVE SELECTED VALUE
        |--------------------------------------------------------------------------
        */

        $this->saveStepValue(
            $sessionId,
            $step['step_key'],
            (string) $matchedOption['value']
        );


        /*
        |--------------------------------------------------------------------------
        | GET NEXT STEP ID
        |--------------------------------------------------------------------------
        */

        $nextStepId = (int) (
            $matchedOption['next_step_id']
            ?? 0
        );


        /*
        |--------------------------------------------------------------------------
        | NO NEXT STEP
        |--------------------------------------------------------------------------
        */

        if ($nextStepId <= 0) {

            /*
            |--------------------------------------------------------------------------
            | TENURE IS THE END OF THE INPUT CHAIN
            |--------------------------------------------------------------------------
            | Your DB has tenure options with next_step_id = NULL.
            | That is OK, but Home Loan enquiry should NOT end here.
            |
            | After tenure:
            |   1. Save the selected tenure.
            |   2. Keep chatbot state on home_loan_info (step 9).
            |   3. Show the Home Loan information options again:
            |      Eligibility, Interest Rate, Documents Required, Apply Now.
            |
            | This allows the user to continue instead of going to Ollama.
            |--------------------------------------------------------------------------
            */

            $selectedTenure = trim(
                (string) (
                    $matchedOption['label']
                    ?? $matchedOption['value']
                    ?? ''
                )
            );

            if ($stepKey === 'tenure') {
                // Do not end the journey at tenure. Create a Home Loan draft
                // and move into the professional eligibility/application flow.
                $this->createOrUpdateLoanApplicationDraft(
                    $sessionId,
                    $selectedTenure
                );

                $amount = $this->getApplicationValue($sessionId, 'loan_amount');
                $tenure = $selectedTenure;

                $this->setApplicationState($sessionId, 'awaiting_eligibility_confirmation');

                return [
                    'message' => $this->buildLoanSummaryMessage($amount, $tenure),
                    'flow_id' => (int) $step['flow_id'],
                    'step_id' => (int) $step['id'],
                    'step_key' => 'loan_summary',
                    'input_type' => 'confirmation',
                    'options' => [
                        ['id'=>0,'label'=>'✅ Yes, check my eligibility','value'=>'yes','next_step_id'=>0,'sort_order'=>1],
                        ['id'=>0,'label'=>'❌ Not now','value'=>'no','next_step_id'=>0,'sort_order'=>2],
                    ],
                ];
            }

            // For any other genuine terminal step, keep the old behaviour.
            $this->clearCurrentChatbotStep($sessionId);

            return [
                'message'    => $selectedTenure !== ''
                    ? "Thank you. Your Home Loan enquiry has been recorded for {$selectedTenure} tenure."
                    : 'Thank you. Your Home Loan enquiry has been recorded successfully.',
                'flow_id'    => (int) $step['flow_id'],
                'step_id'    => (int) $step['id'],
                'step_key'   => $step['step_key'],
                'input_type' => 'end',
                'options'    => [],
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | LOAD NEXT STEP
        |--------------------------------------------------------------------------
        */

        $nextStep = $db->table('chatbot_steps')
            ->where(
                'id',
                $nextStepId
            )
            ->where(
                'status',
                'active'
            )
            ->get()
            ->getRowArray();


        if (!$nextStep) {

            $this->clearCurrentChatbotStep(
                $sessionId
            );

            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | HOME LOAN -> LOAN AMOUNT
        |--------------------------------------------------------------------------
        |
        | DB:
        |
        | home_loan -> home_loan_info
        |
        | We directly move to loan_amount.
        |
        |--------------------------------------------------------------------------
        */

        $nextStepKey = strtolower(
            trim(
                (string) (
                    $nextStep['step_key']
                    ?? ''
                )
            )
        );


        if ($nextStepKey === 'home_loan_info') {

            $loanAmountStep = $db->table('chatbot_steps')
                ->where(
                    'step_key',
                    'loan_amount'
                )
                ->where(
                    'status',
                    'active'
                )
                ->orderBy(
                    'id',
                    'ASC'
                )
                ->get()
                ->getRowArray();


            if ($loanAmountStep) {

                $this->setCurrentChatbotStep(
                    $sessionId,
                    (int) $loanAmountStep['id']
                );


                return $this->buildStepResponse(
                    $sessionId,
                    $loanAmountStep
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | SET NEXT STEP
        |--------------------------------------------------------------------------
        */

        $this->setCurrentChatbotStep(
            $sessionId,
            (int) $nextStep['id']
        );


        /*
        |--------------------------------------------------------------------------
        | RETURN NEXT STEP
        |--------------------------------------------------------------------------
        */

        return $this->buildStepResponse(
            $sessionId,
            $nextStep
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 10. HOME LOAN CONTEXT
    |--------------------------------------------------------------------------
    |
    | If user writes:
    |
    | I want home loan
    | home loan
    | regular home loan
    |
    |--------------------------------------------------------------------------
    */

    if (
        $this->isHomeLoanText($userText)
        &&
        (
            $stepKey === 'home_loan_info'
            ||
            str_contains(
                $stepKey,
                'home_loan'
            )
        )
    ) {

        $this->setCurrentChatbotStep(
            $sessionId,
            (int) $step['id']
        );


        return $this->buildStepResponse(
            $sessionId,
            $step
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 11. HOME LOAN INFO -> USER ASKS FOR LOAN AMOUNT
    |--------------------------------------------------------------------------
    |
    | This is useful if somehow user is still on home_loan_info.
    |
    |--------------------------------------------------------------------------
    */

    if ($stepKey === 'home_loan_info') {

        $amountQuestionKeywords = [
            'loan amount',
            'loan amt',
            'home loan amount',
            'amount of loan',
            'how much loan',
            'borrow amount',
            'loan kitna',
            'kitna loan',
            'kitna amount'
        ];


        foreach (
            $amountQuestionKeywords
            as $keyword
        ) {

            if (
                str_contains(
                    $normalizedText,
                    $keyword
                )
            ) {

                $loanAmountStep = $db->table(
                    'chatbot_steps'
                )
                    ->where(
                        'step_key',
                        'loan_amount'
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->orderBy(
                        'id',
                        'ASC'
                    )
                    ->get()
                    ->getRowArray();


                if ($loanAmountStep) {

                    $this->setCurrentChatbotStep(
                        $sessionId,
                        (int) $loanAmountStep['id']
                    );


                    return $this->buildStepResponse(
                        $sessionId,
                        $loanAmountStep
                    );
                }

                break;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | 12. GENERIC INPUT TYPE
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | loan_amount is already handled above.
    |
    | So it MUST NOT reach this section.
    |--------------------------------------------------------------------------
    */

    if (
        $stepKey !== 'loan_amount'
        &&
        in_array(
            $inputType,
            [
                'text',
                'amount',
                'number',
                'mobile',
                'otp',
                'confirmation'
            ],
            true
        )
    ) {

        /*
        |--------------------------------------------------------------------------
        | SAVE USER VALUE
        |--------------------------------------------------------------------------
        */

        $this->saveStepValue(
            $sessionId,
            $step['step_key'],
            $userText
        );


        /*
        |--------------------------------------------------------------------------
        | GET NEXT STEP
        |--------------------------------------------------------------------------
        */

        $nextStep =
            $this->getInputStepNextStep(
                $stepKey
            );


        if ($nextStep) {

            $this->setCurrentChatbotStep(
                $sessionId,
                (int) $nextStep['id']
            );


            return $this->buildStepResponse(
                $sessionId,
                $nextStep
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SINGLE OPTION FALLBACK
        |--------------------------------------------------------------------------
        */

        $nextOption =
            $this->getNextStepFromSingleOption(
                $currentStepId
            );


        if (
            $nextOption
            &&
            !empty(
                $nextOption['next_step_id']
            )
        ) {

            $nextStep = $db->table(
                'chatbot_steps'
            )
                ->where(
                    'id',
                    (int) $nextOption['next_step_id']
                )
                ->where(
                    'status',
                    'active'
                )
                ->get()
                ->getRowArray();


            if ($nextStep) {

                $this->setCurrentChatbotStep(
                    $sessionId,
                    (int) $nextStep['id']
                );


                return $this->buildStepResponse(
                    $sessionId,
                    $nextStep
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | NO NEXT STEP
        |--------------------------------------------------------------------------
        */

        $this->clearCurrentChatbotStep(
            $sessionId
        );

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | 13. IMPORTANT FINAL FALLBACK
    |--------------------------------------------------------------------------
    |
    | Returning NULL here allows the caller to use Ollama.
    |
    | That is OK for unrelated/new questions.
    |
    | BUT loan_amount has already been completely handled above.
    |
    |--------------------------------------------------------------------------
    */

    return null;
}

    /**
     * --------------------------------------------------------------------------
     * START DATABASE FLOW
     * --------------------------------------------------------------------------
     */
private function startDatabaseFlow(
    int $sessionId,
    string $userText,
    string $language = 'en'
): ?array {

    $db = \Config\Database::connect();

    /*
    |--------------------------------------------------------------------------
    | Detect database flow
    |--------------------------------------------------------------------------
    */
    $flow = $this->detectDatabaseFlow($userText);

    if (!$flow) {
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Check whether this is Home Loan
    |--------------------------------------------------------------------------
    */
    $flowText = strtolower(
        trim(
            ($flow['flow_key'] ?? '') . ' ' .
            ($flow['name'] ?? '') . ' ' .
            ($flow['description'] ?? '')
        )
    );

    $isHomeLoan =
        str_contains($flowText, 'home_loan') ||
        str_contains($flowText, 'home loan');

    /*
    |--------------------------------------------------------------------------
    | HOME LOAN
    |--------------------------------------------------------------------------
    |
    | Home Loan select hote hi directly Loan Amount step ID 13
    |
    */
    if ($isHomeLoan) {

        $loanAmountStep = $db->table('chatbot_steps')
            ->where('step_key', 'loan_amount')
            ->where('status', 'active')
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        if ($loanAmountStep) {
            $this->setCurrentChatbotStep($sessionId, (int) $loanAmountStep['id']);
            return $this->buildStepResponse($sessionId, $loanAmountStep);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Normal database flow
    |--------------------------------------------------------------------------
    */

    $step = $db->table('chatbot_steps')
        ->where('flow_id', (int) $flow['id'])
        ->where('status', 'active')
        ->orderBy('sort_order', 'ASC')
        ->get()
        ->getRowArray();

    if (!$step) {
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Set current step
    |--------------------------------------------------------------------------
    */

    $this->setCurrentChatbotStep(
        $sessionId,
        (int) $step['id']
    );

    /*
    |--------------------------------------------------------------------------
    | Get options
    |--------------------------------------------------------------------------
    */

    $options = $db->table('chatbot_options')
        ->where('step_id', (int) $step['id'])
        ->where('status', 'active')
        ->orderBy('sort_order', 'ASC')
        ->get()
        ->getResultArray();

    /*
    |--------------------------------------------------------------------------
    | Try matching current user message
    |--------------------------------------------------------------------------
    */

    if (!empty($options)) {

        $matchedOption = $this->matchChatbotOption(
            $userText,
            $options
        );

        if ($matchedOption) {

            $this->saveStepValue(
                $sessionId,
                $step['step_key'],
                $matchedOption['value']
            );

            $nextStepId = (int) (
                $matchedOption['next_step_id'] ?? 0
            );

            if ($nextStepId > 0) {

                $nextStep = $db->table('chatbot_steps')
                    ->where('id', $nextStepId)
                    ->where('status', 'active')
                    ->get()
                    ->getRowArray();

                if ($nextStep) {

                    $this->setCurrentChatbotStep(
                        $sessionId,
                        (int) $nextStep['id']
                    );

                    return $this->buildStepResponse(
                        $sessionId,
                        $nextStep
                    );
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show current step
    |--------------------------------------------------------------------------
    */

    return $this->buildStepResponse(
        $sessionId,
        $step
    );
}

 /** Convert common Indian loan amount formats to rupees. */
 private function parseIndianLoanAmount(string $input): ?float
{
    $text = strtolower(trim($input));

    if ($text === '') {
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize
    |--------------------------------------------------------------------------
    */
    $text = str_replace(
        [
            '₹',
            'rs.',
            'rs',
            'inr',
            ',',
            '_'
        ],
        '',
        $text
    );

    $text = preg_replace(
        '/\s+/u',
        ' ',
        $text
    );

    $text = trim($text);


    /*
    |--------------------------------------------------------------------------
    | Crore
    |
    | 1 crore
    | 1 cr
    | 1.5 crore
    |--------------------------------------------------------------------------
    */
    if (
        preg_match(
            '/([0-9]+(?:\.[0-9]+)?)\s*(?:crores?|cr|c)\b/ui',
            $text,
            $m
        )
    ) {
        return (float)$m[1] * 10000000;
    }


    /*
    |--------------------------------------------------------------------------
    | Lakh
    |
    | 10 lakh
    | 10 lakhs
    | 10 lac
    | 10 lacs
    | 10L
    |--------------------------------------------------------------------------
    */
    if (
        preg_match(
            '/([0-9]+(?:\.[0-9]+)?)\s*(?:lakhs?|lacs?|lac|l)\b/ui',
            $text,
            $m
        )
    ) {
        return (float)$m[1] * 100000;
    }


    /*
    |--------------------------------------------------------------------------
    | Thousand
    |
    | 500k
    | 500 K
    |--------------------------------------------------------------------------
    */
    if (
        preg_match(
            '/([0-9]+(?:\.[0-9]+)?)\s*k\b/ui',
            $text,
            $m
        )
    ) {
        return (float)$m[1] * 1000;
    }


    /*
    |--------------------------------------------------------------------------
    | Plain number
    |
    | 100000
    | 1000000
    | 2500000
    |--------------------------------------------------------------------------
    */
    if (
        preg_match(
            '/^[0-9]+(?:\.[0-9]+)?$/',
            $text
        )
    ) {
        return (float)$text;
    }


    /*
    |--------------------------------------------------------------------------
    | Number inside text
    |
    | Example:
    | "I need 1000000"
    |--------------------------------------------------------------------------
    */
    if (
        preg_match(
            '/(?<![0-9.])([0-9]+(?:\.[0-9]+)?)(?![0-9.])/u',
            $text,
            $m
        )
    ) {
        return (float)$m[1];
    }


    return null;
}
    /** Keep amount-like input inside the DB flow instead of Ollama fallback. */
    private function looksLikeLoanAmount(string $input): bool
    {
        $text = strtolower(trim($input));
        $text = str_replace(['₹', ',', '_', ' '], '', $text);
        return (bool) preg_match('/^[0-9]+(?:\.[0-9]+)?(?:crores?|cr|c|lakhs?|lacs?|lac|l|k)?$/iu', $text);
    }

    /** Match parsed rupees against Home Loan ranges — dynamically parses bounds from label/value text, no hardcoded strings. */
    private function matchLoanAmountRange(float $amount, array $options): ?array
    {
        if ($amount <= 0 || empty($options)) {
            return null;
        }

        foreach ($options as $option) {
            $combined = strtolower(
                trim(($option['label'] ?? '') . ' ' . ($option['value'] ?? ''))
            );

            // Normalize separators so "1_5_lakh", "1-5 lakh", "1–5 Lakh" sab handle ho jaayen
            $normalized = str_replace(['_', '–', '—'], '-', $combined);

            // "50 lakh+" / "50_lakh_plus" -> open-ended upper range
            if (preg_match('/([0-9.]+)\s*(lakh|lac|l|crore|cr)s?\s*\+|plus/u', $normalized, $m)) {
                $min = $this->toRupees((float)$m[1], $m[2]);
                if ($amount >= $min) {
                    return $option;
                }
                continue;
            }

            // "1-5 lakh" / "1 lakh - 5 lakh" -> closed range
            if (preg_match(
                '/([0-9.]+)\s*(lakh|lac|l|crore|cr)?s?\s*-\s*([0-9.]+)\s*(lakh|lac|l|crore|cr)s?/u',
                $normalized,
                $m
            )) {
                $unit1 = $m[2] !== '' ? $m[2] : $m[4]; // "1-5 lakh" case: pehla number ka unit doosre se le lo
                $min = $this->toRupees((float)$m[1], $unit1);
                $max = $this->toRupees((float)$m[3], $m[4]);

                if ($amount >= $min && $amount <= $max) {
                    return $option;
                }
            }
        }

        return null;
    }

    /** Convert a number + unit word (lakh/crore/l/cr) into rupees. */
    private function toRupees(float $num, string $unit): float
    {
        $unit = strtolower(trim($unit));

        if (in_array($unit, ['crore', 'cr'], true)) {
            return $num * 10000000;
        }

        if (in_array($unit, ['lakh', 'lac', 'l'], true)) {
            return $num * 100000;
        }

        return $num;
    }


 private function matchChatbotOption(
    string $userText,
    array $options
): ?array {

    $userText = trim($userText);

    if (
        $userText === '' ||
        empty($options)
    ) {
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize
    |--------------------------------------------------------------------------
    */
    $normalize = static function (string $text): string {

        $text = strtolower(
            trim($text)
        );

        /*
        | Underscore ko space
        */
        $text = str_replace(
            ['_', '-'],
            ' ',
            $text
        );

        /*
        | Punctuation remove
        */
        $text = preg_replace(
            '/[^\p{L}\p{N}\s]+/u',
            ' ',
            $text
        );

        /*
        | Multiple spaces
        */
        $text = preg_replace(
            '/\s+/u',
            ' ',
            $text
        );

        return trim($text);
    };

    $normalizedUser = $normalize(
        $userText
    );

    /*
    |--------------------------------------------------------------------------
    | 1. Exact label / value
    |--------------------------------------------------------------------------
    */
    foreach ($options as $option) {

        $label = $normalize(
            (string) ($option['label'] ?? '')
        );

        $value = $normalize(
            (string) ($option['value'] ?? '')
        );

        if (
            $normalizedUser === $label ||
            $normalizedUser === $value
        ) {
            return $option;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 1A. TENURE SINGULAR / PLURAL MATCH
    |--------------------------------------------------------------------------
    | DB may contain:
    |   1 Year, 2 Years, 3 Years, 5 Years, 10 Years
    |
    | User may type:
    |   1 year, 2 year, 3 year, 5 year, 10 year
    |   3 years, 5 years
    |
    | Treat singular/plural as the same option.
    |--------------------------------------------------------------------------
    */
    foreach ($options as $option) {

        $label = $normalize(
            (string) ($option['label'] ?? '')
        );

        $value = $normalize(
            (string) ($option['value'] ?? '')
        );

        $candidates = array_filter([$label, $value]);

        foreach ($candidates as $candidate) {

            $candidateSingular = preg_replace(
                '/\\byears\\b/u',
                'year',
                $candidate
            );

            $candidateSingular = preg_replace(
                '/\\byear\\b/u',
                'year',
                $candidateSingular
            );

            $userSingular = preg_replace(
                '/\\byears\\b/u',
                'year',
                $normalizedUser
            );

            $userSingular = preg_replace(
                '/\\byear\\b/u',
                'year',
                $userSingular
            );

            if (
                $userSingular === $candidateSingular &&
                preg_match('/^\\d+\\s+year$/u', $candidateSingular)
            ) {
                return $option;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Common natural language matching
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | I want loan amount
    | I need loan amount
    | show loan amount
    | tell me loan amount
    |--------------------------------------------------------------------------
    */

    $requestWords = [
        'i want',
        'i need',
        'i require',
        'i would like',
        'please show',
        'show me',
        'tell me',
        'give me',
        'can you give',
        'can i know',
        'what is',
        'what are',
        'information about',
        'details about',
    ];

    $cleanUser = $normalizedUser;

    foreach ($requestWords as $prefix) {

        $prefix = $normalize($prefix);

        if (
            str_starts_with(
                $cleanUser,
                $prefix . ' '
            )
        ) {
            $cleanUser = trim(
                substr(
                    $cleanUser,
                    strlen($prefix)
                )
            );

            break;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Match option by cleaned text
    |--------------------------------------------------------------------------
    */
    foreach ($options as $option) {

        $label = $normalize(
            (string) ($option['label'] ?? '')
        );

        $value = $normalize(
            (string) ($option['value'] ?? '')
        );

        if (
            $label !== '' &&
            (
                $cleanUser === $label ||
                str_contains($cleanUser, $label) ||
                str_contains($label, $cleanUser)
            )
        ) {
            return $option;
        }

        if (
            $value !== '' &&
            (
                $cleanUser === $value ||
                str_contains($cleanUser, $value) ||
                str_contains($value, $cleanUser)
            )
        ) {
            return $option;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 4. HOME LOAN REQUEST
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | "regular home loan"
    | "green home loan"
    | "I need a home loan"
    |
    | ko Home Loan option se map karenge ONLY
    | jab current options mein actual Home Loan option available ho.
    |--------------------------------------------------------------------------
    */

    $homeLoanWords = [
        'home loan',
        'homeloan',
        'housing loan',
        'house loan',
        'home finance',
        'housing finance',
        'home loan please',
        'need home loan',
        'need a home loan',
        'want home loan',
        'want a home loan',
        'i need home loan',
        'i want home loan',
        'home loan chahiye',
        'ghar ka loan',
        'ghar ke liye loan',
        'regular home loan',
        'green home loan',
    ];

    $isHomeLoan = false;

    foreach ($homeLoanWords as $keyword) {

        $keyword = $normalize($keyword);

        if (
            str_contains(
                $normalizedUser,
                $keyword
            )
        ) {
            $isHomeLoan = true;
            break;
        }
    }

    if ($isHomeLoan) {

        foreach ($options as $option) {

            $label = $normalize(
                (string) ($option['label'] ?? '')
            );

            $value = $normalize(
                (string) ($option['value'] ?? '')
            );

            /*
            | Only actual Home Loan option
            */
            if (
                $label === 'home loan' ||
                $value === 'home loan' ||
                $value === 'home_loan' ||
                str_contains($label, 'home loan') ||
                str_contains($value, 'home loan')
            ) {
                return $option;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 5. YES / CONTINUE
    |--------------------------------------------------------------------------
    */
    $yesWords = [
        'yes',
        'yeah',
        'yep',
        'yup',
        'sure',
        'okay',
        'ok',
        'continue',
        'haan',
        'ha',
        'ji',
    ];

    if (
        in_array(
            $normalizedUser,
            $yesWords,
            true
        )
    ) {

        foreach ($options as $option) {

            $label = $normalize(
                (string) ($option['label'] ?? '')
            );

            $value = $normalize(
                (string) ($option['value'] ?? '')
            );

            if (
                $value === 'yes' ||
                $value === 'continue' ||
                str_contains($label, 'yes') ||
                str_contains($label, 'continue')
            ) {
                return $option;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 6. No match
    |--------------------------------------------------------------------------
    */
    return null;
}

    /**
     * Match Home Loan information-menu requests against the DB options.
     *
     * Examples:
     *   eligibility
     *   check eligibility
     *   interest rate
     *   documents required
     *   documents
     *   apply now
     *   I want to apply
     */
    private function matchHomeLoanInfoOption(
        string $userText,
        array $options
    ): ?array {

        $text = strtolower(trim($userText));
        $text = str_replace(['_', '-'], ' ', $text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);

        if ($text === '' || empty($options)) {
            return null;
        }

        foreach ($options as $option) {
            $label = strtolower(trim((string) ($option['label'] ?? '')));
            $value = strtolower(trim((string) ($option['value'] ?? '')));

            $label = str_replace(['_', '-'], ' ', $label);
            $value = str_replace(['_', '-'], ' ', $value);

            $label = trim((string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $label));
            $value = trim((string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value));
            $label = trim((string) preg_replace('/\s+/u', ' ', $label));
            $value = trim((string) preg_replace('/\s+/u', ' ', $value));

            // Exact DB label/value match.
            if ($text === $label || $text === $value) {
                return $option;
            }

            // Natural-language aliases for the DB options.
            $aliases = [];

            if ($value === 'eligibility' || str_contains($label, 'eligibility')) {
                $aliases = [
                    'eligibility',
                    'check eligibility',
                    'loan eligibility',
                    'home loan eligibility',
                    'am i eligible',
                    'am i eligible for home loan',
                    'eligibility check',
                ];
            } elseif ($value === 'interest rate' || str_contains($label, 'interest rate')) {
                $aliases = [
                    'interest rate',
                    'interest rates',
                    'rate',
                    'rates',
                    'home loan interest rate',
                    'home loan interest rates',
                    'roi',
                ];
            } elseif ($value === 'documents required' || str_contains($label, 'documents')) {
                $aliases = [
                    'documents',
                    'document',
                    'documents required',
                    'required documents',
                    'documents needed',
                    'which documents',
                    'home loan documents',
                ];
            } elseif ($value === 'apply now' || str_contains($label, 'apply now')) {
                $aliases = [
                    'apply now',
                    'apply',
                    'apply for loan',
                    'apply for home loan',
                    'home loan apply',
                    'i want to apply',
                    'want to apply',
                ];
            }

            foreach ($aliases as $alias) {
                if ($text === $alias || str_contains($text, $alias)) {
                    return $option;
                }
            }
        }

        return null;
    }

private function isHomeLoanText(string $userText): bool
{
    $text = strtolower(
        trim($userText)
    );

    $text = str_replace(
        ['_', '-'],
        ' ',
        $text
    );

    $text = preg_replace(
        '/\s+/u',
        ' ',
        $text
    );

    $keywords = [
        'home loan',
        'homeloan',
        'housing loan',
        'house loan',
        'home finance',
        'housing finance',
        'regular home loan',
        'green home loan',
        'need home loan',
        'need a home loan',
        'want home loan',
        'want a home loan',
        'i need home loan',
        'i need a home loan',
        'i want home loan',
        'i want a home loan',
        'home loan please',
        'home loan chahiye',
        'ghar ka loan',
        'ghar ke liye loan',
    ];

    foreach ($keywords as $keyword) {

        if (
            str_contains(
                $text,
                $keyword
            )
        ) {
            return true;
        }
    }

    return false;
}


    /* ======================================================================
     * HOME LOAN APPLICATION / ELIGIBILITY JOURNEY
     * ====================================================================== */

    private function handleHomeLoanApplicationFlow(int $sessionId, string $userText, string $language = 'en'): ?array
    {
        $db = \Config\Database::connect();
        $app = $db->table('loan_applications')->where('chat_session_id', $sessionId)->whereIn('status', ['draft','eligibility','eligible','application_pending','paused'])->orderBy('id','DESC')->get()->getRowArray();
        if (!$app) return null;

        $state = (string)($app['flow_state'] ?? '');
        $text = $this->normalizeApplicationText($userText);

        if ($state === 'awaiting_eligibility_confirmation') {
            // IMPORTANT: button text such as
            // "✅ Yes, check my eligibility" is normalized by isYes() and
            // must move directly to the applicant-name step. Never send this
            // confirmation back to Ollama.
            if ($this->isYes($text)) {
                $this->setApplicationState($sessionId, 'awaiting_name');
                return $this->applicationPrompt(
                    $sessionId,
                    'Great! Let’s start your Home Loan eligibility check. Please enter your full name.',
                    'awaiting_name'
                );
            }
            if ($this->isNo($text)) {
                $this->setApplicationState($sessionId, 'paused');
                return $this->applicationResponse($sessionId, 'No problem. Your Home Loan enquiry has been saved. Whenever you are ready, you can continue with the eligibility check.', [], 'application_paused');
            }
            return $this->applicationPrompt($sessionId, 'Would you like to continue with the Home Loan eligibility check?', 'awaiting_eligibility_confirmation', [
                ['id'=>0,'label'=>'✅ Yes, check my eligibility','value'=>'yes','next_step_id'=>0,'sort_order'=>1],
                ['id'=>0,'label'=>'❌ Not now','value'=>'no','next_step_id'=>0,'sort_order'=>2],
            ]);
        }

        switch ($state) {
            case 'awaiting_name':
                if (mb_strlen($userText) < 2 || preg_match('/^[0-9]+$/', trim($userText))) return $this->applicationPrompt($sessionId, 'Please enter a valid full name.', $state);
                $this->updateApplication($sessionId, ['customer_name'=>trim($userText)]);
                $this->setApplicationState($sessionId, 'awaiting_mobile');
                return $this->applicationPrompt($sessionId, 'Thank you. Please enter your 10-digit mobile number.', 'awaiting_mobile');

            case 'awaiting_mobile':
                $mobile = preg_replace('/\\D+/', '', $userText);
                if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) return $this->applicationPrompt($sessionId, 'Please enter a valid 10-digit Indian mobile number starting with 6, 7, 8 or 9.', $state);
                $otp = $this->issueOtp($sessionId, 'mobile', $mobile);
                $this->updateApplication($sessionId, ['mobile_number'=>$mobile, 'mobile_verified'=>0]);
                $this->setApplicationState($sessionId, 'awaiting_mobile_otp');
                $msg = 'We have sent a 6-digit verification OTP to your mobile number. Please enter the OTP to continue.';
                if (ENVIRONMENT !== 'production') $msg .= ' (Development OTP: '.$otp.')';
                return $this->applicationPrompt($sessionId, $msg, 'awaiting_mobile_otp');

            case 'awaiting_mobile_otp':
                if (!$this->verifyOtp($sessionId, 'mobile', $text)) return $this->applicationPrompt($sessionId, 'The OTP is incorrect or expired. Please enter the latest 6-digit OTP.', $state);
                $this->updateApplication($sessionId, ['mobile_verified'=>1]);
                $this->setApplicationState($sessionId, 'awaiting_email');
                return $this->applicationPrompt($sessionId, 'Mobile number verified successfully. Please enter your email address.','awaiting_email');

            case 'awaiting_email':
                $email = strtolower(trim($userText));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return $this->applicationPrompt($sessionId, 'Please enter a valid email address, for example name@example.com.', $state);
                $otp = $this->issueOtp($sessionId, 'email', $email);
                $this->updateApplication($sessionId, ['email'=>$email, 'email_verified'=>0]);
                $this->setApplicationState($sessionId, 'awaiting_email_otp');
                $msg = 'We have sent a 6-digit verification OTP to your email address. Please enter the OTP to continue.';
                if (ENVIRONMENT !== 'production') $msg .= ' (Development OTP: '.$otp.')';
                return $this->applicationPrompt($sessionId, $msg, 'awaiting_email_otp');

            case 'awaiting_email_otp':
                if (!$this->verifyOtp($sessionId, 'email', $text)) return $this->applicationPrompt($sessionId, 'The OTP is incorrect or expired. Please enter the latest 6-digit OTP.', $state);
                $this->updateApplication($sessionId, ['email_verified'=>1]);
                $this->setApplicationState($sessionId, 'awaiting_pan');
                return $this->applicationPrompt($sessionId, 'Email verified successfully. Please enter your 10-character PAN number.', 'awaiting_pan');

            case 'awaiting_pan':
                $pan = strtoupper(preg_replace('/\\s+/', '', $userText));
                if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) return $this->applicationPrompt($sessionId, 'Please enter a valid PAN in the format ABCDE1234F.', $state);
                $this->updateApplication($sessionId, ['pan_number'=>$pan]);
                $this->setApplicationState($sessionId, 'awaiting_aadhaar');
                return $this->applicationPrompt($sessionId, 'PAN format verified. Please enter your 12-digit Aadhaar number.', 'awaiting_aadhaar');

            case 'awaiting_aadhaar':
                $aadhaar = preg_replace('/\\D+/', '', $userText);
                if (!preg_match('/^[0-9]{12}$/', $aadhaar) || !$this->isValidAadhaar($aadhaar)) return $this->applicationPrompt($sessionId, 'Please enter a valid 12-digit Aadhaar number.', $state);
                $this->updateApplication($sessionId, ['aadhaar_number'=>$aadhaar]);
                $this->setApplicationState($sessionId, 'awaiting_employment');
                return $this->applicationPrompt($sessionId, 'Thank you. Please select your employment type.', 'awaiting_employment', [
                    ['id'=>0,'label'=>'👨‍💼 Salaried','value'=>'salaried','next_step_id'=>0,'sort_order'=>1],
                    ['id'=>0,'label'=>'🏢 Self Employed','value'=>'self_employed','next_step_id'=>0,'sort_order'=>2],
                    ['id'=>0,'label'=>'💼 Business Owner','value'=>'business_owner','next_step_id'=>0,'sort_order'=>3],
                ]);

            case 'awaiting_employment':
                $employment = $this->matchEmployment($text);
                if (!$employment) return $this->applicationPrompt($sessionId, 'Please select one of the available employment types: Salaried, Self Employed, or Business Owner.', $state, [
                    ['id'=>0,'label'=>'👨‍💼 Salaried','value'=>'salaried','next_step_id'=>0,'sort_order'=>1],['id'=>0,'label'=>'🏢 Self Employed','value'=>'self_employed','next_step_id'=>0,'sort_order'=>2],['id'=>0,'label'=>'💼 Business Owner','value'=>'business_owner','next_step_id'=>0,'sort_order'=>3]
                ]);
                $this->updateApplication($sessionId, ['employment_type'=>$employment]);
                $this->setApplicationState($sessionId, 'awaiting_income');
                return $this->applicationPrompt($sessionId, 'Please enter your approximate monthly income in rupees.', 'awaiting_income');

            case 'awaiting_income':
                $income = $this->parseIndianLoanAmount($userText);
                if (!$income || $income <= 0) return $this->applicationPrompt($sessionId, 'Please enter a valid monthly income, for example ₹50,000 or 50000.', $state);
                $this->updateApplication($sessionId, ['monthly_income'=>(int)$income]);
                $this->setApplicationState($sessionId, 'awaiting_existing_emi');
                return $this->applicationPrompt($sessionId, 'Do you currently have any monthly EMIs? Please enter the total amount, or type 0 if you have none.', 'awaiting_existing_emi');

            case 'awaiting_existing_emi':
                $emi = $this->parseIndianLoanAmount($userText);
                if ($emi === null || $emi < 0) return $this->applicationPrompt($sessionId, 'Please enter a valid EMI amount, or type 0 if you have no existing EMI.', $state);
                $this->updateApplication($sessionId, ['existing_monthly_emi'=>(int)$emi]);
                $this->setApplicationState($sessionId, 'awaiting_age');
                return $this->applicationPrompt($sessionId, 'Please enter your age in completed years.', 'awaiting_age');

            case 'awaiting_age':
                $age = (int)filter_var($userText, FILTER_SANITIZE_NUMBER_INT);
                if ($age < 18 || $age > 80) return $this->applicationPrompt($sessionId, 'Please enter a valid age between 18 and 80 years.', $state);
                $this->updateApplication($sessionId, ['age'=>$age]);
                $this->setApplicationState($sessionId, 'awaiting_credit_consent');
                return $this->applicationPrompt($sessionId, 'Your basic details are complete. With your consent, we can now check your credit profile through an authorised credit-bureau service. Would you like to continue?', 'awaiting_credit_consent', [
                    ['id'=>0,'label'=>'✅ Yes, check credit profile','value'=>'yes','next_step_id'=>0,'sort_order'=>1],['id'=>0,'label'=>'❌ Not now','value'=>'no','next_step_id'=>0,'sort_order'=>2]
                ]);

            case 'awaiting_credit_consent':
                if ($this->isNo($text)) {
                    $this->setApplicationState($sessionId, 'paused');
                    return $this->applicationResponse($sessionId, 'No problem. Your details have been saved securely. You can continue the eligibility check later.', [], 'credit_check_paused');
                }

                if (!$this->isYes($text)) {
                    return $this->applicationPrompt($sessionId, 'Please confirm whether you want to continue with the credit-profile check.', 'awaiting_credit_consent', [
                        ['id'=>0,'label'=>'✅ Yes, check credit profile','value'=>'yes','next_step_id'=>0,'sort_order'=>1],
                        ['id'=>0,'label'=>'❌ Not now','value'=>'no','next_step_id'=>0,'sort_order'=>2]
                    ]);
                }

                // Do not manufacture a credit score or eligibility result.
                // Replace this point with the authorised credit-bureau API in production.
                $this->setApplicationState($sessionId, 'credit_check_completed');

                return $this->applicationResponse(
                    $sessionId,
                    "✅ Your consent has been recorded successfully.\n\n"
                    . "Your credit-check request has been initiated. Once the authorised credit-bureau response is available, your eligibility can be assessed.\n\n"
                    . "Is there anything else I can help you with regarding your Tata Capital loan?",
                    [
                        ['id'=>0,'label'=>'💬 Yes, I need more help','value'=>'more_help','next_step_id'=>0,'sort_order'=>1],
                        ['id'=>0,'label'=>'✅ No, thank you','value'=>'no_more_help','next_step_id'=>0,'sort_order'=>2]
                    ],
                    'credit_check_completed'
                );

            case 'credit_check_completed':
                // IMPORTANT: Do NOT close the conversation just because the user says
                // OK / OKAY / THANKS. Keep the session active until the customer
                // explicitly indicates that they are finished.
                if ($this->isExplicitConversationClose($text)) {
                    $this->setApplicationState($sessionId, 'closed');
                    return $this->applicationResponse(
                        $sessionId,
                        "You're very welcome! 😊 If you need anything else about Tata Capital loans, just let me know.",
                        [],
                        'conversation_closed'
                    );
                }

                // Keep the completed application alive while the customer asks
                // additional questions. The normal DB/KB router will answer them.
                return null;
        }
        return null;
    }

    private function createOrUpdateLoanApplicationDraft(int $sessionId, string $tenure): void
    {
        $amount = $this->getApplicationValue($sessionId, 'loan_amount');
        $db = \Config\Database::connect();
        $existing = $db->table('loan_applications')->where('chat_session_id',$sessionId)->whereIn('status',['draft','eligibility','eligible','application_pending'])->orderBy('id','DESC')->get()->getRowArray();
        $data = ['chat_session_id'=>$sessionId,'loan_type'=>'home_loan','loan_amount_range'=>$amount,'tenure'=>$tenure,'status'=>'draft','flow_state'=>'awaiting_eligibility_confirmation','updated_at'=>date('Y-m-d H:i:s')];
        if ($existing) $db->table('loan_applications')->where('id',$existing['id'])->update($data); else { $data['application_no']=null; $data['created_at']=date('Y-m-d H:i:s'); $db->table('loan_applications')->insert($data); }
    }

    private function getApplicationValue(int $sessionId, string $key): ?string
    {
        $db=\Config\Database::connect();
        $app=$db->table('loan_applications')->where('chat_session_id',$sessionId)->orderBy('id','DESC')->get()->getRowArray();
        if (!$app) return null;
        return isset($app[$key]) ? (string)$app[$key] : null;
    }

    private function setApplicationState(int $sessionId,string $state): void { $this->updateApplication($sessionId,['flow_state'=>$state]); }

    private function updateApplication(int $sessionId,array $data): void {
        $db=\Config\Database::connect();
        foreach (['pan_number','aadhaar_number'] as $sensitiveKey) {
            if (array_key_exists($sensitiveKey, $data) && $data[$sensitiveKey] !== null && $data[$sensitiveKey] !== '') {
                $data[$sensitiveKey] = $this->encryptSensitiveValue((string)$data[$sensitiveKey]);
            }
        }
        $data['updated_at']=date('Y-m-d H:i:s');
        $app=$db->table('loan_applications')->where('chat_session_id',$sessionId)->orderBy('id','DESC')->get()->getRowArray();
        if ($app) $db->table('loan_applications')->where('id',$app['id'])->update($data);
    }

    private function encryptSensitiveValue(string $value): string {
        return service('encrypter')->encrypt($value);
    }

    private function buildLoanSummaryMessage(?string $amount,string $tenure): string {
        $range=$this->humanAmountRange($amount);
        $db=\Config\Database::connect();
        $rateStep=$db->table('chatbot_steps')->where('step_key','interest_rate')->where('status','active')->orderBy('id','ASC')->get()->getRowArray();
        $rateText=$rateStep ? trim((string)($rateStep['message'] ?? '')) : '';
        if ($rateText === '') {
            $rateText='The applicable interest rate will be determined based on your profile and the applicable Tata Capital terms.';
        }
        return "Great. Your Home Loan enquiry is for {$range} with a {$tenure} tenure.\n\n📊 Interest Rate\n{$rateText}\n\nThe final rate and EMI are subject to eligibility and credit assessment.\n\nWould you like to continue with the eligibility check?";
    }

    private function humanAmountRange(?string $value): string {
        if (!$value) return 'the selected loan amount';
        return ucwords(str_replace('_',' ',$value));
    }

    private function applicationPrompt(int $sessionId,string $message,string $state,array $options=[]): array { $this->setApplicationState($sessionId,$state); return $this->applicationResponse($sessionId,$message,$options,'loan_application'); }

    private function applicationResponse(int $sessionId,string $message,array $options=[],string $stepKey='loan_application'): array {
        return ['message'=>$message,'flow_id'=>1,'step_id'=>0,'step_key'=>$stepKey,'input_type'=>'text','options'=>$options];
    }

    /**
     * Normalize application-flow input so button labels, emojis, commas and
     * punctuation do not prevent an exact state match.
     */
    private function normalizeApplicationText(string $text): string
    {
        $text = strtolower(trim($text));
        $text = str_replace(['_', '-'], ' ', $text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim((string) $text);
    }

    /**
     * Accept both typed answers and the complete button label:
     *   yes
     *   yes check my eligibility
     *   proceed with the check
     *   continue with eligibility
     */
    private function isYes(string $text): bool
    {
        $text = $this->normalizeApplicationText($text);

        if (in_array($text, [
            'yes',
            'y',
            'yeah',
            'yep',
            'yup',
            'haan',
            'ha',
            'ji',
            'sure',
            'okay',
            'ok',
            'continue',
            'proceed',
        ], true)) {
            return true;
        }

        $yesPhrases = [
            'yes check my eligibility',
            'yes check eligibility',
            'yes check my credit profile',
            'yes check credit profile',
            'yes proceed',
            'yes continue',
            'proceed with eligibility',
            'proceed with the eligibility check',
            'continue with eligibility',
            'continue with the eligibility check',
            'continue with the check',
            'start eligibility',
            'start eligibility check',
            'check my eligibility',
            'check eligibility',
            'eligibility check',
            'eligibility check karo',
            'haan check my eligibility',
            'ha check my eligibility',
            'haan eligibility check karo',
            'ha eligibility check karo',
        ];

        foreach ($yesPhrases as $phrase) {
            if ($text === $phrase || str_contains($text, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Only an explicit final response should close the conversation.
     * Simple replies such as OK / THANKS must keep the chat open because
     * the customer may still want another Tata Capital service/query.
     */
    private function isExplicitConversationClose(string $text): bool
    {
        $text = $this->normalizeApplicationText($text);

        $closingPhrases = [
            'no thanks',
            'no thank you',
            'nothing else',
            'nothing more',
            'thats all',
            "that's all",
            'all good thanks',
            'i am done',
            'im done',
            'i am finished',
            'im finished',
            'end chat',
            'close chat',
            'close conversation',
            'goodbye',
            'bye'
        ];

        return in_array($text, $closingPhrases, true)
            || str_contains($text, 'no thanks')
            || str_contains($text, 'no thank you')
            || str_contains($text, 'nothing else')
            || str_contains($text, 'thats all')
            || str_contains($text, "that's all");
    }

    /** Backward-compatible alias for any existing callers. */
    private function isConversationClosingText(string $text): bool
    {
        return $this->isExplicitConversationClose($text);
    }

    private function isNo(string $text): bool
    {
        $text = $this->normalizeApplicationText($text);

        if (in_array($text, [
            'no',
            'n',
            'nah',
            'not now',
            'later',
            'cancel',
            'no thanks',
            'not interested',
        ], true)) {
            return true;
        }

        return str_contains($text, 'not now') ||
               str_contains($text, 'no thanks');
    }

    private function matchEmployment(string $text): ?string {
        if (str_contains($text,'salar')) return 'salaried';
        if (str_contains($text,'self employed') || str_contains($text,'self-employed')) return 'self_employed';
        if (str_contains($text,'business')) return 'business_owner';
        return null;
    }

    private function isValidAadhaar(string $aadhaar): bool {
        // Verhoeff checksum validation. Format validation alone is not sufficient.
        if (!preg_match('/^[0-9]{12}$/',$aadhaar) || preg_match('/^(\\d)\\1{11}$/',$aadhaar)) return false;
        $d=[[0,1,2,3,4,5,6,7,8,9],[1,2,3,4,0,6,7,8,9,5],[2,3,4,0,1,7,8,9,5,6],[3,4,0,1,2,8,9,5,6,7],[4,0,1,2,3,9,5,6,7,8],[5,9,8,7,6,0,4,3,2,1],[6,5,9,8,7,1,0,4,3,2],[7,6,5,9,8,2,1,0,4,3],[8,7,6,5,9,3,2,1,0,4],[9,8,7,6,5,4,3,2,1,0]];
        $p=[[0,1,2,3,4,5,6,7,8,9],[1,5,7,6,2,8,3,0,9,4],[5,8,0,3,7,9,6,1,4,2],[8,9,1,6,0,4,3,5,2,7],[9,4,5,3,1,2,6,8,7,0],[4,2,8,6,5,7,3,9,0,1],[2,7,9,8,6,0,4,3,1,5],[7,0,4,3,2,5,9,6,8,1]];
        $inv=[0,4,3,2,1,5,6,7,8,9]; $c=0; $digits=array_reverse(array_map('intval',str_split($aadhaar))); foreach($digits as $i=>$num) $c=$d[$c][$p[$i%8][$num]]; return $c===0;
    }

    private function issueOtp(int $sessionId,string $channel,string $destination): string {
        $db=\Config\Database::connect(); $otp=(string)random_int(100000,999999);
        $db->table('loan_otp_codes')->insert(['chat_session_id'=>$sessionId,'channel'=>$channel,'destination_hash'=>hash('sha256',$destination),'otp_hash'=>password_hash($otp,PASSWORD_DEFAULT),'expires_at'=>date('Y-m-d H:i:s',time()+300),'attempts'=>0,'created_at'=>date('Y-m-d H:i:s')]);
        // Integrate your SMS/email provider here. Never log/send OTP in production response.
        return $otp;
    }

    private function verifyOtp(int $sessionId,string $channel,string $otp): bool {
        if (!preg_match('/^\\d{6}$/',$otp)) return false; $db=\Config\Database::connect();
        $row=$db->table('loan_otp_codes')->where('chat_session_id',$sessionId)->where('channel',$channel)->where('expires_at >=',date('Y-m-d H:i:s'))->orderBy('id','DESC')->get()->getRowArray();
        if (!$row || (int)$row['attempts']>=5) return false;
        if (password_verify($otp,$row['otp_hash'])) { $db->table('loan_otp_codes')->where('id',$row['id'])->update(['verified_at'=>date('Y-m-d H:i:s')]); return true; }
        $db->table('loan_otp_codes')->where('id',$row['id'])->set('attempts','attempts+1',false)->update(); return false;
    }

    private function buildStepResponse(
        int $sessionId,
        array $step
    ): array {
        $db = \Config\Database::connect();

        /*
        |--------------------------------------------------------------------------
        | Options
        |--------------------------------------------------------------------------
        */
        $options = $db->table('chatbot_options')
            ->where('step_id', (int) $step['id'])
            ->where('status', 'active')
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        /*
        |--------------------------------------------------------------------------
        | Frontend-friendly options
        |--------------------------------------------------------------------------
        */
        $formattedOptions = [];

        foreach ($options as $option) {
            $formattedOptions[] = [
                'id'           => (int) $option['id'],
                'label'        => $option['label'],
                'value'        => $option['value'],
                'next_step_id' => (int) ($option['next_step_id'] ?? 0),
                'sort_order'   => (int) $option['sort_order'],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | END STEP
        |--------------------------------------------------------------------------
        */
        if (
            strtolower($step['input_type'] ?? '') === 'end'
        ) {
            $this->clearCurrentChatbotStep(
                $sessionId
            );
        }

        return [
            'message'    => $step['message'] ?? '',
            'flow_id'    => (int) $step['flow_id'],
            'step_id'    => (int) $step['id'],
            'step_key'   => $step['step_key'],
            'input_type' => $step['input_type'],
            'options'    => $formattedOptions,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CHATBOT STEP STATE (chatbot_sessions table)
    |--------------------------------------------------------------------------
    | IMPORTANT FIX:
    | chatbot_sessions has NO relation column to chat_sessions.id, and it
    | has NO 'chat_session_id' column either. The only usable existing
    | column is 'session_id' (varchar, UNIQUE key).
    |
    | We store the chat_sessions.id (cast to string) inside
    | chatbot_sessions.session_id, and use it consistently for
    | read/write/clear so the value written by setCurrentChatbotStep()
    | is exactly what getCurrentChatbotStep() reads back.
    |
    | setCurrentChatbotStep() upserts (insert if missing, else update) —
    | previously it only ran UPDATE, which silently affected 0 rows for
    | every new session and the state never persisted.
    |--------------------------------------------------------------------------
    */

    private function getCurrentChatbotStep(int $sessionId): ?int
    {
        $db = \Config\Database::connect();

        $row = $db->table('chatbot_sessions')
            ->select('current_step_id')
            ->where('session_id', (string) $sessionId)
            ->get()
            ->getRowArray();

        if (!$row) {
            return null;
        }

        $stepId = (int) ($row['current_step_id'] ?? 0);

        return $stepId > 0 ? $stepId : null;
    }

    private function setCurrentChatbotStep(
        int $sessionId,
        int $stepId
    ): void {
        $db = \Config\Database::connect();

        $exists = $db->table('chatbot_sessions')
            ->where('session_id', (string) $sessionId)
            ->countAllResults() > 0;

        if ($exists) {
            $db->table('chatbot_sessions')
                ->where('session_id', (string) $sessionId)
                ->update([
                    'current_step_id'  => $stepId,
                    'last_activity_at' => date('Y-m-d H:i:s'),
                ]);
        } else {
            $db->table('chatbot_sessions')->insert([
                'session_id'      => (string) $sessionId,
                'current_step_id' => $stepId,
                'status'          => 'active',
            ]);
        }
    }

    private function clearCurrentChatbotStep(
        int $sessionId
    ): void {
        $db = \Config\Database::connect();

        $db->table('chatbot_sessions')
            ->where('session_id', (string) $sessionId)
            ->update([
                'current_step_id' => null,
            ]);
    }

    private function saveStepValue(
        int $sessionId, string $stepKey, string $value
    ): void {
        $db = \Config\Database::connect();
        $stepKey = trim($stepKey);
        $value = trim($value);
        if ($sessionId <= 0 || $stepKey === '') return;

        $table = $db->table('chatbot_session_data');
        $existing = $table->where('session_id',$sessionId)
            ->where('data_key',$stepKey)->get()->getRowArray();

        if ($existing) {
            $table->where('id',(int)$existing['id'])->update([
                'data_value'=>$value, 'updated_at'=>date('Y-m-d H:i:s')
            ]);
        } else {
            $table->insert([
                'session_id'=>$sessionId, 'data_key'=>$stepKey, 'data_value'=>$value,
                'created_at'=>date('Y-m-d H:i:s'), 'updated_at'=>date('Y-m-d H:i:s')
            ]);
        }
    }

    private function getStepValue(int $sessionId, string $stepKey): ?string
    {
        $db = \Config\Database::connect();
        $row = $db->table('chatbot_session_data')->select('data_value')
            ->where('session_id',$sessionId)->where('data_key',trim($stepKey))
            ->get()->getRowArray();
        return $row ? trim((string)($row['data_value'] ?? '')) : null;
    }

    private function complaintOptions(array $options): array
    {
        return !empty($options) ? $options : [
            ['id'=>0,'label'=>'✅ Yes, Submit Complaint','value'=>'yes','next_step_id'=>63,'sort_order'=>1],
            ['id'=>0,'label'=>'❌ No, Cancel','value'=>'no','next_step_id'=>0,'sort_order'=>2],
        ];
    }

    private function processComplaintFlow(
        int $sessionId, array $step, string $userText, string $language, array $options
    ): ?array {
        $db = \Config\Database::connect();
        $key = strtolower(trim((string)($step['step_key'] ?? '')));

        if ($key === 'complaint_type') {
            $matched = $this->matchChatbotOption($userText,$options);
            if (!$matched) return $this->buildStepResponse($sessionId,$step);
            $this->saveStepValue($sessionId,'complaint_type',(string)($matched['value'] ?? $matched['label'] ?? ''));
            $nextId=(int)($matched['next_step_id'] ?? 0);
            $next=$nextId>0
                ? $db->table('chatbot_steps')->where('id',$nextId)->where('status','active')->get()->getRowArray()
                : $db->table('chatbot_steps')->where('flow_id',4)->where('step_key','complaint_details')->where('status','active')->orderBy('id','ASC')->get()->getRowArray();
            if (!$next) return null;
            $this->setCurrentChatbotStep($sessionId,(int)$next['id']);
            return $this->buildStepResponse($sessionId,$next);
        }

        if ($key === 'complaint_details') {
            $description=trim($userText);
            if (mb_strlen($description)<5) return ['message'=>$language==='hi'?'कृपया अपनी शिकायत थोड़ी विस्तार से लिखें।':'Please describe your complaint in a little more detail.','flow_id'=>4,'step_id'=>(int)$step['id'],'step_key'=>$key,'input_type'=>'text','options'=>[]];
            $this->saveStepValue($sessionId,'complaint_details',$description);
            $next=$db->table('chatbot_steps')->where('flow_id',4)->where('step_key','complaint_mobile')->where('status','active')->orderBy('id','ASC')->get()->getRowArray();
            if (!$next) return null;
            $this->setCurrentChatbotStep($sessionId,(int)$next['id']);
            return $this->buildStepResponse($sessionId,$next);
        }

        if ($key === 'complaint_mobile') {
            $mobile=preg_replace('/\D+/','',$userText);
            $mobile=preg_replace('/^91/','',$mobile);
            if (!preg_match('/^[6-9][0-9]{9}$/',$mobile)) return ['message'=>$language==='hi'?'कृपया 10 अंकों का सही मोबाइल नंबर दर्ज करें।':'Please enter a valid 10-digit Indian mobile number.','flow_id'=>4,'step_id'=>(int)$step['id'],'step_key'=>$key,'input_type'=>'text','options'=>[]];
            $this->saveStepValue($sessionId,'complaint_mobile',$mobile);
            $next=$db->table('chatbot_steps')->where('flow_id',4)->where('step_key','complaint_confirmation')->where('status','active')->orderBy('id','ASC')->get()->getRowArray();
            if (!$next) return null;
            $this->setCurrentChatbotStep($sessionId,(int)$next['id']);
            $category=$this->getStepValue($sessionId,'complaint_type') ?: 'Complaint';
            $description=$this->getStepValue($sessionId,'complaint_details') ?: '';
            $summary=$language==='hi'
                ? "कृपया अपनी शिकायत की पुष्टि करें:\n\nश्रेणी: {$category}\n\nशिकायत: {$description}\n\nमोबाइल: {$mobile}\n\nक्या आप इसे दर्ज करना चाहते हैं?"
                : "Please confirm your complaint:\n\nCategory: {$category}\n\nComplaint: {$description}\n\nMobile: {$mobile}\n\nWould you like to submit this complaint?";
            return ['message'=>$summary,'flow_id'=>4,'step_id'=>(int)$next['id'],'step_key'=>$next['step_key'],'input_type'=>'confirmation','options'=>$this->complaintOptions($options)];
        }

        if ($key === 'complaint_confirmation') {
            $text=strtolower(trim($userText));
            $text=str_replace(['_','-'],' ',$text);
            $text=trim((string)preg_replace('/[^\p{L}\p{N}\s]+/u',' ',$text));
            $text=trim((string)preg_replace('/\s+/u',' ',$text));
            $matched=$this->matchChatbotOption($userText,$options);
            $value=$matched?strtolower(trim((string)($matched['value']??''))):'';
            $yes=in_array($text,['yes','y','yeah','yep','sure','okay','ok','submit','submit complaint','yes submit complaint','haan','ha','ji','continue'],true)||in_array($value,['yes','submit','continue'],true);
            $no=in_array($text,['no','n','cancel','not now','no cancel','cancel complaint'],true)||in_array($value,['no','cancel'],true);
            if ($no) {
                $this->clearCurrentChatbotStep($sessionId);
                return ['message'=>$language==='hi'?'ठीक है। आपकी शिकायत दर्ज नहीं की गई है।':'Okay. Your complaint has not been submitted.','flow_id'=>4,'step_id'=>(int)$step['id'],'step_key'=>'complaint_cancelled','input_type'=>'end','options'=>[]];
            }
            if (!$yes) return ['message'=>$language==='hi'?'कृपया Yes चुनकर शिकायत दर्ज करें या No चुनकर cancel करें।':'Please select Yes to submit the complaint or No to cancel.','flow_id'=>4,'step_id'=>(int)$step['id'],'step_key'=>$key,'input_type'=>'confirmation','options'=>$this->complaintOptions($options)];

            $mobile=$this->getStepValue($sessionId,'complaint_mobile');
            $category=$this->getStepValue($sessionId,'complaint_type');
            $description=$this->getStepValue($sessionId,'complaint_details');
            if (!$mobile||!$category||!$description) {
                $this->clearCurrentChatbotStep($sessionId);
                return ['message'=>$language==='hi'?'शिकायत की जानकारी अधूरी है। कृपया दोबारा शिकायत दर्ज करें।':'Some complaint details are missing. Please start again.','flow_id'=>4,'step_id'=>(int)$step['id'],'step_key'=>'complaint_error','input_type'=>'end','options'=>[]];
            }
            try {
                $result=$this->complaint->create($sessionId,$mobile,$category,$description);
                $ticket=(string)($result['ticket_number']??'');
                $duplicate=!empty($result['duplicate']);
                $message=$language==='hi'
                    ? ($duplicate?"आपकी शिकायत पहले से दर्ज है। टिकट नंबर: {$ticket}।":"आपकी शिकायत सफलतापूर्वक दर्ज की गई है। टिकट नंबर: {$ticket}।")
                    : ($duplicate?"Your complaint is already registered. Ticket number: {$ticket}.":"Your complaint has been successfully registered. Ticket number: {$ticket}.");

                /*
                |--------------------------------------------------------------------------
                | MARK COMPLAINT AS COMPLETED
                |--------------------------------------------------------------------------
                */

                $this->saveStepValue(
                    $sessionId,
                    'complaint_completed',
                    '1'
                );

                $this->saveStepValue(
                    $sessionId,
                    'last_complaint_ticket',
                    $ticket
                );

                $this->clearCurrentChatbotStep($sessionId);
                return ['message'=>$message,'flow_id'=>4,'step_id'=>63,'step_key'=>'complaint_submitted','input_type'=>'end','options'=>[],'ticket_number'=>$ticket,'complaint_id'=>$result['id']??null];
            } catch (\Throwable $e) {
                log_message('error','[ComplaintFlow] '.$e->getMessage());
                return ['message'=>$language==='hi'?'शिकायत दर्ज नहीं हो सकी। कृपया कुछ देर बाद दोबारा प्रयास करें।':'Your complaint could not be registered right now. Please try again later.','flow_id'=>4,'step_id'=>(int)$step['id'],'step_key'=>'complaint_error','input_type'=>'text','options'=>[]];
            }
        }
        return null;
    }

    /**
     * Resolve transitions for input steps.
     *
     * Current Home Loan DB flow:
     * loan_amount (13) -> tenure (16)
     *
     * chatbot_options for loan_amount currently have NULL
     * next_step_id, so this transition cannot be read from options.
     */
    private function getInputStepNextStep(string $stepKey): ?array
    {
        $db = \Config\Database::connect();

        $nextStepKey = null;

        if (strtolower(trim($stepKey)) === 'loan_amount') {
            $nextStepKey = 'tenure';
        } elseif (strtolower(trim($stepKey)) === 'complaint_details') {
            $nextStepKey = 'complaint_mobile';
        } elseif (strtolower(trim($stepKey)) === 'complaint_mobile') {
            $nextStepKey = 'complaint_confirmation';
        }

        if ($nextStepKey === null) {
            return null;
        }

        return $db->table('chatbot_steps')
            ->where('step_key', $nextStepKey)
            ->where('status', 'active')
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray() ?: null;
    }

    private function getNextStepFromSingleOption(
        int $stepId
    ): ?array {
        $db = \Config\Database::connect();

        return $db->table('chatbot_options')
            ->where('step_id', $stepId)
            ->where('status', 'active')
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getRowArray() ?: null;
    }

 private function detectDatabaseFlow(string $userText): ?array
{
    $db = \Config\Database::connect();

    $text = strtolower(trim($userText));

    if ($text === '') {
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Direct product / loan detection
    |--------------------------------------------------------------------------
    */

    $productKeywords = [
        'home loan'       => 'home_loan',
        'housing loan'    => 'home_loan',
        'house loan'      => 'home_loan',

        'personal loan'   => 'personal_loan',
        'personal finance'=> 'personal_loan',

        'car loan'        => 'car_loan',
        'vehicle loan'    => 'car_loan',
        'auto loan'       => 'car_loan',

        'business loan'   => 'business_loan',
        'business finance'=> 'business_loan',

        'education loan'  => 'education_loan',
        'student loan'    => 'education_loan',
    ];

    foreach ($productKeywords as $keyword => $optionValue) {

        if (strpos($text, $keyword) === false) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Find loan_type step
        |--------------------------------------------------------------------------
        */

        $step = $db->table('chatbot_steps')
            ->where('step_key', 'loan_type')
            ->where('status', 'active')
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        if (!$step) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Find selected loan option
        |--------------------------------------------------------------------------
        */

        $option = $db->table('chatbot_options')
            ->where('step_id', (int) $step['id'])
            ->where('value', $optionValue)
            ->where('status', 'active')
            ->get()
            ->getRowArray();

        if (!$option) {
            return null;
        }

        $nextStepId = (int) ($option['next_step_id'] ?? 0);

        if ($nextStepId <= 0) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Find next step
        |--------------------------------------------------------------------------
        */

        $nextStep = $db->table('chatbot_steps')
            ->where('id', $nextStepId)
            ->where('status', 'active')
            ->get()
            ->getRowArray();

        if (!$nextStep) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Find actual flow
        |--------------------------------------------------------------------------
        */

        $flow = $db->table('chatbot_flows')
            ->where('id', (int) $nextStep['flow_id'])
            ->where('status', 'active')
            ->get()
            ->getRowArray();

        if ($flow) {
            return $flow;
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback flow object
        |--------------------------------------------------------------------------
        */

        return [
            'id'       => (int) $nextStep['flow_id'],
            'name'     => $nextStep['step_key'],
            'flow_key' => $nextStep['step_key'],
            'status'   => 'active',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Existing flows
    |--------------------------------------------------------------------------
    */

    // Common loan-enquiry spellings/phrases.
    // Keep these before the generic flow detection so the DB flow starts
    // even when the user types a small spelling mistake.
    $loanEnquiryKeywords = [
        'loan enquiry',
        'loan enquery',
        'loan enquary',
        'loan enquiery',
        'loan inquiry',
        'loan inquery',
        'loan options',
        'loan option',
        'tata capital loan options',
        'i want to know about tata capital loan options',
        'i want to know about loan options',
        'tell me about loan options',
        'show loan options',
        'show me loan options',
        'loan ke options',
        'loan ke option',
        'loan ki jankari',
        'loan ki information',
    ];

    foreach ($loanEnquiryKeywords as $keyword) {
        if (strpos($text, strtolower($keyword)) !== false) {
            $flow = $db->table('chatbot_flows')
                ->where('id', 1)
                ->where('status', 'active')
                ->get()
                ->getRowArray();

            if ($flow) {
                return $flow;
            }
        }
    }

    $flowKeywords = [

        6 => [
            'loan statement',
            'loan statements',
            'statement',
            'repayment statement',
            'transaction statement',
            'account statement',
        ],

        7 => [
            'loan status',
            'loan application status',
            'application status',
            'active loan status',
            'disbursement status',
            'loan details',
        ],

        8 => [
            'foreclosure',
            'loan foreclosure',
            'close loan',
            'foreclose loan',
            'foreclosure amount',
        ],

        9 => [
            'cancel loan',
            'loan cancellation',
            'cancellation',
            'cancel my loan',
        ],

        10 => [
            'disbursement',
            'loan disbursement',
            'disbursement status',
            'disbursement date',
            'disbursement process',
            'disbursement documents',
        ],
    ];

    foreach ($flowKeywords as $flowId => $keywords) {

        foreach ($keywords as $keyword) {

            if (strpos($text, strtolower($keyword)) !== false) {

                $flow = $db->table('chatbot_flows')
                    ->where('id', $flowId)
                    ->where('status', 'active')
                    ->get()
                    ->getRowArray();

                if ($flow) {
                    return $flow;
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Raise Complaint detection
    |--------------------------------------------------------------------------
    */
    $complaintKeywords = [
        'register a complaint', 'register complaint', 'raise a complaint',
        'raise complaint', 'make a complaint', 'file a complaint',
        'lodge a complaint', 'complaint register', 'complaint registration',
        'complaint karni hai', 'complaint karna hai', 'shikayat darj',
        'shikayat karni hai', 'shikayat karna hai', 'complaint'
    ];

    foreach ($complaintKeywords as $keyword) {
        if (strpos($text, strtolower($keyword)) !== false) {
            $flow = $db->table('chatbot_flows')
                ->where('id', 4)
                ->where('status', 'active')
                ->get()
                ->getRowArray();
            if ($flow) return $flow;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Generic flow detection
    |--------------------------------------------------------------------------
    */

    $flows = $db->table('chatbot_flows')
        ->where('status', 'active')
        ->orderBy('sort_order', 'ASC')
        ->get()
        ->getResultArray();

    foreach ($flows as $flow) {

        $flowName = strtolower(
            trim($flow['name'] ?? '')
        );

        $flowKey = strtolower(
            trim($flow['flow_key'] ?? '')
        );

        if (
            $flowName !== '' &&
            strpos($text, $flowName) !== false
        ) {
            return $flow;
        }

        if (
            $flowKey !== '' &&
            strpos(
                $text,
                str_replace('_', ' ', $flowKey)
            ) !== false
        ) {
            return $flow;
        }
    }

    return null;
}

    /*
    |--------------------------------------------------------------------------
    | STRICT DATABASE RESPONSE REWRITER
    |--------------------------------------------------------------------------
    | DB -> Ollama is allowed only for language polishing.
    | All factual information must remain exactly as supplied by DB.
    |--------------------------------------------------------------------------
    */
    private function rewriteDatabaseResponse(
        string $dbMessage,
        array $dbOptions,
        string $language = 'en'
    ): string {
        if (trim($dbMessage) === '' && empty($dbOptions)) {
            return '';
        }

        $optionLines = [];

        foreach ($dbOptions as $option) {
            $label = trim((string) ($option['label'] ?? ''));

            if ($label !== '') {
                $optionLines[] = $label;
            }
        }

        $optionsText = '';

        if (!empty($optionLines)) {
            $optionsText = "\nAVAILABLE DATABASE OPTIONS:\n- "
                . implode("\n- ", $optionLines);
        }

        $languageInstruction = ($language === 'hi')
            ? 'Reply in simple Hindi/Hinglish suitable for a customer.'
            : 'Reply in simple natural English.';

        $prompt = <<<PROMPT
You are the response writer for a Tata Capital chatbot.

DATABASE MESSAGE:
{$dbMessage}
{$optionsText}

STRICT RULES:

1. The database content above is the ONLY source of truth.
2. Rewrite it into one natural, customer-friendly response.
3. You may improve grammar and sentence structure.
4. You may NOT add any fact that is not present above.
5. You may NOT invent or change loan amounts.
6. You may NOT invent or change interest rates.
7. You may NOT invent or change tenure.
8. You may NOT invent eligibility, fees, documents or policies.
9. You may NOT replace database options with your own options.
10. If database options are present, show those options clearly.
11. Do not mention database, AI, prompt, model or internal system.
12. Do not answer an additional question from your own knowledge.
13. Keep the response concise.
14. {$languageInstruction}

Return ONLY the final customer-facing response.
PROMPT;

        try {
            return trim((string) $this->ollama->chat(
                [],
                $prompt,
                [],
                $language
            ));
        } catch (\Throwable $e) {
            log_message(
                'error',
                'Database response rewrite error: ' . $e->getMessage()
            );

            return $dbMessage;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STRICT FALLBACK PROMPT
    |--------------------------------------------------------------------------
    | Used only when the DB flow did not match.
    |
    | IMPORTANT:
    | KnowledgeBaseService must provide the verified Tata Capital
    | knowledge/context. Ollama is not allowed to invent information.
    |--------------------------------------------------------------------------
    */
    private function buildStrictFallbackPrompt(
        string $userText,
        array $history,
        string $kbContextStr,
        string $language = 'en'
    ): string {
        $context = trim($kbContextStr);

        $historyText = '';

        foreach (array_slice($history, -10) as $message) {
            $role = ($message['role'] ?? '') === 'assistant'
                ? 'ASSISTANT'
                : 'USER';

            $content = trim((string) ($message['content'] ?? ''));

            if ($content !== '') {
                $historyText .= $role . ': ' . $content . "\n";
            }
        }

        if ($context === '') {
            $context = 'NO VERIFIED TATA CAPITAL INFORMATION WAS FOUND IN THE KNOWLEDGE BASE.';
        }

        $languageInstruction = ($language === 'hi')
            ? 'Reply in simple Hindi/Hinglish.'
            : 'Reply in simple English.';

        return <<<PROMPT
You are a STRICT Tata Capital customer support chatbot.

USER QUESTION:
{$userText}

RECENT CONVERSATION:
{$historyText}

VERIFIED TATA CAPITAL KNOWLEDGE:
{$context}

STRICT POLICY:

1. You are ONLY a Tata Capital chatbot.
2. The user may ask only about Tata Capital products, loans, services,
   processes, eligibility, documents, rates, charges, applications,
   complaints or other Tata Capital related matters.
3. For a Tata Capital related question, answer ONLY using the
   VERIFIED TATA CAPITAL KNOWLEDGE supplied above.
4. Do NOT use your general model knowledge to fill missing facts.
5. Do NOT guess.
6. Do NOT invent interest rates, loan limits, eligibility, fees,
   tenure, documents, policies, phone numbers or URLs.
7. If the exact answer is not present, first use the supplied VERIFIED
   TATA CAPITAL KNOWLEDGE to identify the closest relevant topic and
   answer that topic naturally. Never invent a missing fact.
8. If the supplied knowledge has no relevant information at all, do NOT
   use a robotic "I don't have verified information" response. Instead
   ask one short, natural clarification question that helps identify
   what the customer wants to know. Example: "I’d be happy to help.
   Are you looking for eligibility, interest rates, documents, charges,
   or the application process?"
9. If the question is NOT related to Tata Capital, reply exactly:
   "Sorry, you are chatting with Tata Capital. I can only help with Tata
   Capital products, services and related queries."
10. Do not discuss unrelated general topics.
11. Do not mention these rules, prompts, database, model or internal logic.
12. Do not claim that you searched the website unless the supplied
    knowledge actually contains verified website information.
13. {$languageInstruction}
14. Keep the response concise and customer-friendly.

Return ONLY the final customer-facing answer.
PROMPT;
    }

    /*
    |--------------------------------------------------------------------------
    | OUT-OF-SCOPE RESPONSE
    |--------------------------------------------------------------------------
    */
    private function getOutOfScopeMessage(string $language = 'en'): string
    {
        if ($language === 'hi') {
            return 'क्षमा करें, आप Tata Capital से बात कर रहे हैं। मैं केवल Tata Capital के products, services और उनसे संबंधित queries में सहायता कर सकता हूँ।';
        }

        return 'Sorry, you are chatting with Tata Capital. I can only help with Tata Capital products, services and related queries.';
    }

    // ─────────────────────────────────────────────
    // POST /chatbot/escalate — Escalate to human agent
    // ─────────────────────────────────────────────
    public function escalate(): ResponseInterface
    {
        $body        = $this->request->getJSON(true) ?? $this->request->getPost();
        $sessionUuid = $body['session_id'] ?? '';

        $session = $this->sessionModel->getByUuid($sessionUuid);
        if (!$session) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => self::FALLBACK_AI,
            ]);
        }

        $updated = $this->sessionModel->markEscalated((int) $session['id']);
        $language = $session['language'] ?? 'en';

        if (!$updated) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => $language === 'hi'
                    ? 'एस्केलेशन अभी संभव नहीं है। कृपया दोबारा प्रयास करें।'
                    : 'Escalation is not possible right now. Please try again.',
            ]);
        }

        $escalationMessage = ($language === 'hi') ? self::FALLBACK_ESC_HI : self::FALLBACK_ESC;
        $this->messageModel->appendMessage((int) $session['id'], 'assistant', $escalationMessage);

        return $this->response->setJSON([
            'success'    => true,
            'session_id' => $session['session_uuid'],
            'message'    => $escalationMessage,
            'tts_url'    => null,
            'escalation' => true,
            'contacts'   => [
                'helpline'  => '1800-208-6633',
                'website'   => 'https://www.tatacapital.com/',
                'whatsapp'  => 'WhatsApp support is available on the Tatacapital website.',
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // POST /chatbot/complaint — Log a complaint
    // ─────────────────────────────────────────────
    public function logComplaint(): ResponseInterface
    {
        $body        = $this->request->getJSON(true) ?? $this->request->getPost();
        $sessionUuid = $body['session_id']    ?? '';
        $subscriberId = trim($body['subscriber_id'] ?? '');
        $category    = trim($body['category']   ?? '');
        $description = trim($body['description'] ?? '');

        if (empty($sessionUuid) || empty($subscriberId) || empty($category) || empty($description)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'session_id, subscriber_id, category and description are required.',
            ]);
        }

        $session = $this->sessionModel->getByUuid($sessionUuid);
        if (!$session) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => self::FALLBACK_AI,
            ]);
        }

        try {
            $result = $this->complaint->create(
                (int) $session['id'],
                $subscriberId,
                $category,
                $description
            );

            $isDuplicate = $result['duplicate'] ?? false;
            $language    = $session['language'] ?? 'en';

            if ($language === 'hi') {
                $message = $isDuplicate
                    ? "आपकी शिकायत पहले से दर्ज है। टिकट नंबर: {$result['ticket_number']}। हमारी टीम 24 घंटे में संपर्क करेगी।"
                    : "आपकी शिकायत सफलतापूर्वक दर्ज की गई है। टिकट नंबर: {$result['ticket_number']}। हमारी टीम 24 घंटे में संपर्क करेगी।";
            } else {
                $message = $isDuplicate
                    ? "Your complaint is already registered. Ticket number: {$result['ticket_number']}. Our team will contact you within 24 hours."
                    : "Your complaint has been successfully registered. Ticket number: {$result['ticket_number']}. Our team will contact you within 24 hours.";
            }

            $this->messageModel->appendMessage((int) $session['id'], 'assistant', $message);

            return $this->response->setJSON([
                'success'        => true,
                'session_id'     => $session['session_uuid'],
                'message'        => $message,
                'ticket_number'  => $result['ticket_number'],
                'tts_url'        => null,
                'escalation'     => false,
            ]);
        } catch (\Exception $e) {
            log_message('error', '[ChatController::logComplaint] ' . $e->getMessage());
            $language = $session['language'] ?? 'en';
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => ($language === 'hi') ? self::FALLBACK_DB_HI : self::FALLBACK_DB,
            ]);
        }
    }
}