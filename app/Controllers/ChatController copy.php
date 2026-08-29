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
                'ticket_number' => null,
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
    | 1. CURRENT STEP
    |--------------------------------------------------------------------------
    */
    $currentStepId = $this->getCurrentChatbotStep($sessionId);

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


    /*
    |--------------------------------------------------------------------------
    | 5. NORMALIZE USER TEXT
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

            // Tenure is currently the last step in the DB flow.
            // Do NOT return null here because that would send the request
            // to Ollama and it can repeat the same tenure question.
            $this->clearCurrentChatbotStep($sessionId);

            $selectedTenure = trim((string) ($matchedOption['label'] ?? $matchedOption['value'] ?? ''));

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
        int $sessionId,
        string $stepKey,
        string $value
    ): void {
        $db = \Config\Database::connect();

        /*
         * IMPORTANT:
         * Is function ko aapke actual session-data table ke
         * according adjust karna padega.
         *
         * Example:
         *
         * $db->table('chatbot_session_data')->insert([
         *     'session_id' => $sessionId,
         *     'key'        => $stepKey,
         *     'value'      => $value,
         * ]);
         */
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
7. If the question is Tata Capital related but the supplied knowledge
   does not contain the answer, say:
   "I’m sorry, I don’t have verified information about that Tata Capital
   query right now. Please check the official Tata Capital website or
   contact Tata Capital customer support."
8. If the question is NOT related to Tata Capital, reply exactly:
   "Sorry, you are chatting with Tata Capital. I can only help with Tata
   Capital products, services and related queries."
9. Do not discuss unrelated general topics.
10. Do not mention these rules, prompts, database, model or internal logic.
11. Do not claim that you searched the website unless the supplied
    knowledge actually contains verified website information.
12. {$languageInstruction}
13. Keep the response concise and customer-friendly.

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